<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class BillingBalanceService
{
    private function toCentavos($amount): int
    {
        try {
            return Money::toCents((string) $amount);
        } catch (\InvalidArgumentException) {
            throw new RuntimeException('Invalid financial amount.');
        }
    }

    public function calculate(int $billingId): array
    {
        $billing = DB::table('billings')
            ->where('billing_id', $billingId)
            ->first();

        if (!$billing) {
            throw new RuntimeException(
                'Billing record not found.'
            );
        }

        $total = $this->toCentavos(
            $billing->amount
        );

        $itemsTotal = $this->toCentavos(
            DB::table('billing_items')
                ->where('billing_id', $billingId)
                ->sum('amount')
        );

        $advanceApplied = $this->toCentavos(
            DB::table('advance_payment_allocations')
                ->where('billing_id', $billingId)
                ->sum('amount')
        );

        $paymentsReceived = (int) DB::table(
            'payment_transactions'
        )
            ->where('billing_id', $billingId)
            ->where('status', 'Paid')
            ->sum('amount_centavos');

        $rawRemaining = $total
            - $advanceApplied
            - $paymentsReceived;

        $remaining = max(0, $rawRemaining);

        $issues = [];

        if ($itemsTotal !== $total) {
            $issues[] =
                'Billing items do not match the bill total.';
        }

        if ($rawRemaining < 0) {
            $issues[] =
                'Payments and credits exceed the bill total.';
        }

        if (
            $billing->status === 'Paid'
            && $rawRemaining !== 0
        ) {
            $issues[] =
                'Bill is marked Paid but not fully settled.';
        }

        if (
            $billing->status === 'Unpaid'
            && $rawRemaining === 0
        ) {
            $issues[] =
                'Bill is settled but still marked Unpaid.';
        }

        return [
            'billing_id' => $billingId,
            'status' => $billing->status,

            'total_centavos' => $total,

            'items_total_centavos' => $itemsTotal,

            'advance_applied_centavos' =>
                $advanceApplied,

            'payments_received_centavos' =>
                $paymentsReceived,

            'remaining_centavos' => $remaining,

            'is_fully_settled' =>
                $rawRemaining === 0,

            'is_reconciled' =>
                count($issues) === 0,

            'issues' => $issues,
        ];
    }
}
