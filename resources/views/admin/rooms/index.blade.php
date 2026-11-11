@extends('layouts.rentrack-room-panel')
@section('title', 'Manage Rooms')
@section('section-name', 'Rooms / Overview')

@section('content')
@php
    $roomCount = $rooms->count();
    $activeCount = $rooms->where('availability_status', 'Available')->count();
    $assignedCount = $rooms->sum(fn ($room) => (int) $room->occupied_count);
    $vacantSlots = $rooms->sum(fn ($room) => max(0, (int) $room->maximum_tenants - (int) $room->occupied_count));
@endphp
<div class="rt-page-header">
    <div>
        <p class="rt-eyebrow">PROPERTY OPERATIONS / ROOMS</p>
        <h1>Room management</h1>
        <p class="rt-lead">Manage room inventory, update rental details, and monitor occupancy from one place.</p>
    </div>
    <div class="rt-heading-actions">
        <a class="rt-btn rt-btn-secondary" href="{{ route('admin.room-requests.index') }}">View room requests</a>
        <a class="rt-btn" href="#create-room">+ Add room</a>
    </div>
</div>

<div class="rt-stats">
    <div class="rt-stat rt-stat-primary"><span class="rt-stat-label">Current rooms</span><span class="rt-stat-value">{{ $roomCount }}</span><div class="rt-stat-sub">Excludes archived</div></div>
    <div class="rt-stat"><span class="rt-stat-label">Available rooms</span><span class="rt-stat-value">{{ $activeCount }}</span><div class="rt-stat-sub">Admin-enabled for requests</div></div>
    <div class="rt-stat"><span class="rt-stat-label">Assigned tenants</span><span class="rt-stat-value">{{ $assignedCount }}</span><div class="rt-stat-sub">Current room assignments</div></div>
    <div class="rt-stat"><span class="rt-stat-label">Unfilled capacity</span><span class="rt-stat-value">{{ $vacantSlots }}</span><div class="rt-stat-sub">All non-archived rooms</div></div>
</div>

<section class="rt-card" aria-labelledby="current-rooms-title">
    <div class="rt-card-header">
        <div><h2 id="current-rooms-title">Current rooms</h2><p>Availability, occupancy and rental pricing</p></div>
        <label><span class="rt-muted" style="font-size:11px;display:block;margin-bottom:5px">Search rooms</span><input class="rt-search" type="search" data-room-search placeholder="Room, building, address..." autocomplete="off"></label>
    </div>
    <div class="rt-table-wrap">
        <table class="rt-table">
            <thead><tr><th scope="col">Room</th><th scope="col">Restriction</th><th scope="col">Occupancy</th><th scope="col">Rent price</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
            <tbody>
            @forelse($rooms as $room)
                @php
                    $occupied = (int) $room->occupied_count;
                    $capacity = max(1, (int) $room->maximum_tenants);
                    $isFull = $occupied >= $capacity;
                    $isEnabled = $room->availability_status === 'Available';
                @endphp
                <tr data-room-row data-room-key="{{ strtolower($room->room_number.' '.$room->building_number.' '.$room->building_address) }}">
                    <td><div class="rt-strong">Room {{ $room->room_number }}</div><div class="rt-cell-sub">Building {{ $room->building_number }} · {{ $room->building_address }}</div></td>
                    <td>{{ $room->gender_allowed }}</td>
                    <td style="min-width:152px"><div class="rt-strong">{{ $occupied }} / {{ $capacity }}</div><div class="rt-progress" role="progressbar" aria-label="Room occupancy" aria-valuenow="{{ $occupied }}" aria-valuemin="0" aria-valuemax="{{ $capacity }}"><span style="width:{{ min(100, round(($occupied / $capacity) * 100)) }}%"></span></div><div class="rt-progress-caption">{{ max(0, $capacity - $occupied) }} slot(s) left</div></td>
                    <td class="rt-strong">₱{{ number_format((float) $room->rent_price, 2) }}</td>
                    <td>
                        @if(!$isEnabled)<span class="rt-tag rt-tag-gray">Unavailable</span>
                        @elseif($isFull)<span class="rt-tag rt-tag-amber">Full</span>
                        @else<span class="rt-tag rt-tag-green">Available</span>@endif
                    </td>
                    <td><div class="rt-actions"><a class="rt-btn rt-btn-secondary rt-btn-small" href="{{ route('admin.rooms.edit', ['room' => $room->room_id]) }}">Edit</a>
                        @if($occupied === 0)
                            <form method="POST" action="{{ route('admin.rooms.archive', ['room' => $room->room_id]) }}" data-confirm-title="Archive room" data-confirm-message="Archive this room? Only empty rooms without pending requests can be archived." data-confirm-button="Archive room" data-confirm-danger="true">
                                @csrf @method('PATCH')<button type="submit" class="rt-btn rt-btn-danger rt-btn-small">Archive</button>
                            </form>
                        @endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="rt-empty"><span class="rt-empty-icon" aria-hidden="true">⌂</span><strong>No rooms added yet</strong>Create your first room using the form below.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="rt-card-body rt-muted" data-search-empty hidden>No rooms match your search.</div>
</section>

<section class="rt-card" id="create-room" aria-labelledby="create-title">
    <div class="rt-card-header"><div><h2 id="create-title">Add a new room</h2><p>Required fields are marked with an asterisk</p></div><span class="rt-tag rt-tag-blue">New inventory</span></div>
    <div class="rt-card-body">
        <form method="POST" action="{{ route('admin.rooms.store') }}" data-confirm-title="Create room" data-confirm-message="Add this room to the RentTrack inventory?" data-confirm-button="Create room">
            @csrf
            <div class="rt-form-grid">
                <div class="rt-field"><label for="building_number">Building number *</label><input id="building_number" name="building_number" value="{{ old('building_number') }}" maxlength="20" required></div>
                <div class="rt-field"><label for="room_number">Room number *</label><input id="room_number" name="room_number" value="{{ old('room_number') }}" maxlength="20" required></div>
                <div class="rt-field rt-field-wide"><label for="building_address">Building address *</label><input id="building_address" name="building_address" value="{{ old('building_address') }}" maxlength="150" required></div>
                <div class="rt-field"><label for="gender_allowed">Gender allowed *</label><select id="gender_allowed" name="gender_allowed" required><option value="" disabled {{ old('gender_allowed') ? '' : 'selected' }}>Select restriction</option><option value="Male" {{ old('gender_allowed') === 'Male' ? 'selected' : '' }}>Male</option><option value="Female" {{ old('gender_allowed') === 'Female' ? 'selected' : '' }}>Female</option></select></div>
                <div class="rt-field"><label for="maximum_tenants">Maximum tenants *</label><input id="maximum_tenants" type="number" name="maximum_tenants" min="1" max="100" value="{{ old('maximum_tenants', 1) }}" required></div>
                <div class="rt-field"><label for="rent_price">Rental price (₱) *</label><input id="rent_price" type="number" name="rent_price" min="0" max="99999999.99" step="0.01" value="{{ old('rent_price') }}" required><small>Enter the price according to your billing policy.</small></div>
            </div>
            <div class="rt-form-actions"><button class="rt-btn" type="submit">+ Create room</button><span class="rt-muted" style="font-size:12px">New rooms start as Available with zero occupants.</span></div>
        </form>
    </div>
</section>

<section class="rt-card" aria-labelledby="archived-title">
    <div class="rt-card-header"><div><h2 id="archived-title">Archived rooms <span class="rt-muted">({{ $archivedRooms->count() }})</span></h2><p>Preserved for history; restored rooms return as Unavailable</p></div></div>
    <div class="rt-table-wrap"><table class="rt-table"><thead><tr><th scope="col">Room</th><th scope="col">Building</th><th scope="col">Rent price</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
        <tbody>
            @forelse($archivedRooms as $room)
                <tr><td class="rt-strong">Room {{ $room->room_number }}</td><td>{{ $room->building_number }}</td><td>₱{{ number_format((float) $room->rent_price, 2) }}</td><td><span class="rt-tag rt-tag-gray">Archived</span></td><td><form method="POST" action="{{ route('admin.rooms.restore', ['room' => $room->room_id]) }}" data-confirm-title="Restore room" data-confirm-message="Restore this room as Unavailable? You can activate it manually afterward." data-confirm-button="Restore room">@csrf @method('PATCH')<button class="rt-btn rt-btn-secondary rt-btn-small" type="submit">Restore</button></form></td></tr>
            @empty
                <tr><td colspan="5"><div class="rt-empty"><strong>No archived rooms</strong>All room records are in the active inventory.</div></td></tr>
            @endforelse
        </tbody>
    </table></div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const search = document.querySelector('[data-room-search]');
    if (!search) return;
    const rows = Array.from(document.querySelectorAll('[data-room-row]'));
    const empty = document.querySelector('[data-search-empty]');
    search.addEventListener('input', function () {
        const value = this.value.trim().toLowerCase();
        let shown = 0;
        rows.forEach(function (row) {
            const match = (row.dataset.roomKey || '').includes(value);
            row.hidden = !match;
            if (match) shown++;
        });
        if (empty) empty.hidden = shown !== 0 || rows.length === 0;
    });
})();
</script>
@endpush
