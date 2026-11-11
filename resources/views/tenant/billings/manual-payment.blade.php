
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>RentTrack | Manual Payment</title>
    <link rel="stylesheet" href="{{ asset('css/rentrack-legacy-premium.css') }}">
</head>
<body class="rt-legacy-page">
@include('components.premium-legacy-nav')

    <h1>Confirm Manual Payment</h1>

    <p>
        <a href="{{ route('admin.billings.index') }}">
            Back to Billing Management
        </a>
    </p>

    @if($errors->any())
        <div style="background:#fee2e2;padding:15px;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <h3>Bill #{{ $billing->billing_id }}</h3>

    <p>Tenant: {{ $billing->tenant?->tenant_name }}</p>

    <p>
        Amount:
        ₱{{ number_format((float) $billing->amount, 2) }}
    </p>

    <p>Status: {{ $billing->status }}</p>

    @if($billing->status === 'Unpaid')

        <form
            method="POST"
            action="{{ route('admin.billings.manual.store', $billing) }}"
            data-confirm-title="Confirm manual payment"
            data-confirm-message="Confirm that the full payment has actually been received?"
            data-confirm-button="Record payment"
        >
            @csrf

            <p>
                <label>Payment Method</label>
                <select name="payment_method" required>
                    <option value="">Select Method</option>
                    @foreach(['Cash', 'GCash Transfer', 'Bank Transfer'] as $method)
                        <option value="{{ $method }}" @selected(old('payment_method') === $method)>
                            {{ $method }}
                        </option>
                    @endforeach
                </select>
            </p>

            <p>
                <label>Actual Payment Date</label>
                <input
                    type="date"
                    name="payment_date"
                    value="{{ old('payment_date', now('Asia/Manila')->toDateString()) }}"
                    required
                >
            </p>

            <p>
                <label>Receipt / Transaction Reference</label>
                <input
                    name="external_reference"
                    maxlength="120"
                    value="{{ old('external_reference') }}"
                >
            </p>

            <p>
                <label>Admin Notes</label>
                <textarea name="notes" maxlength="1000">{{ old('notes') }}</textarea>
            </p>

            <button type="submit">
                Confirm Payment & Mark as Paid
            </button>
        </form>

    @else
        <p>This bill has already been processed.</p>
    @endif

@include('components.confirm-dialog')
</body>
</html>
