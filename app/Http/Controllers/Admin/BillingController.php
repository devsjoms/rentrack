<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\Money;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillingController extends Controller
{
    // Display billing records and eligible tenants.
    public function index(): View
    {
        $billings = Billing::query()
            ->with(['tenant', 'room'])
            ->orderByDesc('billing_period')
            ->orderByDesc('billing_id')
            ->paginate(15);

        $eligibleTenants = DB::table('tenants as t')
            ->join('rooms as r', 't.room_id', '=', 'r.room_id')
            ->where('t.status', 'Active')
            ->select(
                't.tenant_id',
                't.tenant_name',
                'r.room_number',
                'r.building_number',
                'r.rent_price'
            )
            ->orderBy('t.tenant_name')
            ->get();
        
            $rentPrices = DB::table('tenants as t')
                ->join(
                    'rooms as r',
                    'r.room_id',
                    '=',
                    't.room_id'
                )
                ->where('t.status', 'Active')
                ->pluck(
                    'r.rent_price',
                    't.tenant_id'
                )
                ->all();

        return view('admin.billings.index', compact(
            'eligibleTenants',
            'billings',
            'rentPrices'
        ));
    }

    // Generate one monthly bill for a selected tenant.
    
public function store(Request $request)
{
    $data = $request->validate([
        'tenant_id' => [
            'required',
            'integer',
            'exists:tenants,tenant_id',
        ],

        'billing_period' => [
            'required',
            'date_format:Y-m',
        ],

        'due_date' => [
            'required',
            'date_format:Y-m-d',
        ],

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
            Rule::in([
                'Rent',
                'Electricity',
                'Water',
            ]),
        ],

        'electricity_amount' => [
            'nullable',
            'numeric',
            'gt:0',
            'decimal:0,2',
            'max:99999999.99',
        ],

        'water_amount' => [
            'nullable',
            'numeric',
            'gt:0',
            'decimal:0,2',
            'max:99999999.99',
        ],
    ]);

    $selected = $data['categories'];
    $billingPeriod = $data['billing_period'] . '-01';

    // Convert money into integer centavos.
    // Avoid floating-point calculation errors.
    $toCents = static function ($value): int {
        try {
            return Money::toCents((string) $value);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'amount' => 'Invalid billing amount.',
            ]);
        }
    };

    $toMoney = static function (int $cents): string {
        return Money::fromCents($cents);
    };

    // Selected utilities require their respective amounts.
    foreach ([
        'Electricity' => 'electricity_amount',
        'Water' => 'water_amount',
    ] as $category => $field) {

        if (
            in_array($category, $selected, true)
            && !isset($data[$field])
        ) {
            throw ValidationException::withMessages([
                $field => "{$category} amount is required.",
            ]);
        }
    }

    DB::transaction(function () use (
        $request,
        $data,
        $selected,
        $billingPeriod,
        $toCents,
        $toMoney
    ) {

        // Lock the tenant to serialize monthly
        // bill generation requests.
        $tenant = DB::table('tenants')
            ->where('tenant_id', $data['tenant_id'])
            ->lockForUpdate()
            ->first();

        if (!$tenant || $tenant->status !== 'Active') {
            throw ValidationException::withMessages([
                'tenant_id' =>
                    'Only active tenants can be billed.',
            ]);
        }

        if ($tenant->room_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' =>
                    'Tenant must have an assigned room.',
            ]);
        }

        $room = DB::table('rooms')
            ->where('room_id', $tenant->room_id)
            ->first();

        if (!$room) {
            throw ValidationException::withMessages([
                'tenant_id' =>
                    'Assigned room could not be found.',
            ]);
        }

        // One monthly bill per tenant.
        $existingBill = DB::table('billings')
            ->where('tenant_id', $tenant->tenant_id)
            ->where('billing_period', $billingPeriod)
            ->exists();

        if ($existingBill) {
            throw ValidationException::withMessages([
                'billing_period' =>
                    'This tenant already has a bill '
                    . 'for the selected month. '
                    . 'Use Edit Charges instead.',
            ]);
        }

        $items = [];

        // RENT: Take price from database, not input.
        if (in_array('Rent', $selected, true)) {

            $rentCents = $toCents($room->rent_price);

            if ($rentCents <= 0) {
                throw ValidationException::withMessages([
                    'categories' =>
                        'Assigned room has no valid rent price.',
                ]);
            }

            $items[] = [
                'item_type' => 'Rent',
                'description' => 'Monthly Room Rental',
                'cents' => $rentCents,
            ];
        }

        // ELECTRICITY: Optional.
        if (in_array('Electricity', $selected, true)) {

            $electricityCents = $toCents(
                $data['electricity_amount']
            );

            $items[] = [
                'item_type' => 'Electricity',
                'description' => 'Electricity Charge',
                'cents' => $electricityCents,
            ];
        }

        // WATER: Optional.
        if (in_array('Water', $selected, true)) {

            $waterCents = $toCents(
                $data['water_amount']
            );

            $items[] = [
                'item_type' => 'Water',
                'description' => 'Water Charge',
                'cents' => $waterCents,
            ];
        }

        $totalCents = array_sum(
            array_column($items, 'cents')
        );

        // billings.amount is DECIMAL(10,2).
        if (
            count($items) === 0 ||
            $totalCents <= 0 ||
            $totalCents > 9999999999
        ) {
            throw ValidationException::withMessages([
                'categories' =>
                    'Select at least one valid charge. '
                    . 'Total must not exceed PHP 99,999,999.99.',
            ]);
        }

        // CREATE MAIN BILL.
        $billingId = DB::table('billings')
            ->insertGetId([
                'tenant_id' => $tenant->tenant_id,
                'room_id' => $room->room_id,
                'billing_period' => $billingPeriod,
                'billing_date' =>
                    now('Asia/Manila')->toDateString(),
                'due_date' => $data['due_date'],
                'amount' => $toMoney($totalCents),
                'status' => 'Unpaid',
                'payment_date' => null,
            ]);

        // CREATE ALL SELECTED BILLING ITEMS.
        $rows = [];

        foreach ($items as $item) {

            $rows[] = [
                'billing_id' => $billingId,
                'item_type' => $item['item_type'],
                'description' => $item['description'],
                'amount' => $toMoney($item['cents']),
                'created_at' => now('Asia/Manila'),
                'updated_at' => now('Asia/Manila'),
            ];
        }

        DB::table('billing_items')->insert($rows);

        // RECORD ADMIN ACTIVITY.
        DB::table('audit_log')->insert([
            'user_id' =>
                $request->user()->getAuthIdentifier(),

            'action' => 'BILL_CREATED',

            'description' =>
                "Generated Billing #{$billingId} "
                . "for Tenant #{$tenant->tenant_id}. "
                . "Categories: "
                . implode(', ', $selected)
                . ". Total: PHP "
                . $toMoney($totalCents),

            'created_at' => now('Asia/Manila'),
        ]);

    }, 3);

    return redirect()
        ->route('admin.billings.index')
        ->with(
            'status',
            'Monthly bill successfully generated '
            . 'with selected billing categories.'
        );
}

}
