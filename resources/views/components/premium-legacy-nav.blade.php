{{-- Navigation for operational screens that still have standalone Blade templates. --}}
@php($isLegacyAdmin = request()->is('admin/*'))
<header class="rt-legacy-nav">
  <a class="rt-legacy-logo" href="{{ $isLegacyAdmin ? route('admin.dashboard') : route('tenant.dashboard') }}"><span>R</span><strong>renttrack<b>.</b></strong><small>WORKSPACE</small></a>
  <nav class="rt-legacy-links" aria-label="Workspace navigation">
  @if($isLegacyAdmin)
    <a href="{{ route('admin.dashboard') }}" @class(['current' => request()->routeIs('admin.dashboard')])>Overview</a>
    <a href="{{ route('admin.registrations.index') }}" @class(['current' => request()->is('admin/registration-requests*')])>Registrations</a>
    <a href="{{ route('admin.rooms.index') }}" @class(['current' => request()->is('admin/rooms*')])>Rooms</a>
    <a href="{{ route('admin.room-requests.index') }}" @class(['current' => request()->is('admin/room-requests*')])>Requests</a>
    <a href="{{ route('admin.billings.index') }}" @class(['current' => request()->is('admin/billings*')])>Billing</a>
    <a href="{{ route('admin.advance-payments.index') }}" @class(['current' => request()->is('admin/advance-payments*')])>Advance payments</a>
  @else
    <a href="{{ route('tenant.dashboard') }}">Overview</a><a href="{{ route('tenant.rooms.index') }}">Rooms</a><a href="{{ route('tenant.billings.index') }}">Billing</a>
  @endif
  </nav>
  <a class="rt-legacy-back" href="{{ $isLegacyAdmin ? route('admin.dashboard') : route('tenant.dashboard') }}">↖ &nbsp;Dashboard</a>
  <details class="rt-legacy-menu"><summary aria-label="Open workspace navigation">☰</summary><nav aria-label="Mobile workspace navigation">
  @if($isLegacyAdmin)
    <a href="{{ route('admin.dashboard') }}">Overview</a><a href="{{ route('admin.registrations.index') }}">Registrations</a><a href="{{ route('admin.rooms.index') }}">Rooms</a><a href="{{ route('admin.room-requests.index') }}">Room requests</a><a href="{{ route('admin.billings.index') }}">Billing</a><a href="{{ route('admin.advance-payments.index') }}">Advance payments</a>
  @else
    <a href="{{ route('tenant.dashboard') }}">Overview</a><a href="{{ route('tenant.rooms.index') }}">Rooms</a><a href="{{ route('tenant.billings.index') }}">Billing</a>
  @endif
  </nav></details>
</header>
