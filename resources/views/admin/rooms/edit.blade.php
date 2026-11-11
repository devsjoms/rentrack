@extends('layouts.rentrack-room-panel')
@section('title', 'Edit Room')
@section('section-name', 'Rooms / Edit room')

@section('content')
@php $occupied = $room->tenants()->count(); @endphp
<div class="rt-page-header">
    <div>
        <p class="rt-eyebrow">PROPERTY OPERATIONS / ROOMS / EDIT</p>
        <h1>Edit Room {{ $room->room_number }}</h1>
        <p class="rt-lead">Update room details and availability without changing existing tenant assignments.</p>
    </div>
    <div class="rt-heading-actions"><a class="rt-btn rt-btn-secondary" href="{{ route('admin.rooms.index') }}">← Back to rooms</a></div>
</div>

<div class="rt-stats rt-stats-three">
    <div class="rt-stat"><span class="rt-stat-label">Building</span><span class="rt-stat-value">{{ $room->building_number }}</span></div>
    <div class="rt-stat"><span class="rt-stat-label">Assigned tenants</span><span class="rt-stat-value">{{ $occupied }}</span></div>
    <div class="rt-stat"><span class="rt-stat-label">Maximum capacity</span><span class="rt-stat-value">{{ $room->maximum_tenants }}</span></div>
</div>

<div class="rt-card">
    <div class="rt-card-header"><div><h2>Room details</h2><p>Changes take effect after saving</p></div><span class="rt-tag {{ $room->availability_status === 'Available' ? 'rt-tag-green' : 'rt-tag-gray' }}">{{ $room->availability_status }}</span></div>
    <div class="rt-card-body">
        @if($occupied > 0)<div class="rt-alert rt-alert-info" role="status">This room has {{ $occupied }} assigned tenant(s). Capacity cannot be reduced below occupancy and gender restrictions cannot be changed until the room is vacant.</div>@endif
        <form method="POST" action="{{ route('admin.rooms.update', ['room' => $room->room_id]) }}" data-confirm-title="Save room changes" data-confirm-message="Apply these changes to Room {{ $room->room_number }}?" data-confirm-button="Save changes">
            @csrf @method('PUT')
            <div class="rt-form-grid">
                <div class="rt-field"><label for="building_number">Building number *</label><input id="building_number" name="building_number" maxlength="20" value="{{ old('building_number', $room->building_number) }}" required></div>
                <div class="rt-field"><label for="room_number">Room number *</label><input id="room_number" name="room_number" maxlength="20" value="{{ old('room_number', $room->room_number) }}" required></div>
                <div class="rt-field rt-field-wide"><label for="building_address">Building address *</label><input id="building_address" name="building_address" maxlength="150" value="{{ old('building_address', $room->building_address) }}" required></div>
                <div class="rt-field"><label for="gender_allowed">Gender allowed *</label><select name="gender_allowed" id="gender_allowed" required><option value="Male" {{ old('gender_allowed', $room->gender_allowed) === 'Male' ? 'selected' : '' }}>Male</option><option value="Female" {{ old('gender_allowed', $room->gender_allowed) === 'Female' ? 'selected' : '' }}>Female</option></select><small>Occupied rooms must retain the current restriction.</small></div>
                <div class="rt-field"><label for="maximum_tenants">Maximum tenants *</label><input id="maximum_tenants" name="maximum_tenants" type="number" min="{{ max(1, $occupied) }}" max="100" value="{{ old('maximum_tenants', $room->maximum_tenants) }}" required></div>
                <div class="rt-field"><label for="rent_price">Rent price (₱) *</label><input id="rent_price" name="rent_price" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('rent_price', $room->rent_price) }}" required></div>
                <div class="rt-field"><label for="availability_status">Availability *</label><select id="availability_status" name="availability_status" required><option value="Available" {{ old('availability_status', $room->availability_status) === 'Available' ? 'selected' : '' }}>Available</option><option value="Unavailable" {{ old('availability_status', $room->availability_status) === 'Unavailable' ? 'selected' : '' }}>Unavailable</option></select><small>Unavailable blocks new request approvals; it does not remove assigned tenants.</small></div>
            </div>
            <div class="rt-form-actions"><button type="submit" class="rt-btn">Save changes</button><a class="rt-btn rt-btn-secondary" href="{{ route('admin.rooms.index') }}">Cancel</a></div>
        </form>
    </div>
</div>
@endsection
