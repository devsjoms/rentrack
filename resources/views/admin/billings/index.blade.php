
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>RentTrack | Billing Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 16px;
            background: #f3f4f6;
            color: #1f2937;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1150px;
            margin: auto;
        }

        h1 {
            color: #0f766e;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin: 20px 0;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 14px;
        }

        select,
        input:not([type="checkbox"]),
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: white;
            font-family: inherit;
        }

        input:disabled {
            background: #f1f5f9;
            cursor: not-allowed;
        }

        button {
            background: #0f766e;
            color: white;
            border: none;
            padding: 13px 22px;
            border-radius: 7px;
            margin-top: 20px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover:not(:disabled) {
            background: #115e59;
        }

        button:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        .alert {
            padding: 13px;
            margin: 15px 0;
            border-radius: 7px;
        }

        .success {
            color: #166534;
            background: #dcfce7;
        }

        .error {
            color: #991b1b;
            background: #fee2e2;
        }

        .warning {
            color: #92400e;
            background: #fef3c7;
        }

        .description {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .section-divider {
            margin: 30px 0;
            border: 0;
            border-top: 1px solid #e5e7eb;
        }

        /* BILLING CATEGORIES */

        .billing-categories {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .category-card {
            padding: 18px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: white;
        }

        .category-card:has(input[type="checkbox"]:checked) {
            border-color: #0f766e;
            background: #f0fdfa;
        }

        .category-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            cursor: pointer;
        }

        .category-title input {
            width: 18px;
            height: 18px;
            accent-color: #0f766e;
        }

        .category-title span {
            font-weight: bold;
            font-size: 16px;
        }

        .category-description {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }

        .rent-amount {
            font-size: 21px;
            font-weight: bold;
            color: #0f766e;
            margin-top: 13px;
        }

        /* BILLING TOTAL */

        .total-card {
            margin-top: 25px;
            padding: 22px;
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 10px;
        }

        .total-card h3 {
            margin-top: 0;
        }

        .total-amount {
            font-size: 32px;
            font-weight: bold;
            color: #0f766e;
            margin-top: 10px;
        }

        /* TABLE */

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        th {
            background: #f1f5f9;
            white-space: nowrap;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
        }

        .status-unpaid {
            background: #fef3c7;
            color: #92400e;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        /* REMAINING BALANCE */

        .balance-amount {
            font-weight: bold;
            font-size: 15px;
            white-space: nowrap;
        }

        .balance-unpaid {
            color: #b45309;
        }

        .balance-paid {
            color: #0f766e;
        }

        .balance-error {
            color: #dc2626;
            font-size: 12px;
            margin-top: 6px;
            font-weight: bold;
        }

        /* ACTION BUTTONS */

        .actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            min-width: 230px;
        }

        .action-btn {
            display: inline-block;
            padding: 9px 13px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-edit {
            background: #2563eb;
            color: white;
        }

        .btn-payment {
            background: #0f766e;
            color: white;
        }

        .btn-credit {
            background: #7c3aed;
            color: white;
        }

        .btn-edit:hover {
            background: #1d4ed8;
        }

        .btn-payment:hover {
            background: #115e59;
        }

        .btn-credit:hover {
            background: #6d28d9;
        }

        .paid-label {
            color: #166534;
            font-size: 13px;
            font-weight: bold;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            gap: 15px;
        }

        a {
            color: #0f766e;
        }

        @media (max-width: 950px) {
            .billing-categories {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 18px;
            }

            .total-amount {
                font-size: 26px;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/rentrack-admin-legacy.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-legacy-premium.css') }}">
</head>

<body class="rt-admin-legacy rt-legacy-page">
@include('components.premium-legacy-nav')

<div class="container">

    <!-- ====================================== -->
    <!-- PAGE HEADER -->
    <!-- ====================================== -->

    <h1>Billing Management</h1>

    <p>
        <a href="{{ route('admin.dashboard') }}">
            Back to Admin Dashboard
        </a>
    </p>

    <p>
        <a href="{{ route('admin.advance-payments.index') }}">
            View / Record Advance Payments
        </a>
    </p>

    <!-- SUCCESS MESSAGE -->
    @if(session('status'))
        <div class="alert success">
            {{ session('status') }}
        </div>
    @endif

    <!-- VALIDATION ERRORS -->
    @if($errors->any())
        <div class="alert error">
            <strong>Please correct the following:</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- ====================================== -->
    <!-- GENERATE MONTHLY BILL -->
    <!-- ====================================== -->

    <div class="card">

        <h2>Generate Monthly Bill</h2>

        <p class="description">
            Select a tenant and one or more billing
            categories. Rent uses the assigned room's
            rental price, while Electricity and Water
            can be entered separately.
        </p>

        <form
            id="generate-bill-form"
            method="POST"
            action="{{ route('admin.billings.store') }}"
        >
            @csrf

            <div class="grid">

                <!-- TENANT -->
                <div>
                    <label for="tenant_id">
                        Select Tenant
                    </label>

                    <select
                        id="tenant_id"
                        name="tenant_id"
                        required
                    >
                        <option value="">
                            Select an assigned tenant
                        </option>

                        @foreach($eligibleTenants as $tenant)

                            <option
                                value="{{ $tenant->tenant_id }}"
                                data-rent="{{ $tenant->rent_price }}"
                                @selected(
                                    (string) old('tenant_id') ===
                                    (string) $tenant->tenant_id
                                )
                            >
                                {{ $tenant->tenant_name }}
                                — Room {{ $tenant->room_number }}
                                — ₱{{ number_format(
                                    (float) $tenant->rent_price,
                                    2
                                ) }}
                            </option>

                        @endforeach

                    </select>
                </div>

                <!-- BILLING MONTH -->
                <div>
                    <label for="billing_period">
                        Billing Month
                    </label>

                    <input
                        id="billing_period"
                        type="month"
                        name="billing_period"
                        value="{{ old(
                            'billing_period',
                            now('Asia/Manila')->format('Y-m')
                        ) }}"
                        required
                    >
                </div>

                <!-- DUE DATE -->
                <div>
                    <label for="due_date">
                        Payment Due Date
                    </label>

                    <input
                        id="due_date"
                        type="date"
                        name="due_date"
                        value="{{ old(
                            'due_date',
                            now('Asia/Manila')
                                ->endOfMonth()
                                ->format('Y-m-d')
                        ) }}"
                        required
                    >
                </div>

            </div>

            @if($eligibleTenants->isEmpty())
                <div class="alert warning">
                    No active tenants with assigned rooms
                    are currently available for billing.
                </div>
            @endif

            <hr class="section-divider">

            <!-- BILLING CATEGORY SELECTION -->

            <h3>Select Billing Categories</h3>

            <p class="description">
                Select Rent, Electricity, Water, or any
                combination. At least one is required.
            </p>

            @php
                $selectedCategories = old(
                    'categories',
                    ['Rent']
                );

                if (!is_array($selectedCategories)) {
                    $selectedCategories = ['Rent'];
                }
            @endphp

            <div class="billing-categories">

                <!-- RENT -->
                <div class="category-card">

                    <label class="category-title">

                        <input
                            type="checkbox"
                            id="category_rent"
                            name="categories[]"
                            value="Rent"
                            @checked(
                                in_array(
                                    'Rent',
                                    $selectedCategories,
                                    true
                                )
                            )
                        >

                        <span>Rent</span>
                    </label>

                    <p class="category-description">
                        Monthly rental price of the
                        tenant's assigned room.
                    </p>

                    <div
                        class="rent-amount"
                        id="rent-preview"
                    >
                        ₱0.00
                    </div>

                    <p class="category-description">
                        Automatically retrieved from the
                        selected tenant's room.
                    </p>

                </div>

                <!-- ELECTRICITY -->
                <div class="category-card">

                    <label class="category-title">

                        <input
                            type="checkbox"
                            id="category_electricity"
                            name="categories[]"
                            value="Electricity"
                            @checked(
                                in_array(
                                    'Electricity',
                                    $selectedCategories,
                                    true
                                )
                            )
                        >

                        <span>Electricity</span>
                    </label>

                    <label for="electricity_amount">
                        Electricity Amount (PHP)
                    </label>

                    <input
                        type="number"
                        id="electricity_amount"
                        name="electricity_amount"
                        min="0.01"
                        max="99999999.99"
                        step="0.01"
                        placeholder="Enter electricity amount"
                        value="{{ old('electricity_amount') }}"
                    >

                    <p class="category-description">
                        Included only when Electricity
                        is selected.
                    </p>

                </div>

                <!-- WATER -->
                <div class="category-card">

                    <label class="category-title">

                        <input
                            type="checkbox"
                            id="category_water"
                            name="categories[]"
                            value="Water"
                            @checked(
                                in_array(
                                    'Water',
                                    $selectedCategories,
                                    true
                                )
                            )
                        >

                        <span>Water</span>
                    </label>

                    <label for="water_amount">
                        Water Amount (PHP)
                    </label>

                    <input
                        type="number"
                        id="water_amount"
                        name="water_amount"
                        min="0.01"
                        max="99999999.99"
                        step="0.01"
                        placeholder="Enter water amount"
                        value="{{ old('water_amount') }}"
                    >

                    <p class="category-description">
                        Included only when Water
                        is selected.
                    </p>

                </div>

            </div>

            <div
                id="rent-warning"
                class="alert warning"
                hidden
            >
                Rent is not selected. This bill will
                contain only the selected utility charges.
            </div>

            <!-- AUTOMATIC BILLING TOTAL -->
            <div class="total-card">

                <h3>Total Monthly Charges</h3>

                <div
                    class="total-amount"
                    id="billing-total-preview"
                >
                    ₱0.00
                </div>

                <p class="description">
                    The total automatically updates based
                    on selected categories. The server
                    recalculates the final amount.
                </p>

            </div>

            <button
                type="submit"
                id="generate-bill-button"
                @disabled($eligibleTenants->isEmpty())
            >
                Generate Monthly Bill
            </button>

        </form>

    </div>

    <!-- ====================================== -->
    <!-- RECORD ADVANCE PAYMENT -->
    <!-- ====================================== -->

    <div class="card">

        <h2>Record Advance Payment</h2>

        <p class="description">
            Record verified payments received in advance.
            The amount becomes tenant credit and does not
            increase monthly billing charges.
        </p>

        <form
            method="POST"
            action="{{ route('admin.advance-payments.store') }}"
            data-confirm-title="Record advance payment"
            data-confirm-message="Confirm that this advance payment has actually been received."
            data-confirm-button="Record payment"
        >
            @csrf

            <input
                type="hidden"
                name="submission_key"
                value="{{ old(
                    'submission_key',
                    (string) \Illuminate\Support\Str::uuid()
                ) }}"
            >

            <div class="grid">

                <!-- TENANT -->
                <div>
                    <label for="advance_tenant_id">
                        Select Tenant
                    </label>

                    <select
                        id="advance_tenant_id"
                        name="tenant_id"
                        required
                    >
                        <option value="">
                            Select Tenant
                        </option>

                        @foreach($eligibleTenants as $tenant)

                            <option
                                value="{{ $tenant->tenant_id }}"
                                @selected(
                                    (string) old('tenant_id') ===
                                    (string) $tenant->tenant_id
                                )
                            >
                                {{ $tenant->tenant_name }}
                                — Room {{ $tenant->room_number }}
                            </option>

                        @endforeach

                    </select>
                </div>

                <!-- ADVANCE AMOUNT -->
                <div>
                    <label for="advance_amount">
                        Advance Amount (PHP)
                    </label>

                    <input
                        type="number"
                        id="advance_amount"
                        name="amount"
                        min="0.01"
                        max="99999999.99"
                        step="0.01"
                        value="{{ old('amount') }}"
                        placeholder="Enter advance amount"
                        required
                    >
                </div>

                <!-- PAYMENT METHOD -->
                <div>
                    <label for="advance_method">
                        Payment Method
                    </label>

                    <select
                        id="advance_method"
                        name="payment_method"
                        required
                    >
                        <option value="">
                            Select Method
                        </option>

                        @foreach([
                            'Cash',
                            'GCash Transfer',
                            'Bank Transfer'
                        ] as $method)

                            <option
                                value="{{ $method }}"
                                @selected(
                                    old('payment_method') === $method
                                )
                            >
                                {{ $method }}
                            </option>

                        @endforeach

                    </select>
                </div>

                <!-- PAYMENT DATE -->
                <div>
                    <label for="advance_date">
                        Payment Date
                    </label>

                    <input
                        type="date"
                        id="advance_date"
                        name="payment_date"
                        max="{{ now('Asia/Manila')->toDateString() }}"
                        value="{{ old(
                            'payment_date',
                            now('Asia/Manila')->toDateString()
                        ) }}"
                        required
                    >
                </div>

                <!-- TRANSACTION REFERENCE -->
                <div>
                    <label for="advance_reference">
                        Receipt / Transaction Reference
                    </label>

                    <input
                        type="text"
                        id="advance_reference"
                        name="external_reference"
                        maxlength="120"
                        value="{{ old('external_reference') }}"
                        placeholder="Required for transfers"
                    >
                </div>

                <!-- ADMIN NOTES -->
                <div>
                    <label for="advance_notes">
                        Admin Notes
                    </label>

                    <input
                        type="text"
                        id="advance_notes"
                        name="notes"
                        maxlength="1000"
                        value="{{ old('notes') }}"
                        placeholder="Optional notes"
                    >
                </div>

            </div>

            <p style="margin-top:20px;">
                <label>
                    <input
                        type="checkbox"
                        name="confirm_received"
                        value="1"
                        style="width:auto;"
                        required
                    >

                    I confirm that the advance payment
                    has actually been received.
                </label>
            </p>

            <button
                type="submit"
                @disabled($eligibleTenants->isEmpty())
            >
                Record Advance Payment
            </button>

        </form>

        <p class="description">
            <a href="{{ route('admin.advance-payments.index') }}">
                View Advance Payment History
            </a>
        </p>

    </div>

    <!-- ====================================== -->
    <!-- BILLING RECORDS -->
    <!-- ====================================== -->

    <div class="card">

        <h2>Rental Billing Records</h2>

        <p class="description">
            View monthly bill totals, outstanding balances,
            payment status, and available billing actions.
        </p>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>Tenant</th>
                        <th>Room</th>
                        <th>Billing Month</th>
                        <th>Total Charges</th>
                        <th>Remaining Balance</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @php
                        /*
                         * Use the precomputed balance_summary
                         * from BillingController@index when present.
                         *
                         * Otherwise, calculate it using the
                         * existing BillingBalanceService.
                         */
                        $balanceService = app(
                            \App\Services\BillingBalanceService::class
                        );
                    @endphp

                    @forelse($billings as $billing)

                        @php
                            $balance = $billing->balance_summary
                                ?? $balanceService->calculate(
                                    (int) $billing->billing_id
                                );

                            $remainingCentavos =
                                $balance['remaining_centavos'];

                            $isReconciled =
                                $balance['is_reconciled'];
                        @endphp

                        <tr>

                            <!-- BILL ID -->
                            <td>
                                #{{ $billing->billing_id }}
                            </td>

                            <!-- TENANT -->
                            <td>
                                {{ $billing->tenant?->tenant_name ?? 'N/A' }}
                            </td>

                            <!-- ROOM -->
                            <td>
                                {{ $billing->room?->room_number ?? 'N/A' }}
                            </td>

                            <!-- BILLING MONTH -->
                            <td>
                                {{ $billing->billing_period?->format('M Y') ?? 'Legacy record' }}
                            </td>

                            <!-- TOTAL CHARGES -->
                            <td>
                                ₱{{ number_format(
                                    (float) $billing->amount,
                                    2
                                ) }}
                            </td>

                            <!-- REMAINING BALANCE -->
                            <td>

                                <span
                                    class="balance-amount
                                        {{ $remainingCentavos > 0
                                            ? 'balance-unpaid'
                                            : 'balance-paid' }}"
                                >
                                    ₱{{ number_format(
                                        $remainingCentavos / 100,
                                        2
                                    ) }}
                                </span>

                                @unless($isReconciled)

                                    <div class="balance-error">
                                        Reconciliation Required
                                    </div>

                                @endunless

                            </td>

                            <!-- DUE DATE -->
                            <td>
                                {{ $billing->due_date?->format('M d, Y') ?? 'N/A' }}
                            </td>

                            <!-- BILLING STATUS -->
                            <td>

                                <span
                                    class="status
                                        {{ $billing->status === 'Paid'
                                            ? 'status-paid'
                                            : 'status-unpaid' }}"
                                >
                                    {{ $billing->status }}
                                </span>

                            </td>

                            <!-- ACTIONS -->
                            <td>

                                <div class="actions">

                                    <!-- EDIT CHARGES -->
                                    <a
                                        href="{{ route(
                                            'admin.billings.edit',
                                            $billing
                                        ) }}"
                                        class="action-btn btn-edit"
                                    >
                                        Edit Charges
                                    </a>

                                    @if(
                                        $billing->status === 'Unpaid'
                                        && $isReconciled
                                        && $remainingCentavos > 0
                                    )

                                        <!-- MANUAL PAYMENT -->
                                        <a
                                            href="{{ route(
                                                'admin.billings.manual.show',
                                                $billing
                                            ) }}"
                                            class="action-btn btn-payment"
                                        >
                                            Record Manual Payment
                                        </a>

                                        <!-- APPLY ADVANCE CREDIT -->
                                        <a
                                            href="{{ route(
                                                'admin.billings.advance-credit.show',
                                                $billing
                                            ) }}"
                                            class="action-btn btn-credit"
                                        >
                                            Apply Advance Credit
                                        </a>

                                    @elseif(
                                        $billing->status === 'Paid'
                                        && $isReconciled
                                    )

                                        <span class="paid-label">
                                            Fully Settled
                                        </span>

                                    @else

                                        <span class="balance-error">
                                            Review Billing
                                        </span>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="9"
                                style="
                                    text-align:center;
                                    padding:25px;
                                "
                            >
                                No billing records generated yet.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <!-- PAGINATION -->
        <div class="pagination">

            <div>
                @if($billings->previousPageUrl())

                    <a href="{{ $billings->previousPageUrl() }}">
                        Previous
                    </a>

                @endif
            </div>

            <div>
                Page {{ $billings->currentPage() }}
                of {{ $billings->lastPage() }}
            </div>

            <div>
                @if($billings->nextPageUrl())

                    <a href="{{ $billings->nextPageUrl() }}">
                        Next
                    </a>

                @endif
            </div>

        </div>

    </div>

</div>

<!-- ====================================== -->
<!-- AUTOMATIC BILLING TOTAL JAVASCRIPT -->
<!-- ====================================== -->

<script>
(() => {

    const form = document.getElementById(
        'generate-bill-form'
    );

    if (!form) return;

    const tenantSelect = document.getElementById(
        'tenant_id'
    );

    const rentCheckbox = document.getElementById(
        'category_rent'
    );

    const electricityCheckbox = document.getElementById(
        'category_electricity'
    );

    const waterCheckbox = document.getElementById(
        'category_water'
    );

    const electricityInput = document.getElementById(
        'electricity_amount'
    );

    const waterInput = document.getElementById(
        'water_amount'
    );

    const rentPreview = document.getElementById(
        'rent-preview'
    );

    const totalPreview = document.getElementById(
        'billing-total-preview'
    );

    const generateButton = document.getElementById(
        'generate-bill-button'
    );

    const rentWarning = document.getElementById(
        'rent-warning'
    );

    const peso = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    });

    /*
     * Money calculations use integer centavos.
     */
    function parseCentavos(value) {

        const text = String(value ?? '').trim();

        if (!/^\d{1,8}(?:\.\d{1,2})?$/.test(text)) {
            return null;
        }

        const parts = text.split('.');

        const whole = Number(parts[0]);

        const fraction = Number(
            (parts[1] ?? '').padEnd(2, '0')
        );

        return (whole * 100) + fraction;
    }

    function updateBillingTotal() {

        const selectedTenant =
            tenantSelect.selectedOptions[0];

        const tenantId = tenantSelect.value;

        const roomRent = parseCentavos(
            selectedTenant?.dataset.rent ?? ''
        );

        const rentSelected =
            rentCheckbox.checked;

        const electricitySelected =
            electricityCheckbox.checked;

        const waterSelected =
            waterCheckbox.checked;

        // Enable only selected utility inputs.
        electricityInput.disabled =
            !electricitySelected;

        electricityInput.required =
            electricitySelected;

        waterInput.disabled =
            !waterSelected;

        waterInput.required =
            waterSelected;

        // Display the assigned room rent.
        rentPreview.textContent = peso.format(
            (roomRent ?? 0) / 100
        );

        // Warn if Rent is excluded.
        rentWarning.hidden = rentSelected;

        const hasSelectedCategory =
            rentSelected ||
            electricitySelected ||
            waterSelected;

        let totalCentavos = 0;
        let valid = true;

        // RENT
        if (rentSelected) {

            if (
                roomRent === null ||
                roomRent <= 0
            ) {
                valid = false;
            } else {
                totalCentavos += roomRent;
            }
        }

        // ELECTRICITY
        if (electricitySelected) {

            const amount = parseCentavos(
                electricityInput.value
            );

            if (
                amount === null ||
                amount <= 0
            ) {
                valid = false;
            } else {
                totalCentavos += amount;
            }
        }

        // WATER
        if (waterSelected) {

            const amount = parseCentavos(
                waterInput.value
            );

            if (
                amount === null ||
                amount <= 0
            ) {
                valid = false;
            } else {
                totalCentavos += amount;
            }
        }

        // Show live preview.
        totalPreview.textContent = peso.format(
            totalCentavos / 100
        );

        // Allow submission only when valid.
        generateButton.disabled =
            !tenantId ||
            !hasSelectedCategory ||
            !valid ||
            totalCentavos <= 0 ||
            totalCentavos > 9999999999;
    }

    [
        tenantSelect,
        rentCheckbox,
        electricityCheckbox,
        waterCheckbox,
        electricityInput,
        waterInput
    ].forEach(element => {

        element.addEventListener(
            'change',
            updateBillingTotal
        );

        element.addEventListener(
            'input',
            updateBillingTotal
        );

    });

    // Initialize total on page load.
    updateBillingTotal();

})();
</script>

@include('components.confirm-dialog')
</body>
</html>
