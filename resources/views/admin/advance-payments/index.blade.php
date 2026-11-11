
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>RentTrack | Advance Payments</title>

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
            max-width: 1100px;
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
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        select,
        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-family: inherit;
        }

        button {
            background: #0f766e;
            color: white;
            border: none;
            padding: 13px 20px;
            border-radius: 7px;
            cursor: pointer;
            margin-top: 20px;
        }

        button:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        .alert {
            padding: 14px;
            border-radius: 7px;
            margin: 15px 0;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .notice {
            background: #fef3c7;
            color: #92400e;
        }

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
        }

        th {
            background: #f1f5f9;
        }

        .credit {
            color: #0f766e;
            font-weight: bold;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 20px;
        }

        a {
            color: #0f766e;
        }

        @media (max-width: 700px) {
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

    <h1>Advance Payments</h1>

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
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- RECORD ADVANCE PAYMENT -->
    <div class="card">

        <h2>Record Advance Payment</h2>

        <div class="alert notice">
            Only record money that has actually been
            received and verified.

            Advance Payments become Tenant credits.
            They are not additional rental charges.
        </div>

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
                            Select Tenant
                        </option>

                        @foreach($tenants as $tenant)
                            <option
                                value="{{ $tenant->tenant_id }}"
                                @selected(
                                    (string) old('tenant_id') ===
                                    (string) $tenant->tenant_id
                                )
                            >
                                {{ $tenant->tenant_name }}
                                (ID: {{ $tenant->tenant_id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="amount">
                        Advance Amount (PHP)
                    </label>

                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        step="0.01"
                        min="0.01"
                        max="99999999.99"
                        value="{{ old('amount') }}"
                        required
                    >
                </div>

                <div>
                    <label for="payment_method">
                        Payment Method
                    </label>

                    <select
                        id="payment_method"
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

                <div>
                    <label for="payment_date">
                        Payment Date
                    </label>

                    <input
                        type="date"
                        id="payment_date"
                        name="payment_date"
                        max="{{ now('Asia/Manila')->toDateString() }}"
                        value="{{ old(
                            'payment_date',
                            now('Asia/Manila')->toDateString()
                        ) }}"
                        required
                    >
                </div>

                <div>
                    <label for="external_reference">
                        Receipt / Transaction Reference
                    </label>

                    <input
                        type="text"
                        id="external_reference"
                        name="external_reference"
                        maxlength="120"
                        value="{{ old('external_reference') }}"
                        placeholder="Required for transfers"
                    >
                </div>

                <div>
                    <label for="notes">
                        Admin Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        maxlength="1000"
                        rows="2"
                    >{{ old('notes') }}</textarea>
                </div>

            </div>

            <p>
                <label>
                    <input
                        type="checkbox"
                        name="confirm_received"
                        value="1"
                        style="width:auto;"
                        required
                    >
                    I confirm that the advance payment
                    has already been received.
                </label>
            </p>

            <button
                type="submit"
                @disabled($tenants->isEmpty())
            >
                Record Advance Payment
            </button>

        </form>
    </div>

    <!-- ADVANCE PAYMENT HISTORY -->
    <div class="card">

        <h2>Advance Payment History</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tenant</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Amount</th>
                        <th>Applied</th>
                        <th>Available</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($advances as $advance)
                        <tr>
                            <td>
                                #{{ $advance->advance_payment_id }}
                            </td>

                            <td>
                                {{ $advance->tenant_name }}
                            </td>

                            <td>
                                {{ $advance->payment_date }}
                            </td>

                            <td>
                                {{ $advance->payment_method ?? 'N/A' }}
                            </td>

                            <td>
                                {{ $advance->external_reference
                                    ?: $advance->reference_number }}
                            </td>

                            <td>
                                ₱{{ number_format(
                                    (float) $advance->amount, 2
                                ) }}
                            </td>

                            <td>
                                ₱{{ number_format(
                                    (float) $advance->applied_amount, 2
                                ) }}
                            </td>

                            <td class="credit">
                                ₱{{ number_format(
                                    (float) $advance->available_amount, 2
                                ) }}
                            </td>

                            <td>
                                {{ $advance->status }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="9"
                                style="text-align:center;padding:25px;"
                            >
                                No advance payment records yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">

            <div>
                @if($advances->previousPageUrl())
                    <a href="{{ $advances->previousPageUrl() }}">
                        Previous
                    </a>
                @endif
            </div>

            <div>
                Page {{ $advances->currentPage() }}
                of {{ $advances->lastPage() }}
            </div>

            <div>
                @if($advances->nextPageUrl())
                    <a href="{{ $advances->nextPageUrl() }}">
                        Next
                    </a>
                @endif
            </div>

        </div>

    </div>

</div>

@include('components.confirm-dialog')
</body>
</html>
