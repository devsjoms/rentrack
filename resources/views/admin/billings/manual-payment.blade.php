
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentTrack | Record Manual Payment</title>

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
            max-width: 760px;
            margin: auto;
        }

        h1 {
            color: #0f766e;
            margin-bottom: 10px;
        }

        .card {
            background: #fff;
            padding: 26px;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .row strong {
            text-align: right;
        }

        .highlight {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 9px;
            padding: 15px;
            margin-top: 20px;
        }

        .highlight .row {
            border-bottom: none;
        }

        .amount {
            font-size: 25px;
            font-weight: bold;
            color: #0f766e;
        }

        .muted {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
        }

        .alert {
            padding: 13px;
            margin: 15px 0;
            border-radius: 7px;
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

        label {
            display: block;
            font-weight: bold;
            margin: 20px 0 8px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: 2px solid #0f766e;
            outline-offset: 1px;
        }

        .checkbox-row {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-top: 22px;
        }

        .checkbox-row input {
            width: auto;
            margin-top: 3px;
        }

        .checkbox-row label {
            margin: 0;
            font-weight: normal;
            line-height: 1.6;
        }

        button {
            background: #0f766e;
            color: white;
            border: 0;
            padding: 13px 20px;
            border-radius: 7px;
            cursor: pointer;
            margin-top: 20px;
            font-weight: bold;
            width: 100%;
        }

        button:hover {
            background: #115e59;
        }

        button:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        a {
            color: #0f766e;
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .row {
                flex-direction: column;
                gap: 5px;
            }

            .row strong {
                text-align: left;
            }

            .card {
                padding: 18px;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/rentrack-admin-legacy.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-legacy-premium.css') }}">
</head>

<body class="rt-admin-legacy rt-legacy-page">
@include('components.premium-legacy-nav')
<div class="container">

    <h1>Record Manual Payment</h1>

    <p>
        <a href="{{ route('admin.billings.index') }}">
            ← Back to Billing Management
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

    <div class="card">

        <h2>Billing #{{ $billing->billing_id }}</h2>

        <p class="muted">
            Tenant:
            {{ $billing->tenant?->tenant_name ?? 'N/A' }}

            <br>

            Room:
            {{ $billing->room?->room_number ?? 'N/A' }}
        </p>

        <div class="row">
            <span>Billing Status</span>
            <strong>{{ $billing->status }}</strong>
        </div>

        <div class="row">
            <span>Original Bill</span>
            <strong>
                ₱{{ number_format($balance['total_centavos'] / 100, 2) }}
            </strong>
        </div>

        <div class="row">
            <span>Applied Advance Credit</span>
            <strong>
                ₱{{ number_format($balance['advance_applied_centavos'] / 100, 2) }}
            </strong>
        </div>

        <div class="row">
            <span>Confirmed Payments</span>
            <strong>
                ₱{{ number_format($balance['payments_received_centavos'] / 100, 2) }}
            </strong>
        </div>

        <div class="highlight">
            <div class="row">
                <span>
                    <strong>Remaining Balance to Pay</strong>
                </span>

                <span class="amount">
                    ₱{{ number_format($remainingCents / 100, 2) }}
                </span>
            </div>
        </div>

        @if(!$balance['is_reconciled'])
            <div class="alert error">
                <strong>Billing reconciliation required.</strong>

                <ul>
                    @foreach($balance['issues'] as $issue)
                        <li>{{ $issue }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($canPay)

            <h3 style="margin-top:30px;">
                Payment Information
            </h3>

            <p class="muted">
                Record a payment that has already been
                received and verified by the administrator.
                This action will settle the entire remaining
                billing balance.
            </p>

            <form
                method="POST"
                action="{{ route('admin.billings.manual.store', $billing) }}"
                data-confirm-title="Record manual payment"
                data-confirm-message="Have you verified receipt of the full payment? This will settle the remaining billing balance."
                data-confirm-button="Record payment"
            >

                @csrf

                <label for="payment_method">
                    Payment Method
                </label>

                <select
                    id="payment_method"
                    name="payment_method"
                    required
                >
                    <option value="Cash"
                        @selected(old('payment_method', 'Cash') === 'Cash')>
                        Cash
                    </option>

                    <option value="GCash Transfer"
                        @selected(old('payment_method') === 'GCash Transfer')>
                        GCash Transfer
                    </option>

                    <option value="Bank Transfer"
                        @selected(old('payment_method') === 'Bank Transfer')>
                        Bank Transfer
                    </option>
                </select>

                <label for="payment_date">
                    Date Payment Was Received
                </label>

                <input
                    type="date"
                    id="payment_date"
                    name="payment_date"
                    value="{{ old('payment_date', now('Asia/Manila')->toDateString()) }}"
                    max="{{ now('Asia/Manila')->toDateString() }}"
                    required
                >

                <label for="external_reference">
                    External Transaction Reference
                </label>

                <input
                    type="text"
                    id="external_reference"
                    name="external_reference"
                    maxlength="120"
                    value="{{ old('external_reference') }}"
                    placeholder="GCash or bank transaction reference"
                >

                <p class="muted">
                    Required for GCash Transfer and Bank Transfer.
                    Optional for Cash.
                </p>

                <label for="notes">
                    Notes (Optional)
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    maxlength="1000"
                    placeholder="Additional payment information"
                >{{ old('notes') }}</textarea>

                <div class="checkbox-row">
                    <input
                        type="checkbox"
                        id="confirm_received"
                        name="confirm_received"
                        value="1"
                        required
                        @checked(old('confirm_received'))
                    >

                    <label for="confirm_received">
                        I confirm that the full remaining payment
                        of ₱{{ number_format($remainingCents / 100, 2) }}
                        has already been received and verified.
                    </label>
                </div>

                <button type="submit">
                    Confirm and Record Payment
                </button>

            </form>

        @else

            <div class="alert warning">
                <strong>Manual Payment Unavailable</strong>

                <p>
                    This billing cannot accept a manual payment
                    at the moment. It may already be paid, have
                    an unresolved online payment, or require
                    billing reconciliation.
                </p>
            </div>

        @endif

    </div>
</div>

<script>
    const methodSelect = document.getElementById('payment_method');
    const referenceInput = document.getElementById('external_reference');

    if (methodSelect && referenceInput) {
        function updateReferenceRequirement() {
            const isTransfer = methodSelect.value !== 'Cash';
            referenceInput.required = isTransfer;
        }

        methodSelect.addEventListener(
            'change',
            updateReferenceRequirement
        );

        updateReferenceRequirement();
    }
</script>

@include('components.confirm-dialog')
</body>
</html>
