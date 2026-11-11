@once
<style>
    .rtb-dashboard {
        max-width: 1180px;
        margin: 24px auto;
        color: #1f2937;
        font-family: Arial, sans-serif;
    }
    .rtb-heading {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        margin-bottom: 16px;
    }
    .rtb-heading h2 { color: #0f766e; margin: 0; font-size: 24px; }
    .rtb-heading p { color: #64748b; margin: 7px 0 0; font-size: 14px; }
    .rtb-link {
        display: inline-block;
        background: #0f766e;
        color: #ffffff !important;
        padding: 11px 15px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
    }
    .rtb-link:hover { background: #115e59; }
    .rtb-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 15px;
    }
    .rtb-tile, .rtb-records, .rtb-notice {
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 12px;
        padding: 19px;
    }
    .rtb-label { color: #64748b; font-size: 13px; }
    .rtb-value { font-size: 27px; color: #0f766e; font-weight: 800; margin-top: 10px; overflow-wrap: anywhere; }
    .rtb-caption { font-size: 12px; color: #64748b; margin-top: 6px; line-height: 1.5; }
    .rtb-records { margin-top: 16px; }
    .rtb-records h3 { margin: 0 0 12px; color: #1f2937; font-size: 18px; }
    .rtb-table-wrap { overflow-x: auto; }
    .rtb-table { border-collapse: collapse; width: 100%; font-size: 14px; }
    .rtb-table th, .rtb-table td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .rtb-table th { background: #f8fafc; font-size: 12px; color: #475569; }
    .rtb-money { font-weight: 700; white-space: nowrap; }
    .rtb-remaining { color: #b45309; }
    .rtb-zero { color: #0f766e; }
    .rtb-status { font-weight: 700; font-size: 12px; }
    .rtb-notice { margin-top: 14px; background: #fff7ed; color: #92400e; }
    @media(max-width: 760px) {
        .rtb-summary { grid-template-columns: 1fr; }
        .rtb-value { font-size: 23px; }
        .rtb-table { min-width: 590px; }
    }
</style>
@endonce

<section class="rtb-dashboard" aria-label="My billing summary">
    <div class="rtb-heading">
        <div>
            <h2>My Billing Overview</h2>
            <p>See your outstanding balance, advance credit, and recent statements.</p>
        </div>
        <a class="rtb-link" href="{{ route('tenant.billings.index') }}">View Billing Statements</a>
    </div>

    @if(!$hasTenant)
        <div class="rtb-notice">Your tenant profile is not linked yet. Contact the administrator to access your billing summary.</div>
    @else
        <div class="rtb-summary">
            <div class="rtb-tile">
                <div class="rtb-label">Total Outstanding Balance</div>
                <div class="rtb-value">₱{{ number_format($outstandingCents / 100, 2) }}</div>
                <div class="rtb-caption">Amount still due across unpaid bills</div>
            </div>
            <div class="rtb-tile">
                <div class="rtb-label">Available Advance Credit</div>
                <div class="rtb-value">₱{{ number_format($availableCreditCents / 100, 2) }}</div>
                <div class="rtb-caption">Verified credit not yet applied to a bill</div>
            </div>
            <div class="rtb-tile">
                <div class="rtb-label">Bills with Outstanding Balance</div>
                <div class="rtb-value">{{ number_format($openBills) }}</div>
                <div class="rtb-caption">Billing statements with an amount remaining</div>
            </div>
        </div>

        @if($needsReview)
            <div class="rtb-notice">One or more financial records need reconciliation. Please contact the administrator before making another payment.</div>
        @endif

        <div class="rtb-records">
            <h3>Recent Billing Statements</h3>
            <div class="rtb-table-wrap">
                <table class="rtb-table">
                    <thead>
                        <tr>
                            <th>Bill</th>
                            <th>Month / Due Date</th>
                            <th>Original Total</th>
                            <th>Remaining Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBills as $bill)
                            <tr>
                                <td>#{{ $bill['id'] }}</td>
                                <td>
                                    {{ $bill['month'] }}
                                    <div class="rtb-caption">Due: {{ $bill['due_date'] }}</div>
                                </td>
                                <td class="rtb-money">₱{{ number_format($bill['total_centavos'] / 100, 2) }}</td>
                                <td class="rtb-money {{ $bill['remaining_centavos'] > 0 ? 'rtb-remaining' : 'rtb-zero' }}">
                                    ₱{{ number_format($bill['remaining_centavos'] / 100, 2) }}
                                </td>
                                <td>
                                    <span class="rtb-status">{{ $bill['status'] }}</span>
                                    @unless($bill['is_reconciled'])
                                        <div class="rtb-caption" style="color:#b45309">Review needed</div>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="color:#64748b;padding:20px 10px">No billing statements yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="rtb-caption">Advance credit is not deducted from a bill until the admin applies it. The full history is available in My Billing Statements.</p>
        </div>
    @endif
</section>
