<?php

namespace App\View\Components;

use App\Services\BillingBalanceService;
use App\Services\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Read-only overview for the currently signed-in tenant.
 * Does not accept a tenant_id from the request or view.
 */
class TenantBillingOverview extends Component
{
    public bool $hasTenant = false;
    public int $outstandingCents = 0;
    public int $availableCreditCents = 0;
    public int $openBills = 0;
    public bool $needsReview = false;
    public array $recentBills = [];

    public function __construct()
    {
        $account = Auth::user();

        // Authenticated tenant_id=0 is valid in the existing database.
        if (!$account || $account->role !== 'tenant' || $account->tenant_id === null) {
            return;
        }

        $tenantId = (int) $account->tenant_id;
        $this->hasTenant = DB::table('tenants')
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$this->hasTenant) {
            return;
        }

        $balanceService = app(BillingBalanceService::class);
        $balanceCache = [];

        // Read only the authenticated tenant's bill IDs.
        $unpaidIds = DB::table('billings')
            ->where('tenant_id', $tenantId)
            ->where('status', 'Unpaid')
            ->orderBy('billing_id')
            ->pluck('billing_id');

        foreach ($unpaidIds as $billingId) {
            $id = (int) $billingId;
            $balance = $balanceService->calculate($id);
            $balanceCache[$id] = $balance;
            $remaining = (int) $balance['remaining_centavos'];

            // Include remaining balance even when status needs reconciliation.
            $this->outstandingCents += $remaining;

            if ($remaining > 0) {
                $this->openBills++;
            }

            if (!$balance['is_reconciled']) {
                $this->needsReview = true;
            }
        }

        // Verified advances only. Allocations are already received money,
        // not a separate payment to add or subtract a second time.
        $advances = DB::table('advance_payments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'Paid')
            ->get(['advance_payment_id', 'amount']);

        $advanceIds = $advances->pluck('advance_payment_id')->all();
        $usedCentsByAdvance = [];

        if ($advanceIds !== []) {
            $allocations = DB::table('advance_payment_allocations')
                ->whereIn('advance_payment_id', $advanceIds)
                ->get(['advance_payment_id', 'amount']);

            foreach ($allocations as $allocation) {
                $id = (int) $allocation->advance_payment_id;
                $usedCentsByAdvance[$id] = ($usedCentsByAdvance[$id] ?? 0)
                    + Money::toCents((string) $allocation->amount);
            }
        }

        foreach ($advances as $advance) {
            $advanceId = (int) $advance->advance_payment_id;
            $received = Money::toCents((string) $advance->amount);
            $used = $usedCentsByAdvance[$advanceId] ?? 0;
            $this->availableCreditCents += max(0, $received - $used);

            if ($used > $received) {
                $this->needsReview = true;
            }
        }

        // Recent statements link to the full read-only tenant billing page.
        $bills = DB::table('billings')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('billing_period')
            ->orderByDesc('billing_id')
            ->limit(4)
            ->get(['billing_id', 'billing_period', 'due_date', 'amount', 'status']);

        foreach ($bills as $bill) {
            $id = (int) $bill->billing_id;
            $balance = $balanceCache[$id]
                ?? $balanceService->calculate($id);

            if (!$balance['is_reconciled']) {
                $this->needsReview = true;
            }

            $partialPaid = (
                (int) $balance['advance_applied_centavos']
                + (int) $balance['payments_received_centavos']
            ) > 0;

            $this->recentBills[] = [
                'id' => $id,
                'month' => $bill->billing_period
                    ? \Carbon\Carbon::parse($bill->billing_period)->format('M Y')
                    : 'Unspecified month',
                'due_date' => $bill->due_date
                    ? \Carbon\Carbon::parse($bill->due_date)->format('M d, Y')
                    : 'Not set',
                'total_centavos' => Money::toCents((string) $bill->amount),
                'remaining_centavos' => (int) $balance['remaining_centavos'],
                'status' => $bill->status === 'Paid'
                    ? 'Paid'
                    : ($partialPaid ? 'Partially Settled' : 'Unpaid'),
                'is_reconciled' => (bool) $balance['is_reconciled'],
            ];
        }
    }

    public function render(): View
    {
        return view('components.tenant-billing-overview');
    }
}
