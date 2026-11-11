<?php

namespace App\Http\Controllers;

use App\Services\BillingBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PayMongoWebhookController extends Controller
{
    public function handle(Request $request, BillingBalanceService $balances): JsonResponse
    {
        $raw = $request->getContent();
        $payload = json_decode($raw, true);
        if (!is_array($payload) || !$this->verify($request, $raw, $payload)) {
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        $event = $payload['data'] ?? [];
        $eventId = $event['id'] ?? null;
        $attrs = $event['attributes'] ?? [];
        $type = $attrs['type'] ?? null;
        if (!is_string($eventId) || $eventId === '' || !is_string($type)) {
            return response()->json(['error' => 'Invalid event structure'], 400);
        }
        if ($type !== 'checkout_session.payment.paid') {
            return response()->json(['received' => true, 'ignored' => true]);
        }

        $session = $attrs['data'] ?? null;
        $sid = data_get($session, 'id');
        $sa = data_get($session, 'attributes', []);
        $reference = data_get($sa, 'reference_number');
        $payments = data_get($sa, 'payments', []);
        if (!is_string($sid) || !is_array($payments)) {
            return response()->json(['error' => 'Missing session/payment details'], 422);
        }

        $paid = collect($payments)->first(fn ($p) => data_get($p, 'attributes.status') === 'paid');
        $paymentId = data_get($paid, 'id');
        $paymentAmount = data_get($paid, 'attributes.amount');
        $currency = data_get($paid, 'attributes.currency');
        // Reject ambiguous or incomplete webhook evidence rather than guessing the paid amount.
        if (!is_string($paymentId) || !is_numeric($paymentAmount) || !is_string($currency)) {
            Log::warning('PayMongo event missing verifiable paid payment', ['event_id' => $eventId]);
            return response()->json(['error' => 'Paid payment details unavailable'], 422);
        }

        try {
            DB::transaction(function () use ($balances, $eventId, $type, $sid, $reference, $paid, $paymentId, $paymentAmount, $currency, $raw) {
                // Find billing ID without trusting an unverified browser input.
                $candidate = DB::table('payment_transactions')->where('checkout_session_id', $sid)->first();
                if (!$candidate) {
                    throw new \RuntimeException('Unknown checkout session');
                }
                $billId = (int) $candidate->billing_id;
                $billTenant = DB::table('billings')->where('billing_id', $billId)->value('tenant_id');
                DB::table('tenants')->where('tenant_id', $billTenant)->lockForUpdate()->first();
                $bill = DB::table('billings')->where('billing_id', $billId)->lockForUpdate()->first();
                $payment = DB::table('payment_transactions')->where('payment_id', $candidate->payment_id)->lockForUpdate()->first();
                $existing = DB::table('payment_webhook_events')->where('provider_event_id', $eventId)->first();
                if ($existing) {
                    return; // Unique event ID provides idempotency.
                }
                if (!$bill || !$payment || $payment->provider !== 'paymongo'
                    || $payment->checkout_session_id !== $sid || $payment->reference_number !== $reference
                    || (bool) $payment->is_live || strtoupper($currency) !== 'PHP'
                    || (int) $paymentAmount !== (int) $payment->amount_centavos
                    || (int) $paymentAmount <= 0) {
                    throw new \RuntimeException('Checkout payment mismatch');
                }
                if ($payment->status !== 'Pending') {
                    throw new \RuntimeException('Payment is not pending; manual reconciliation needed');
                }
                if ($bill->status !== 'Unpaid') {
                    throw new \RuntimeException('Billing not unpaid; manual reconciliation needed');
                }
                $summary = $balances->calculate($billId);
                if (!$summary['is_reconciled'] || (int) $summary['remaining_centavos'] !== (int) $payment->amount_centavos) {
                    throw new \RuntimeException('Billing ledger mismatch; manual reconciliation needed');
                }
                $now = now('Asia/Manila');
                DB::table('payment_transactions')->where('payment_id', $payment->payment_id)->update([
                    'status' => 'Paid', 'provider_payment_id' => $paymentId, 'paid_at' => $now, 'updated_at' => $now,
                ]);
                $after = $balances->calculate($billId);
                if ((int) $after['remaining_centavos'] !== 0) {
                    throw new \RuntimeException('Unexpected remaining balance after payment');
                }
                DB::table('billings')->where('billing_id', $billId)->update([
                    'status' => 'Paid', 'payment_date' => $now->toDateString(),
                ]);
                DB::table('payment_webhook_events')->insert([
                    'provider_event_id' => $eventId,
                    'payment_id' => $payment->payment_id,
                    'event_type' => $type,
                    'checkout_session_id' => $sid,
                    'payload_sha256' => hash('sha256', $raw),
                    'is_live' => 0,
                    'processing_status' => 'Processed',
                    'received_at' => $now,
                    'processed_at' => $now,
                ]);
                DB::table('audit_log')->insert([
                    'user_id' => null,
                    'action' => 'PAYMONGO_PAYMENT_CONFIRMED',
                    'description' => 'Test-mode PayMongo payment confirmed: bill #' . $billId . ', session ' . $sid . ', payment ' . $paymentId,
                    'created_at' => $now,
                ]);
            }, 3);
        } catch (Throwable $e) {
            Log::error('PayMongo webhook requires reconciliation', ['event_id' => $eventId, 'message' => $e->getMessage()]);
            return response()->json(['error' => 'Processing pending reconciliation'], 409);
        }
        return response()->json(['received' => true]);
    }

    private function verify(Request $request, string $raw, array $payload): bool
    {
        $secret = config('services.paymongo.webhook_secret');
        if (!is_string($secret) || $secret === '' || (bool) config('services.paymongo.live_mode', false)) {
            return false;
        }
        if (($payload['data']['attributes']['livemode'] ?? null) !== false) {
            return false;
        }
        $components = [];
        foreach (explode(',', (string) $request->header('Paymongo-Signature')) as $piece) {
            $parts = explode('=', trim($piece), 2);
            if (count($parts) === 2) $components[trim($parts[0])] = trim($parts[1]);
        }
        $timestamp = $components['t'] ?? '';
        $signature = $components['te'] ?? '';
        if (!ctype_digit($timestamp) || !preg_match('/^[a-f0-9]{64}$/i', $signature)) {
            return false;
        }
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $raw, $secret);
        return hash_equals($expected, strtolower($signature));
    }
}
