@extends('layouts.rentrack-room-panel')
@section('title', 'Room Requests')
@section('section-name', 'Room requests / Approvals')

@section('content')
<div class="rt-page-header">
    <div>
        <p class="rt-eyebrow">PROPERTY OPERATIONS / APPROVALS</p>
        <h1>Room requests</h1>
        <p class="rt-lead">Review tenant room applications, assign eligible rooms, and view recent decisions.</p>
    </div>
    <div class="rt-heading-actions"><a class="rt-btn rt-btn-secondary" href="{{ route('admin.rooms.index') }}">← Manage rooms</a></div>
</div>

<div class="rt-stats rt-stats-two">
    <div class="rt-stat rt-stat-primary"><span class="rt-stat-label">Awaiting review</span><span class="rt-stat-value">{{ $pendingRequests->count() }}</span><div class="rt-stat-sub">Pending requests</div></div>
    <div class="rt-stat"><span class="rt-stat-label">Recent decisions</span><span class="rt-stat-value">{{ $history->count() }}</span><div class="rt-stat-sub">Up to 50 latest history records</div></div>
</div>

<section class="rt-card" aria-labelledby="pending-heading">
    <div class="rt-card-header"><div><h2 id="pending-heading">Pending applications</h2><p>Approve only after verifying tenant eligibility and room capacity</p></div><span class="rt-tag rt-tag-amber">{{ $pendingRequests->count() }} pending</span></div>
    <div class="rt-table-wrap"><table class="rt-table">
        <thead><tr><th scope="col">Request</th><th scope="col">Tenant</th><th scope="col">Requested room</th><th scope="col">Submitted</th><th scope="col">Status</th><th scope="col">Decision</th></tr></thead>
        <tbody>
            @forelse($pendingRequests as $item)
                <tr>
                    <td class="rt-strong">#{{ $item->request_id }}</td>
                    <td><span class="rt-strong">{{ $item->tenant?->tenant_name ?? 'Tenant record unavailable' }}</span><div class="rt-cell-sub">Tenant ID {{ $item->tenant_id }}</div></td>
                    <td><span class="rt-strong">{{ $item->room ? 'Room '.$item->room->room_number : 'Room unavailable' }}</span><div class="rt-cell-sub">{{ $item->room ? 'Building '.$item->room->building_number : 'Room record missing' }}</div></td>
                    <td>{{ $item->request_date ? \Illuminate\Support\Carbon::parse($item->request_date)->format('M d, Y') : '—' }}<div class="rt-cell-sub">{{ $item->request_date ? \Illuminate\Support\Carbon::parse($item->request_date)->format('h:i A') : '' }}</div></td>
                    <td><span class="rt-tag rt-tag-amber">Pending</span></td>
                    <td><div class="rt-actions">
                        @if($item->room && $item->tenant)
                            <form method="POST" action="{{ route('admin.room-requests.approve', ['roomRequest' => $item->request_id]) }}" data-confirm-title="Approve room request" data-confirm-message="Approve request #{{ $item->request_id }} and assign this room to the tenant?" data-confirm-button="Approve request">
                                @csrf @method('PATCH')<button type="submit" class="rt-btn rt-btn-small">Approve</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.room-requests.reject', ['roomRequest' => $item->request_id]) }}" data-confirm-title="Reject room request" data-confirm-message="Reject room request #{{ $item->request_id }}?" data-confirm-button="Reject request" data-confirm-danger="true">
                            @csrf @method('PATCH')<button type="submit" class="rt-btn rt-btn-danger rt-btn-small">Reject</button>
                        </form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="rt-empty"><span class="rt-empty-icon" aria-hidden="true">✓</span><strong>All caught up</strong>No pending room requests to review.</div></td></tr>
            @endforelse
        </tbody>
    </table></div>
</section>

<section class="rt-card" aria-labelledby="history-heading">
    <div class="rt-card-header"><div><h2 id="history-heading">Request history</h2><p>Last 50 approved or rejected room requests</p></div></div>
    <div class="rt-table-wrap"><table class="rt-table">
        <thead><tr><th scope="col">Request</th><th scope="col">Tenant</th><th scope="col">Room</th><th scope="col">Submitted</th><th scope="col">Decision</th></tr></thead>
        <tbody>
        @forelse($history as $item)
            <tr>
                <td class="rt-strong">#{{ $item->request_id }}</td>
                <td>{{ $item->tenant?->tenant_name ?? 'Tenant unavailable' }}</td>
                <td>{{ $item->room ? 'Room '.$item->room->room_number : 'Room unavailable' }}</td>
                <td>{{ $item->request_date ? \Illuminate\Support\Carbon::parse($item->request_date)->format('M d, Y h:i A') : '—' }}</td>
                <td><span class="rt-tag {{ $item->status === 'Approved' ? 'rt-tag-green' : 'rt-tag-red' }}">{{ $item->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="rt-empty"><strong>No decisions yet</strong>Approved and rejected requests will appear here.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
