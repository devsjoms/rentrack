<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Services\BillingBalanceService;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    public function store(Request $request, Billing $billing, BillingBalanceService $balances, PayMongoService $paymongo): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;
        if ($request->user()->role !== 'tenant' || $tenantId === null || (int) $billing->tenant_id !== (int) $tenantId) {
            abort(404);
        }
        if ((bool) config('services.paymongo.live_mode', false) || !str_starts_with((string) config('services.paymongo.secret_key', ''), 'sk_test_')) {
            return back()->withErrors(['payment' => 'RentTrack online payments are restricted to PayMongo test mode.']);
        }

        try {
            $checkoutUrl = DB::transaction(function () use ($billing, $balances, $paymongo) {
                // Shared lock order with existing Admin settlement controllers.
                DB::table('tenants')->where('tenant_id', $billing->tenant_id)->lockForUpdate()->first();
                $lockedBill = DB::table('billings')->where('billing_id', $billing->billing_id)->lockForUpdate()->first();
                if (!$lockedBill || $lockedBill->status !== 'Unpaid') {
                    throw ValidationException::withMessages(['payment' => 'This bill is no longer payable.']);
                }
                $summary = $balances->calculate((int) $billing->billing_id);
                if (!$summary['is_reconciled'] || $summary['remaining_centavos'] <= 0) {
                    throw ValidationException::withMessages(['payment' => 'Bill balance needs reconciliation before checkout.']);
                }
                $existing = DB::table('payment_transactions')
                    ->where('billing_id', $billing->billing_id)
                    ->whereNotIn('status', ['Paid', 'Failed', 'Cancelled', 'Expired'])
                    ->lockForUpdate()->first();
                if ($existing) {
                    // Never initiate a second charge if a pending record exists.
                    if ($existing->provider === 'paymongo' && $existing->checkout_url
                        && (int) $existing->amount_centavos === (int) $summary['remaining_centavos']
                        && str_starts_with((string) $existing->checkout_url, 'https://checkout.paymongo.com/')) {
                        return $existing->checkout_url;
                    }
                    throw ValidationException::withMessages(['payment' => 'An unresolved payment session exists. Ask the admin to reconcile or expire it before another checkout.']);
                }

                $reference = 'RT-' . (string) Str::ulid();
                // Keep lock until the unique checkout record is saved; do not retry uncertain network failures automatically.
                $session = $paymongo->createCheckout(
                    (int) $summary['remaining_centavos'],
                    $reference,
                    route('tenant.billings.index', ['checkout' => 'returned']),
                    route('tenant.billings.index', ['checkout' => 'cancelled'])
                );
                $url = (string) $session['attributes']['checkout_url'];
                if (!str_starts_with($url, 'https://checkout.paymongo.com/')) {
                    throw ValidationException::withMessages(['payment' => 'Unexpected PayMongo checkout URL.']);
                }
                if (($session['attributes']['livemode'] ?? false) !== false) {
                    throw ValidationException::withMessages(['payment' => 'Unexpected live-mode checkout session.']);
                }
                DB::table('payment_transactions')->insert([
                    'billing_id' => $billing->billing_id,
                    'provider' => 'paymongo',
                    'payment_method' => 'GCash',
                    'recorded_by_user_id' => null,
                    'external_reference' => null,
                    'notes' => 'PayMongo hosted checkout - test mode',
                    'reference_number' => $reference,
                    'checkout_session_id' => $session['id'],
                    'provider_payment_id' => null,
                    'checkout_url' => $url,
                    'amount_centavos' => $summary['remaining_centavos'],
                    'currency' => 'PHP',
                    'status' => 'Pending',
                    'is_live' => 0,
                    'paid_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return $url;
            }, 1);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['payment' => 'Unable to start checkout. No payment was marked as Paid. Review existing sessions before retrying.']);
        }
        return redirect()->away($checkoutUrl);
    }
}
