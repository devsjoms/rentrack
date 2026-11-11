<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Services\BillingBalanceService;
use App\Services\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdvanceCreditController extends Controller
{
    public function show(Billing $billing, BillingBalanceService $service): View
    {
        $billing->loadMissing(['tenant', 'room']);
        $balance = $service->calculate((int) $billing->billing_id);
        $credits = $this->creditBalances((int) $billing->tenant_id);
        $availableCents = $credits['available'];
        $remainingCents = $balance['remaining_centavos'];
        $maxCents = min($availableCents, $remainingCents);
        $canApply = $billing->status === 'Unpaid'
            && $balance['is_reconciled'] && $maxCents > 0
            && !$this->hasUnresolvedPayment((int) $billing->billing_id);

        return view('admin.billings.advance-credit', compact(
            'billing', 'balance', 'availableCents', 'remainingCents', 'maxCents', 'canApply'
        ));
    }

    public function store(Request $request, Billing $billing, BillingBalanceService $service): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'],
        ]);

        $wantedCents = Money::toCents((string) $data['amount']);

        DB::transaction(function () use ($request, $billing, $service, $wantedCents) {
            // Consistent locking order: tenant -> bill -> credits -> allocations.
            $tenant = DB::table('tenants')->where('tenant_id', $billing->tenant_id)
                ->lockForUpdate()->first();

            if (!$tenant) {
                throw ValidationException::withMessages(['billing' => 'Tenant not found.']);
            }

            $bill = DB::table('billings')->where('billing_id', $billing->billing_id)
                ->lockForUpdate()->first();

            if (!$bill || $bill->status !== 'Unpaid') {
                throw ValidationException::withMessages(['billing' => 'This bill is already paid or unavailable.']);
            }

            $balance = $service->calculate((int) $bill->billing_id);
            if (!$balance['is_reconciled'] || $balance['remaining_centavos'] <= 0) {
                throw ValidationException::withMessages(['billing' => 'Resolve the billing reconciliation issue first.']);
            }

            if ($this->hasUnresolvedPayment((int) $bill->billing_id)) {
                throw ValidationException::withMessages(['billing' => 'An unresolved payment attempt exists for this bill.']);
            }

            $credits = $this->creditBalances((int) $bill->tenant_id, true);
            if ($wantedCents > $balance['remaining_centavos'] || $wantedCents > $credits['available']) {
                throw ValidationException::withMessages(['amount' => 'Amount exceeds the available credit or remaining balance.']);
            }

            $left = $wantedCents;
            foreach ($credits['sources'] as $source) {
                if ($left <= 0) {
                    break;
                }
                $take = min($left, $source['available']);
                if ($take <= 0) {
                    continue;
                }

                $existing = DB::table('advance_payment_allocations')
                    ->where('advance_payment_id', $source['id'])
                    ->where('billing_id', $bill->billing_id)
                    ->lockForUpdate()->first();

                $now = now('Asia/Manila');
                if ($existing) {
                    DB::table('advance_payment_allocations')
                        ->where('allocation_id', $existing->allocation_id)
                        ->update([
                            'amount' => Money::fromCents(Money::toCents($existing->amount) + $take),
                            'applied_at' => $now,
                        ]);
                } else {
                    DB::table('advance_payment_allocations')->insert([
                        'advance_payment_id' => $source['id'],
                        'billing_id' => $bill->billing_id,
                        'amount' => Money::fromCents($take),
                        'applied_at' => $now,
                    ]);
                }
                $left -= $take;
            }

            if ($left !== 0) {
                throw ValidationException::withMessages(['amount' => 'Credit allocation could not be completed.']);
            }

            $after = $balance['remaining_centavos'] - $wantedCents;
            if ($after === 0) {
                DB::table('billings')->where('billing_id', $bill->billing_id)->update([
                    'status' => 'Paid',
                    'payment_date' => now('Asia/Manila')->toDateString(),
                ]);
            }

            // Applying existing credit is NOT a new cash receipt.
            DB::table('audit_log')->insert([
                'user_id' => $request->user()->getAuthIdentifier(),
                'action' => 'ADVANCE_CREDIT_APPLIED',
                'description' => 'Billing #' . $bill->billing_id
                    . ': applied PHP ' . Money::fromCents($wantedCents)
                    . ' of previously received advance credit; remaining PHP '
                    . Money::fromCents($after) . '.',
                'created_at' => now('Asia/Manila'),
            ]);
        }, 3);

        return redirect()->route('admin.billings.advance-credit.show', $billing)
            ->with('status', 'Advance credit applied successfully.');
    }

    private function hasUnresolvedPayment(int $billingId): bool
    {
        return DB::table('payment_transactions')
            ->where('billing_id', $billingId)
            ->whereNotIn('status', ['Paid', 'Failed', 'Cancelled', 'Expired'])
            ->exists();
    }

    private function creditBalances(int $tenantId, bool $lock = false): array
    {
        $q = DB::table('advance_payments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'Paid')
            ->orderBy('payment_date')
            ->orderBy('advance_payment_id');

        if ($lock) {
            $q->lockForUpdate();
        }

        $payments = $q->get();
        if ($payments->isEmpty()) {
            return ['available' => 0, 'sources' => []];
        }

        $allocQ = DB::table('advance_payment_allocations')
            ->whereIn('advance_payment_id', $payments->pluck('advance_payment_id')->all());
        if ($lock) {
            $allocQ->lockForUpdate();
        }

        $usedByAdvance = [];
        foreach ($allocQ->get() as $allocation) {
            $id = (int) $allocation->advance_payment_id;
            $usedByAdvance[$id] = ($usedByAdvance[$id] ?? 0)
                + Money::toCents($allocation->amount);
        }

        $sources = [];
        $available = 0;
        foreach ($payments as $payment) {
            $id = (int) $payment->advance_payment_id;
            $free = Money::toCents($payment->amount) - ($usedByAdvance[$id] ?? 0);
            if ($free < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Advance payment #' . $id . ' has overallocated credit.',
                ]);
            }
            $available += $free;
            $sources[] = ['id' => $id, 'available' => $free];
        }

        return ['available' => $available, 'sources' => $sources];
    }
}
