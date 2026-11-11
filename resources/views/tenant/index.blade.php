@extends('layouts.rentrack-room-panel')
@section('title', 'Browse Rooms')
@section('section-name', 'Rooms / Browse')

@section('content')
@php
    $isActiveTenant = $tenant->status === 'Active';
    $hasAssignedRoom = $tenant->room_id !== null;
    $canSubmitRequest = $isActiveTenant && !$hasAssignedRoom && !$hasPendingRequest;
@endphp
<div class="rt-page-header">
    <div>
        <p class="rt-eyebrow">TENANT PORTAL / ROOM SELECTION</p>
        <h1>Find your next room</h1>
        <p class="rt-lead">Browse rooms that match your tenant profile. Requests are subject to administrator approval.</p>
    </div>
    <div class="rt-heading-actions"><a class="rt-btn rt-btn-secondary" href="{{ route('tenant.dashboard') }}">← Back to dashboard</a></div>
</div>

@if($hasAssignedRoom)
    <div class="rt-alert rt-alert-info" role="status"><strong>Room assigned</strong><span>You are currently assigned to room ID #{{ $tenant->room_id }}. New room requests are unavailable while you have an assignment.</span></div>
@elseif(!$isActiveTenant)
    <div class="rt-alert rt-alert-info" role="status"><strong>Account not eligible</strong><span>Only active tenant profiles can submit room requests.</span></div>
@elseif($hasPendingRequest)
    <div class="rt-alert rt-alert-info" role="status"><strong>Request in review</strong><span>You already have a pending room request. Wait for an admin decision before submitting another.</span></div>
@endif

<div class="rt-stats rt-stats-three">
    <div class="rt-stat rt-stat-primary"><span class="rt-stat-label">Matching rooms</span><span class="rt-stat-value">{{ $availableRooms->count() }}</span><div class="rt-stat-sub">Available with capacity</div></div>
    <div class="rt-stat"><span class="rt-stat-label">Your room requests</span><span class="rt-stat-value">{{ $requests->count() }}</span><div class="rt-stat-sub">All request history</div></div>
    <div class="rt-stat"><span class="rt-stat-label">Request status</span><span style="display:block;font-size:19px;font-weight:800;margin-top:17px">{{ $hasAssignedRoom ? 'Assigned' : ($hasPendingRequest ? 'Pending review' : ($isActiveTenant ? 'Eligible' : 'Inactive')) }}</span></div>
</div>

<section aria-labelledby="available-heading">
    <div class="rt-card-header" style="padding:6px 2px 18px;border:0">
        <div><h2 id="available-heading">Rooms you can explore</h2><p>Prices and remaining slots are based on current room records.</p></div>
        <span class="rt-tag rt-tag-green">{{ $availableRooms->count() }} matching</span>
    </div>
    <div class="rt-room-grid">
        @forelse($availableRooms as $room)
            @php
                $occupied = (int) $room->occupied_count;
                $capacity = (int) $room->maximum_tenants;
                $remaining = max(0, $capacity - $occupied);
            @endphp
            <article class="rt-room">
                <div class="rt-room-top"><div><div class="rt-room-number">Room {{ $room->room_number }}</div><div class="rt-room-location">Building {{ $room->building_number }} · {{ $room->building_address }}</div></div><span class="rt-tag rt-tag-green">Available</span></div>
                <div class="rt-room-price">₱{{ number_format((float) $room->rent_price, 2) }} <small>/ listed rate</small></div>
                <div class="rt-room-meta"><span>{{ $room->gender_allowed }}</span><span>{{ $remaining }} slot(s) left</span><span>{{ $occupied }}/{{ $capacity }} occupied</span></div>
                <div class="rt-progress" role="progressbar" aria-label="Room occupancy" aria-valuenow="{{ $occupied }}" aria-valuemin="0" aria-valuemax="{{ max(1,$capacity) }}"><span style="width:{{ $capacity > 0 ? min(100, round($occupied / $capacity * 100)) : 0 }}%"></span></div>
                <div class="rt-divider"></div>
                <form method="POST" action="{{ route('tenant.room-requests.store') }}" data-confirm-title="Submit room request" data-confirm-message="Send a request for Room {{ $room->room_number }}? An admin must approve before you are assigned." data-confirm-button="Send request">
                    @csrf
                    <input type="hidden" name="room_id" value="{{ $room->room_id }}">
                    <button class="rt-btn" type="submit" {{ $canSubmitRequest ? '' : 'disabled' }}>{{ $canSubmitRequest ? 'Request this room' : ($hasPendingRequest ? 'Pending request exists' : 'Request unavailable') }}</button>
                </form>
            </article>
        @empty
            <div class="rt-card" style="grid-column:1/-1;margin:0"><div class="rt-empty"><span class="rt-empty-icon" aria-hidden="true">⌂</span><strong>No matching rooms right now</strong>There are currently no available rooms matching your profile and capacity requirements.</div></div>
        @endforelse
    </div>
</section>

<section class="rt-card" aria-labelledby="my-requests-heading">
    <div class="rt-card-header"><div><h2 id="my-requests-heading">My request history</h2><p>Review the status of your previous applications</p></div></div>
    <div class="rt-table-wrap"><table class="rt-table">
        <thead><tr><th scope="col">Request ID</th><th scope="col">Room</th><th scope="col">Date submitted</th><th scope="col">Status</th></tr></thead>
        <tbody>
        @forelse($requests as $item)
            <tr>
                <td class="rt-strong">#{{ $item->request_id }}</td>
                <td>{{ $item->room ? 'Room '.$item->room->room_number : 'Room no longer available' }}</td>
                <td>{{ $item->request_date ? \Illuminate\Support\Carbon::parse($item->request_date)->format('M d, Y h:i A') : '—' }}</td>
                <td><span class="rt-tag {{ match($item->status) { 'Approved' => 'rt-tag-green', 'Pending' => 'rt-tag-amber', 'Rejected' => 'rt-tag-red', default => 'rt-tag-gray' } }}">{{ $item->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="rt-empty"><strong>No room requests yet</strong>When you apply for a room, you can track the decision here.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
<div class="rt-inline-info">ℹ Room requests do not automatically assign rooms. An administrator must approve them.</div>
@endsection
