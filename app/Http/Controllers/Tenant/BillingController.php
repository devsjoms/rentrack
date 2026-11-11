<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Services\BillingBalanceService;
use App\Services\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(BillingBalanceService $balanceService): View
    {
        $account = Auth::user();

        abort_unless($account && $account->role === 'tenant', 403);

        // IMPORTANT: tenant_id = 0 is valid in the imported RentTrack database.
        // Never accept a tenant ID from the request for this page.
        abort_if($account->tenant_id === null, 403, 'No tenant profile is linked to this account.');

        $tenantId = (int) $account->tenant_id;
        $tenant = DB::table('tenants')->where('tenant_id', $tenantId)->first();
        abort_unless($tenant, 403, 'Tenant profile not found.');

        $billings = Billing::query()
            ->where('tenant_id', $tenantId)
            ->with(['items', 'room'])
            ->orderByDesc('billing_period')
            ->orderByDesc('billing_id')
            ->paginate(10);

        // Reuse exactly the same calculation used by Admin Billing.
        $billings->getCollection()->each(function ($billing) use ($balanceService): void {
            $billing->balance_summary = $balanceService->calculate((int) $billing->billing_id);
        });

        // Outstanding balance covers ALL tenant bills, not just this page.
        $unpaidIds = DB::table('billings')
            ->where('tenant_id', $tenantId)
            ->where('status', 'Unpaid')
            ->pluck('billing_id');

        $outstandingCents = 0;
        foreach ($unpaidIds as $billingId) {
            $balance = $balanceService->calculate((int) $billingId);
            $outstandingCents += (int) $balance['remaining_centavos'];
        }

        // Advance payment history and unused credit (only verified Paid advances count).
        $advancePayments = DB::table('advance_payments')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('payment_date')
            ->orderByDesc('advance_payment_id')
            ->get();

        $advanceIds = $advancePayments->pluck('advance_payment_id')->all();
        $usedByAdvance = [];

        if ($advanceIds !== []) {
            $allocations = DB::table('advance_payment_allocations')
                ->whereIn('advance_payment_id', $advanceIds)
                ->get(['advance_payment_id', 'amount']);

            foreach ($allocations as $allocation) {
                $id = (int) $allocation->advance_payment_id;
                $usedByAdvance[$id] = ($usedByAdvance[$id] ?? 0)
                    + Money::toCents((string) $allocation->amount);
            }
        }

        $totalAdvanceCents = 0;
        $availableCreditCents = 0;
        $creditIssue = false;

        foreach ($advancePayments as $advance) {
            $advanceId = (int) $advance->advance_payment_id;
            $amountCents = Money::toCents((string) $advance->amount);
            $appliedCents = $usedByAdvance[$advanceId] ?? 0;
            $isVerified = $advance->status === 'Paid';

            $advance->amount_centavos = $amountCents;
            $advance->applied_centavos = $appliedCents;
            $advance->available_centavos = $isVerified
                ? max(0, $amountCents - $appliedCents)
                : 0;

            if ($isVerified) {
                $totalAdvanceCents += $amountCents;
                $availableCreditCents += $advance->available_centavos;

                if ($appliedCents > $amountCents) {
                    $creditIssue = true;
                }
            }
        }

        // Show transactions for this tenant's bills only.
        // Pending/failed attempts are shown with their actual status, not as received funds.
        $payments = DB::table('payment_transactions as p')
            ->join('billings as b', 'b.billing_id', '=', 'p.billing_id')
            ->where('b.tenant_id', $tenantId)
            ->orderByDesc('p.payment_id')
            ->limit(30)
            ->get([
                'p.payment_id',
                'p.billing_id',
                'p.provider',
                'p.payment_method',
                'p.amount_centavos',
                'p.status',
                'p.paid_at',
                'p.reference_number',
                'p.external_reference',
            ]);

        return view('tenant.billings.index', compact(
            'tenant',
            'billings',
            'outstandingCents',
            'advancePayments',
            'totalAdvanceCents',
            'availableCreditCents',
            'creditIssue',
            'payments'
        ));
    }
}