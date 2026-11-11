
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>RentTrack | Edit Billing Charges</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px 16px;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }

        .container {
            max-width: 950px;
            margin: auto;
        }

        h1 { color: #0f766e; }

        .card {
            background: white;
            padding: 25px;
            margin-top: 20px;
            border-radius: 12px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
        }

        input:disabled {
            background: #f1f5f9;
            cursor: not-allowed;
        }

        .category {
            padding: 18px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
        }

        .category:has(input[type="checkbox"]:checked) {
            border-color: #0f766e;
            background: #f0fdfa;
        }

        .category-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .category-title input {
            width: 18px;
            height: 18px;
            accent-color: #0f766e;
        }

        .muted {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .total-card {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 10px;
            padding: 20px;
            margin-top: 22px;
        }

        .total {
            color: #0f766e;
            font-size: 28px;
            font-weight: bold;
        }

        .alert {
            padding: 14px;
            margin: 15px 0;
            border-radius: 8px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .warning {
            background: #fef3c7;
            color: #92400e;
        }

        button {
            padding: 13px 20px;
            margin-top: 20px;
            background: #0f766e;
            color: white;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
        }

        button:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 13px;
            border-bottom: 1px solid #e5e7eb;
        }

        th { background: #f1f5f9; }

        .table-wrap { overflow-x: auto; }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            background: #fef3c7;
            color: #92400e;
            font-weight: bold;
        }

        .status.paid {
            background: #dcfce7;
            color: #166534;
        }

        a { color: #0f766e; }

        @media(max-width: 760px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/rentrack-admin-legacy.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-legacy-premium.css') }}">
</head>

<body class="rt-admin-legacy rt-legacy-page">
@include('components.premium-legacy-nav')

<div class="container">

    <h1>Edit Billing Charges</h1>

    <p>
        <a href="{{ route('admin.billings.index') }}">
            Back to Billing Management
        </a>
    </p>

    @if(session('status'))
        <div class="alert success">
            {{ session('status') }}
        </div>
    @endif

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

    @php
        $canEditCharges =
            $billing->status === 'Unpaid'
            && !$billing->paymentTransactions()->exists()
            && !$billing->advanceAllocations()->exists();

        $types = ['Rent', 'Electricity', 'Water'];

        $existingTypes = $billing->items
            ->pluck('item_type')
            ->all();

        $selectedTypes = old(
            'categories',
            $existingTypes
        );

        if (!is_array($selectedTypes)) {
            $selectedTypes = $existingTypes;
        }

        $rentValue = old(
            'amounts.Rent',
            $billing->items
                ->firstWhere('item_type', 'Rent')?->amount
                ?? $billing->room?->rent_price
        );

        $electricityValue = old(
            'amounts.Electricity',
            $billing->items
                ->firstWhere('item_type', 'Electricity')?->amount
                ?? ''
        );

        $waterValue = old(
            'amounts.Water',
            $billing->items
                ->firstWhere('item_type', 'Water')?->amount
                ?? ''
        );
    @endphp

    <!-- BILLING INFORMATION -->
    <div class="card">

        <h2>Billing #{{ $billing->billing_id }}</h2>

        <p>
            <strong>Tenant:</strong>
            {{ $billing->tenant?->tenant_name ?? 'N/A' }}
        </p>

        <p>
            <strong>Room:</strong>
            {{ $billing->room?->room_number ?? 'N/A' }}
        </p>

        <p>
            <strong>Billing Month:</strong>
            {{ $billing->billing_period?->format('F Y') ?? 'N/A' }}
        </p>

        <p>
            <strong>Due Date:</strong>
            {{ $billing->due_date?->format('M d, Y') ?? 'N/A' }}
        </p>

        <p>
            <strong>Status:</strong>
            <span class="status {{ $billing->status === 'Paid' ? 'paid' : '' }}">
                {{ $billing->status }}
            </span>
        </p>

    </div>

    <!-- EDIT MULTIPLE CHARGES -->
    <div class="card">

        <h2>Add / Update Billing Charges</h2>

        <p class="muted">
            Check the charges that should be included
            in this bill. You may select one, two,
            or all three categories.
        </p>

        <div class="alert warning">
            Unchecking an existing category will remove
            that charge when you save. At least one
            category must remain selected.
        </div>

        <form
            id="edit-charges-form"
            method="POST"
            action="{{ route('admin.billings.items.bulk', $billing) }}"
        >
            @csrf
            @method('PUT')

            <div class="grid">

                <!-- RENT -->
                <div class="category">

                    <label class="category-title">
                        <input
                            type="checkbox"
                            id="category_rent"
                            name="categories[]"
                            value="Rent"
                            @checked(in_array('Rent', $selectedTypes, true))
                            @disabled(!$canEditCharges)
                        >

                        Rent
                    </label>

                    <label for="rent_amount">
                        Rent Amount (PHP)
                    </label>

                    <input
                        type="number"
                        id="rent_amount"
                        name="amounts[Rent]"
                        step="0.01"
                        min="0.01"
                        max="99999999.99"
                        value="{{ $rentValue }}"
                    >

                    <p class="muted">
                        Defaults to the existing Rent charge
                        or assigned room price.
                    </p>

                </div>

                <!-- ELECTRICITY -->
                <div class="category">

                    <label class="category-title">
                        <input
                            type="checkbox"
                            id="category_electricity"
                            name="categories[]"
                            value="Electricity"
                            @checked(in_array('Electricity', $selectedTypes, true))
                            @disabled(!$canEditCharges)
                        >

                        Electricity
                    </label>

                    <label for="electricity_amount">
                        Electricity Amount (PHP)
                    </label>

                    <input
                        type="number"
                        id="electricity_amount"
                        name="amounts[Electricity]"
                        step="0.01"
                        min="0.01"
                        max="99999999.99"
                        value="{{ $electricityValue }}"
                        placeholder="Enter amount"
                    >

                </div>

                <!-- WATER -->
                <div class="category">

                    <label class="category-title">
                        <input
                            type="checkbox"
                            id="category_water"
                            name="categories[]"
                            value="Water"
                            @checked(in_array('Water', $selectedTypes, true))
                            @disabled(!$canEditCharges)
                        >

                        Water
                    </label>

                    <label for="water_amount">
                        Water Amount (PHP)
                    </label>

                    <input
                        type="number"
                        id="water_amount"
                        name="amounts[Water]"
                        step="0.01"
                        min="0.01"
                        max="99999999.99"
                        value="{{ $waterValue }}"
                        placeholder="Enter amount"
                    >

                </div>

            </div>

            <div class="total-card">
                <h3>Updated Monthly Total</h3>

                <div
                    class="total"
                    id="total-preview"
                >
                    ₱0.00
                </div>

                <p class="muted">
                    Preview only. The backend will recalculate
                    the final amount from the saved charges.
                </p>
            </div>

            <button
                type="submit"
                id="save-charges-btn"
                @disabled(!$canEditCharges)
            >
                Save Selected Charges
            </button>

            @unless($canEditCharges)
                <div class="alert warning">
                    This billing statement cannot be modified
                    because it is paid or already has
                    payment activity or applied credit.
                </div>
            @endunless

        </form>

    </div>

    <!-- SAVED BREAKDOWN -->
    <div class="card">

        <h2>Current Billing Breakdown</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Charge Type</th>
                        <th>Amount</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($billing->items as $item)
                        <tr>
                            <td>{{ $item->item_type }}</td>

                            <td>
                                ₱{{ number_format(
                                    (float) $item->amount,
                                    2
                                ) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                No charges have been added.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3>Current Total Monthly Charges</h3>

        <div class="total">
            ₱{{ number_format(
                (float) $billing->amount,
                2
            ) }}
        </div>

        <p class="muted">
            Advance payments are separate tenant credits.
            They are not included in this charge total.
        </p>

    </div>

</div>

<script>
(() => {

    const form = document.getElementById(
        'edit-charges-form'
    );

    const saveButton = document.getElementById(
        'save-charges-btn'
    );

    const totalPreview = document.getElementById(
        'total-preview'
    );

    const canEdit = @json($canEditCharges);

    const existingTypes = @json($existingTypes);

    const fields = [
        {
            type: 'Rent',
            checkbox: document.getElementById('category_rent'),
            amount: document.getElementById('rent_amount')
        },
        {
            type: 'Electricity',
            checkbox: document.getElementById('category_electricity'),
            amount: document.getElementById('electricity_amount')
        },
        {
            type: 'Water',
            checkbox: document.getElementById('category_water'),
            amount: document.getElementById('water_amount')
        }
    ];

    const peso = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    });

    function parseCentavos(value) {

        const text = String(value ?? '').trim();

        if (!/^\d{1,8}(?:\.\d{1,2})?$/.test(text)) {
            return null;
        }

        const parts = text.split('.');

        return Number(parts[0]) * 100
            + Number((parts[1] ?? '').padEnd(2, '0'));
    }

    function updatePreview() {

        let total = 0;
        let count = 0;
        let valid = true;

        fields.forEach(field => {

            const selected = field.checkbox.checked;

            field.amount.disabled = !canEdit || !selected;
            field.amount.required = canEdit && selected;

            if (selected) {
                count++;

                const cents = parseCentavos(
                    field.amount.value
                );

                if (cents === null || cents <= 0) {
                    valid = false;
                } else {
                    total += cents;
                }
            }

        });

        totalPreview.textContent = peso.format(
            total / 100
        );

        saveButton.disabled =
            !canEdit ||
            count === 0 ||
            !valid ||
            total > 9999999999;
    }

    fields.forEach(field => {
        field.checkbox.addEventListener(
            'change',
            updatePreview
        );

        field.amount.addEventListener(
            'input',
            updatePreview
        );
    });

    form.addEventListener('submit', function(event) {

        const selectedTypes = fields
            .filter(field => field.checkbox.checked)
            .map(field => field.type);

        const removedTypes = existingTypes.filter(
            type => !selectedTypes.includes(type)
        );

        if (removedTypes.length > 0) {
            form.dataset.confirmTitle = 'Remove billing charges';
            form.dataset.confirmMessage =
                'The following existing charges will be removed: '
                + removedTypes.join(', ')
                + '. Continue?';
            form.dataset.confirmButton = 'Save changes';
            form.dataset.confirmDanger = 'true';
        } else {
            delete form.dataset.confirmTitle;
            delete form.dataset.confirmMessage;
            delete form.dataset.confirmButton;
            delete form.dataset.confirmDanger;
        }
    });

    updatePreview();

})();
</script>

@include('components.confirm-dialog')
</body>
</html>
