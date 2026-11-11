<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Services\Money;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;


class BillingItemController extends Controller
{
    public function edit(Billing $billing): View
    {
        $billing->load([
            'tenant',
            'room',
            'items',
        ]);

        return view(
            'admin.billings.edit',
            compact('billing')
        );
    }

    private function ensureEditable(Billing $billing): void
    {
        if ($billing->status !== 'Unpaid') {
            throw ValidationException::withMessages([
                'billing' => 'Only unpaid bills can be edited.',
            ]);
        }

        if ($billing->paymentTransactions()->exists()) {
            throw ValidationException::withMessages([
                'billing' =>
                    'This bill has payment attempts. '
                    . 'Reconcile them before editing.',
            ]);
        }

        if ($billing->advanceAllocations()->exists()) {
            throw ValidationException::withMessages([
                'billing' =>
                    'This bill has applied advance credits.',
            ]);
        }
    }

    private function recalculate(Billing $billing): void
    {
        // MySQL sums DECIMAL values without PHP floating-point addition.
        $total = DB::table('billing_items')
            ->where('billing_id', $billing->billing_id)
            ->selectRaw('COALESCE(SUM(amount), 0) AS total')
            ->value('total');

        // billings.amount is DECIMAL(10,2).
        if (
            !preg_match('/^\d{1,8}\.\d{2}$/', (string) $total)
            || (float) $total <= 0
        ) {
            throw ValidationException::withMessages([
                'amount' => 'Invalid or excessive billing total.',
            ]);
        }

        $billing->amount = $total;
        $billing->save();
    }

    public function save(
        Request $request,
        Billing $billing
    ): RedirectResponse {
        $data = $request->validate([
            'item_type' => [
                'required',
                Rule::in(['Rent', 'Electricity', 'Water']),
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:99999999.99',
            ],
        ]);

        DB::transaction(function () use ($billing, $data) {
            $locked = Billing::whereKey($billing->billing_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureEditable($locked);

            $item = $locked->items()
                ->where('item_type', $data['item_type'])
                ->first();

            $oldAmount = $item?->amount;

            if ($item) {
                $item->update([
                    'amount' => $data['amount'],
                ]);
            } else {
                $locked->items()->create([
                    'item_type' => $data['item_type'],
                    'description' => $data['item_type'] . ' charge',
                    'amount' => $data['amount'],
                ]);
            }

            $this->recalculate($locked);

            DB::table('audit_log')->insert([
                'user_id' => auth()->id(),
                'action' => 'BILLING_ITEM_SAVED',
                'description' =>
                    "Bill #{$locked->billing_id}: "
                    . "{$data['item_type']} changed from "
                    . ($oldAmount ?? 'none')
                    . " to {$data['amount']}",
                'created_at' => now(),
            ]);
        }, 3);

        return redirect()
            ->route('admin.billings.edit', $billing)
            ->with('status', 'Billing charge saved successfully!');
    }

    public function destroy(
        Billing $billing,
        string $itemType
    ): RedirectResponse {
        abort_unless(
            in_array($itemType, ['Electricity', 'Water'], true),
            404
        );

        DB::transaction(function () use ($billing, $itemType) {
            $locked = Billing::whereKey($billing->billing_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureEditable($locked);

            $item = $locked->items()
                ->where('item_type', $itemType)
                ->firstOrFail();

            $oldAmount = $item->amount;

            $item->delete();

            $this->recalculate($locked);

            DB::table('audit_log')->insert([
                'user_id' => auth()->id(),
                'action' => 'BILLING_ITEM_REMOVED',
                'description' =>
                    "Bill #{$locked->billing_id}: "
                    . "Removed {$itemType} charge of {$oldAmount}",
                'created_at' => now(),
            ]);
        }, 3);

        return redirect()
            ->route('admin.billings.edit', $billing)
            ->with('status', 'Billing charge removed successfully!');
    }
    
public function saveSelected(
    Request $request,
    Billing $billing
): RedirectResponse {

    $types = ['Rent', 'Electricity', 'Water'];

    $data = $request->validate([
        'categories' => [
            'required',
            'array',
            'min:1',
            'max:3',
        ],

        'categories.*' => [
            'required',
            'string',
            'distinct',
            Rule::in($types),
        ],

        'amounts' => [
            'required',
            'array',
        ],

        'amounts.*' => [
            'nullable',
            'numeric',
            'gt:0',
            'decimal:0,2',
            'max:99999999.99',
        ],
    ]);

    $selected = $data['categories'];

    $toCents = static function ($amount): int {
        try {
            return Money::toCents((string) $amount);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'amounts' => 'Invalid charge amount.',
            ]);
        }
    };

    $toMoney = static function (int $cents): string {
        return Money::fromCents($cents);
    };

    // Validate selected amounts before modifying records.
    $charges = [];

    foreach ($selected as $type) {
        $amount = $data['amounts'][$type] ?? null;

        if ($amount === null || $amount === '') {
            throw ValidationException::withMessages([
                'amounts' => "Enter an amount for {$type}.",
            ]);
        }

        $cents = $toCents($amount);

        if ($cents <= 0) {
            throw ValidationException::withMessages([
                'amounts' =>
                    "{$type} amount must be greater than zero.",
            ]);
        }

        $charges[$type] = $toMoney($cents);
    }

    DB::transaction(function () use (
        $request,
        $billing,
        $types,
        $selected,
        $charges,
        $toCents,
        $toMoney
    ) {

        // Lock tenant first for consistent credit operations.
        $tenant = DB::table('tenants')
            ->where('tenant_id', $billing->tenant_id)
            ->lockForUpdate()
            ->first();

        if (!$tenant) {
            throw ValidationException::withMessages([
                'billing' => 'Tenant could not be found.',
            ]);
        }

        $bill = DB::table('billings')
            ->where('billing_id', $billing->billing_id)
            ->lockForUpdate()
            ->first();

        if (!$bill || $bill->status !== 'Unpaid') {
            throw ValidationException::withMessages([
                'billing' =>
                    'Only unpaid bills can be edited.',
            ]);
        }

        // Prevent changing financial obligations after payment
        // activity or applied advance credits.
        $hasPayment = DB::table('payment_transactions')
            ->where('billing_id', $bill->billing_id)
            ->exists();

        $hasCredit = DB::table('advance_payment_allocations')
            ->where('billing_id', $bill->billing_id)
            ->exists();

        if ($hasPayment || $hasCredit) {
            throw ValidationException::withMessages([
                'billing' =>
                    'Charges are locked because payment '
                    . 'activity or applied credit exists.',
            ]);
        }

        $descriptions = [
            'Rent' => 'Monthly Room Rental',
            'Electricity' => 'Electricity Charge',
            'Water' => 'Water Charge',
        ];

        // Insert new charges or update existing ones.
        foreach ($charges as $type => $amount) {

            $existing = DB::table('billing_items')
                ->where('billing_id', $bill->billing_id)
                ->where('item_type', $type)
                ->first();

            if ($existing) {

                DB::table('billing_items')
                    ->where(
                        'billing_item_id',
                        $existing->billing_item_id
                    )
                    ->update([
                        'amount' => $amount,
                        'updated_at' => now('Asia/Manila'),
                    ]);

            } else {

                DB::table('billing_items')->insert([
                    'billing_id' => $bill->billing_id,
                    'item_type' => $type,
                    'description' => $descriptions[$type],
                    'amount' => $amount,
                    'created_at' => now('Asia/Manila'),
                    'updated_at' => now('Asia/Manila'),
                ]);
            }
        }

        // Unchecked categories are removed from the bill.
        DB::table('billing_items')
            ->where('billing_id', $bill->billing_id)
            ->whereIn('item_type', $types)
            ->whereNotIn('item_type', $selected)
            ->delete();

        // Calculate the final total using stored items.
        $savedItems = DB::table('billing_items')
            ->where('billing_id', $bill->billing_id)
            ->get(['item_type', 'amount']);

        $totalCents = 0;

        foreach ($savedItems as $item) {
            $totalCents += $toCents($item->amount);
        }

        if (
            $totalCents <= 0 ||
            $totalCents > 9999999999
        ) {
            throw ValidationException::withMessages([
                'amounts' => 'Invalid total billing amount.',
            ]);
        }

        DB::table('billings')
            ->where('billing_id', $bill->billing_id)
            ->update([
                'amount' => $toMoney($totalCents),
            ]);

        DB::table('audit_log')->insert([
            'user_id' =>
                $request->user()->getAuthIdentifier(),
            'action' => 'BILLING_CHARGES_UPDATED',
            'description' =>
                "Updated charges for Billing #{$bill->billing_id}. "
                . "Selected: " . implode(', ', $selected)
                . ". Total: PHP " . $toMoney($totalCents),
            'created_at' => now('Asia/Manila'),
        ]);

    }, 3);

    return redirect()
        ->route('admin.billings.edit', $billing)
        ->with(
            'status',
            'Billing charges updated successfully.'
        );
}

}
