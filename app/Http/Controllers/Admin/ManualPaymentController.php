<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Services\BillingBalanceService;
use App\Services\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ManualPaymentController extends Controller
{
    public function show(Billing $billing, BillingBalanceService $service): View
    {
        $billing->loadMissing(['tenant', 'room']);
        $balance = $service->calculate((int) $billing->billing_id);
        $remainingCents = $balance['remaining_centavos'];
        $canPay = $billing->status === 'Unpaid' && $balance['is_reconciled']
            && $remainingCents > 0 && !$this->hasUnresolvedPayment((int) $billing->billing_id);

        return view('admin.billings.manual-payment', compact('billing', 'balance', 'remainingCents', 'canPay'));
    }

    public function store(Request $request, Billing $billing, BillingBalanceService $service): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['Cash', 'GCash Transfer', 'Bank Transfer'])],
            'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . now('Asia/Manila')->toDateString()],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'confirm_received' => ['required', 'accepted'],
        ]);

        if ($data['payment_method'] !== 'Cash' && blank($data['external_reference'] ?? null)) {
            throw ValidationException::withMessages([
                'external_reference' => 'Transaction reference is required for transfers.',
            ]);
        }

        DB::transaction(function () use ($request, $billing, $service, $data) {
            // Lock tenant then bill: same order as advance-credit application.
            $tenant = DB::table('tenants')->where('tenant_id', $billing->tenant_id)
                ->lockForUpdate()->first();
            if (!$tenant) {
                throw ValidationException::withMessages(['billing' => 'Tenant not found.']);
            }

            $bill = DB::table('billings')->where('billing_id', $billing->billing_id)
                ->lockForUpdate()->first();
            if (!$bill || $bill->status !== 'Unpaid') {
                throw ValidationException::withMessages(['billing' => 'Billing is already paid or unavailable.']);
            }

            if ($this->hasUnresolvedPayment((int) $bill->billing_id)) {
                throw ValidationException::withMessages(['billing' => 'An unresolved payment attempt exists for this bill.']);
            }

            $balance = $service->calculate((int) $bill->billing_id);
            if (!$balance['is_reconciled'] || $balance['remaining_centavos'] <= 0) {
                throw ValidationException::withMessages(['billing' => 'Please reconcile the bill before accepting payment.']);
            }

            // Do not permit accidentally reusing one transfer reference on multiple bills.
            $externalRef = trim((string) ($data['external_reference'] ?? ''));
            if ($data['payment_method'] !== 'Cash' && DB::table('payment_transactions')
                ->where('provider', 'manual')->where('status', 'Paid')
                ->where('external_reference', $externalRef)->exists()) {
                throw ValidationException::withMessages(['external_reference' => 'This transfer reference was already recorded.']);
            }

            // This payment settles EXACTLY the remaining balance, not the original bill total.
            $amountCents = (int) $balance['remaining_centavos'];
            $receivedAt = now('Asia/Manila');
            $reference = 'MAN-' . (string) Str::ulid();

            DB::table('payment_transactions')->insert([
                'billing_id' => $bill->billing_id,
                'provider' => 'manual',
                'payment_method' => $data['payment_method'],
                'recorded_by_user_id' => $request->user()->getAuthIdentifier(),
                'external_reference' => $externalRef !== '' ? $externalRef : null,
                'notes' => $data['notes'] ?? null,
                'reference_number' => $reference,
                'checkout_session_id' => null,
                'provider_payment_id' => null,
                'checkout_url' => null,
                'amount_centavos' => $amountCents,
                'currency' => 'PHP',
                'status' => 'Paid',
                'is_live' => 0,
                // Confirmation timestamp, while payment_date stores actual receipt date.
                'paid_at' => $receivedAt,
                'created_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ]);

            DB::table('billings')->where('billing_id', $bill->billing_id)->update([
                'status' => 'Paid',
                'payment_date' => $data['payment_date'],
            ]);

            DB::table('transaction_log')->insert([
                'user_id' => $request->user()->getAuthIdentifier(),
                'transaction_type' => 'MANUAL_BILL_PAYMENT',
                'amount' => Money::fromCents($amountCents),
                'description' => 'Payment ' . $reference . ' for Billing #' . $bill->billing_id
                    . ' (' . $data['payment_method'] . '), received on ' . $data['payment_date'] . '.',
                'transaction_date' => $receivedAt,
            ]);

            DB::table('audit_log')->insert([
                'user_id' => $request->user()->getAuthIdentifier(),
                'action' => 'MANUAL_PAYMENT_CONFIRMED',
                'description' => 'Billing #' . $bill->billing_id . ': received PHP '
                    . Money::fromCents($amountCents) . ' to settle remaining balance. Ref: ' . $reference,
                'created_at' => $receivedAt,
            ]);
        }, 3);

        return redirect()->route('admin.billings.index')
            ->with('status', 'Remaining balance paid in full and recorded successfully.');
    }

    private function hasUnresolvedPayment(int $billingId): bool
    {
        return DB::table('payment_transactions')
            ->where('billing_id', $billingId)
            ->whereNotIn('status', ['Paid', 'Failed', 'Cancelled', 'Expired'])
            ->exists();
    }
}
