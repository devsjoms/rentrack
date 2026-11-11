<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentTrack | Apply Advance Credit</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 30px 16px; background: #f3f4f6; color: #1f2937; font-family: Arial, sans-serif; }
        .container { max-width: 760px; margin: auto; }
        h1 { color: #0f766e; }
        .card { background: white; padding: 26px; border-radius: 12px; margin-top: 18px; }
        .row { display: flex; justify-content: space-between; gap: 20px; padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
        .row strong { text-align: right; }
        .highlight { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 9px; padding: 15px; }
        .muted { font-size: 14px; color: #64748b; line-height: 1.6; }
        .alert { padding: 13px; margin: 15px 0; border-radius: 7px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        .warning { background: #fef3c7; color: #92400e; }
        label { display: block; font-weight: bold; margin: 20px 0 8px; }
        input[type=number] { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 7px; }
        button { background: #0f766e; color: white; border: 0; padding: 13px 20px; border-radius: 7px; cursor: pointer; margin-top: 20px; font-weight: bold; }
        button:disabled { background: #94a3b8; cursor: not-allowed; }
        a { color: #0f766e; }
    </style>
    <link rel="stylesheet" href="{{ asset('css/rentrack-admin-legacy.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-legacy-premium.css') }}">
</head>
<body class="rt-admin-legacy rt-legacy-page">
@include('components.premium-legacy-nav')
<div class="container">
    <h1>Apply Advance Credit</h1>
    <p><a href="{{ route('admin.billings.index') }}">Back to Billing Management</a></p>

    @if(session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card">
        <h2>Billing #{{ $billing->billing_id }}</h2>
        <p class="muted">Tenant: {{ $billing->tenant?->tenant_name ?? 'N/A' }} | Room: {{ $billing->room?->room_number ?? 'N/A' }}</p>
        <div class="row"><span>Status</span><strong>{{ $billing->status }}</strong></div>
        <div class="row"><span>Original Bill</span><strong>₱{{ number_format($balance['total_centavos'] / 100, 2) }}</strong></div>
        <div class="row"><span>Previously Applied Credit</span><strong>₱{{ number_format($balance['advance_applied_centavos'] / 100, 2) }}</strong></div>
        <div class="row"><span>Confirmed Other Payments</span><strong>₱{{ number_format($balance['payments_received_centavos'] / 100, 2) }}</strong></div>
        <div class="row"><span>Available Tenant Advance Credit</span><strong>₱{{ number_format($availableCents / 100, 2) }}</strong></div>
        <div class="highlight" style="margin-top:18px;">
            <div class="row"><span>Remaining Bill Balance</span><strong>₱{{ number_format($remainingCents / 100, 2) }}</strong></div>
            <div class="row"><span>Maximum Credit You Can Apply</span><strong>₱{{ number_format($maxCents / 100, 2) }}</strong></div>
        </div>

        @if(!$balance['is_reconciled'])
            <div class="alert error">Reconciliation required: {{ implode(' ', $balance['issues']) }}</div>
        @endif

        <form method="POST" action="{{ route('admin.billings.advance-credit.store', $billing) }}"
              data-confirm-title="Apply advance credit"
              data-confirm-message="Apply this existing credit to the bill? This does not record new cash received."
              data-confirm-button="Apply credit">
            @csrf
            <label for="amount">Amount of Credit to Apply (PHP)</label>
            <input id="amount" name="amount" type="number" min="0.01" step="0.01"
                   max="{{ \App\Services\Money::fromCents($maxCents) }}"
                   value="{{ old('amount', \App\Services\Money::fromCents($maxCents)) }}"
                   required @disabled(!$canApply)>
            <p class="muted">You can apply less than the maximum. If the bill is not fully covered, it stays Unpaid, and only the remaining balance needs settlement.</p>
            <button type="submit" @disabled(!$canApply)>Apply Advance Credit</button>
        </form>

        @unless($canApply)
            <div class="alert warning">Credit application is unavailable. This bill may already be paid, lack available credit, have a reconciliation issue, or have an unresolved payment attempt.</div>
        @endunless
    </div>
</div>
@include('components.confirm-dialog')
</body>
</html>
