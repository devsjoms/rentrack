<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#101d23">
    <title>@yield('title', 'Dashboard') | RentTrack</title>
    <link rel="stylesheet" href="{{ asset('css/rentrack-rooms.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-tenant.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-billing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-composition.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-premium.css') }}">
</head>
<body class="rt-portal">
@php
    $isAdminPanel = request()->is('admin/*');
    $displayName = auth()->user()?->admin?->admin_name ?? auth()->user()?->tenant?->tenant_name ?? auth()->user()?->username ?? 'Account';
    $initial = strtoupper(substr(trim($displayName), 0, 1));
@endphp
{{-- Self-contained SVG icon set, so the interface doesn't depend on a third-party icon service. --}}
<svg xmlns="http://www.w3.org/2000/svg" class="rt-icon-definitions" aria-hidden="true">
  <symbol id="rt-i-grid" viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></symbol>
  <symbol id="rt-i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1.5A5.5 5.5 0 0 1 8.5 13h1A5.5 5.5 0 0 1 15 18.5V20M16 5.4a3 3 0 0 1 0 5.7M17 13a5 5 0 0 1 4 5v2"/></symbol>
  <symbol id="rt-i-door" viewBox="0 0 24 24"><path d="M5 21V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v17M3 21h18M9 21V6h6v15"/><circle cx="13" cy="13" r=".7" fill="currentColor" stroke="none"/></symbol>
  <symbol id="rt-i-inbox" viewBox="0 0 24 24"><path d="M4 5h16l2 10v5H2v-5L4 5Z"/><path d="M2.5 15H8l1.5 2h5l1.5-2h5.5"/></symbol>
  <symbol id="rt-i-receipt" viewBox="0 0 24 24"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6M9 16h3"/></symbol>
  <symbol id="rt-i-wallet" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="15" rx="3"/><path d="M3 9V6a2 2 0 0 1 2-2h12M15 13h6M16.5 13h.01"/></symbol>
  <symbol id="rt-i-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
  <symbol id="rt-i-external" viewBox="0 0 24 24"><path d="M14 4h6v6M20 4l-10 10"/><path d="M20 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5"/></symbol>
  <symbol id="rt-i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/></symbol>
  <symbol id="rt-i-check" viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></symbol>
  <symbol id="rt-i-log-out" viewBox="0 0 24 24"><path d="M9 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4M14 7l5 5-5 5M19 12H9"/></symbol>
  <symbol id="rt-i-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol>
  <symbol id="rt-i-spark" viewBox="0 0 24 24"><path d="m12 2 2.3 6.7L21 11l-6.7 2.3L12 20l-2.3-6.7L3 11l6.7-2.3L12 2Z"/></symbol>
</svg>
<div class="rt-shell">
    <button class="rt-nav-toggle" type="button" aria-controls="rt-sidebar" aria-expanded="false" data-nav-toggle><svg class="rt-icon" aria-hidden="true"><use href="#rt-i-menu"/></svg><span class="rt-sr-only">Open navigation</span></button>
    <div class="rt-nav-scrim" data-nav-scrim hidden></div>
    <aside class="rt-sidebar" id="rt-sidebar" aria-label="Portal navigation" aria-hidden="false">
        <button class="rt-nav-close" type="button" data-nav-close aria-label="Close navigation">×</button>
        <a class="rt-brand" href="{{ $isAdminPanel ? route('admin.dashboard') : route('tenant.dashboard') }}">
            <span class="rt-brand-mark" aria-hidden="true"><span>R</span><span class="rt-brand-dot"></span></span>
            <span class="rt-brand-text"><strong>renttrack<span class="rt-brand-period">.</span></strong><small>PROPERTY WORKSPACE</small></span>
        </a>
        <div class="rt-sidebar-context"><span class="rt-sidebar-context-dot"></span><div><strong>{{ $isAdminPanel ? 'Admin workspace' : 'Tenant workspace' }}</strong><small>{{ $isAdminPanel ? 'Property operations' : 'Your rental, simplified' }}</small></div><span class="rt-context-chevron">⌄</span></div>
        <span class="rt-nav-label">MAIN MENU</span>
        <nav class="rt-nav" aria-label="{{ $isAdminPanel ? 'Administrator navigation' : 'Tenant navigation' }}">
            @if($isAdminPanel)
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><svg class="rt-icon"><use href="#rt-i-grid"/></svg><span>Overview</span></a>
                <a href="{{ route('admin.registrations.index') }}" class="{{ request()->is('admin/registration-requests*') ? 'active' : '' }}" @if(request()->is('admin/registration-requests*')) aria-current="page" @endif><svg class="rt-icon"><use href="#rt-i-users"/></svg><span>Registrations</span></a>
                <a href="{{ route('admin.rooms.index') }}" class="{{ request()->is('admin/rooms*') ? 'active' : '' }}" @if(request()->is('admin/rooms*')) aria-current="page" @endif><svg class="rt-icon"><use href="#rt-i-door"/></svg><span>Rooms</span></a>
                <a href="{{ route('admin.room-requests.index') }}" class="{{ request()->is('admin/room-requests*') ? 'active' : '' }}" @if(request()->is('admin/room-requests*')) aria-current="page" @endif><svg class="rt-icon"><use href="#rt-i-inbox"/></svg><span>Room requests</span></a>
                <a href="{{ route('admin.billings.index') }}" class="{{ request()->is('admin/billings*') ? 'active' : '' }}" @if(request()->is('admin/billings*')) aria-current="page" @endif><svg class="rt-icon"><use href="#rt-i-receipt"/></svg><span>Billing</span></a>
                <a href="{{ route('admin.advance-payments.index') }}" class="{{ request()->is('admin/advance-payments*') ? 'active' : '' }}" @if(request()->is('admin/advance-payments*')) aria-current="page" @endif><svg class="rt-icon"><use href="#rt-i-wallet"/></svg><span>Advance payments</span></a>
            @else
                <a href="{{ route('tenant.dashboard') }}" class="{{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}" @if(request()->routeIs('tenant.dashboard')) aria-current="page" @endif><svg class="rt-icon"><use href="#rt-i-grid"/></svg><span>Overview</span></a>
                <a href="{{ route('tenant.rooms.index') }}" class="{{ request()->is('tenant/rooms*') ? 'active' : '' }}" @if(request()->is('tenant/rooms*')) aria-current="page" @endif><svg class="rt-icon"><use href="#rt-i-door"/></svg><span>My room & requests</span></a>
                <a href="{{ route('tenant.billings.index') }}" class="{{ request()->is('tenant/billings*') ? 'active' : '' }}" @if(request()->is('tenant/billings*')) aria-current="page" @endif><svg class="rt-icon"><use href="#rt-i-receipt"/></svg><span>Billing & payments</span></a>
            @endif
        </nav>
        <div class="rt-sidebar-spacer"></div>
        <div class="rt-sidebar-tip"><span class="rt-sidebar-tip-icon"><svg class="rt-icon"><use href="#rt-i-spark"/></svg></span><strong>Everything in its place.</strong><p>A clearer way to stay on top of your rental workspace.</p></div>
        <div class="rt-sidebar-account"><span class="rt-account-avatar">{{ $initial }}</span><div><strong>{{ $displayName }}</strong><small>{{ $isAdminPanel ? 'Administrator' : 'Tenant account' }}</small></div></div>
        <form method="POST" action="{{ route('logout') }}" class="rt-logout">@csrf<button type="submit"><svg class="rt-icon"><use href="#rt-i-log-out"/></svg><span>Sign out</span></button></form>
    </aside>

    <div class="rt-main-wrap">
        <header class="rt-topbar">
            <div class="rt-topbar-left"><span class="rt-topbar-location">Workspace</span><span class="rt-topbar-separator">/</span><strong>@yield('section-name', 'Overview')</strong></div>
            <div class="rt-topbar-right"><span class="rt-topbar-date"><svg class="rt-icon"><use href="#rt-i-calendar"/></svg>{{ now('Asia/Manila')->format('M d, Y') }}</span><a class="rt-topbar-site" href="{{ route('home') }}" title="Go to homepage"><svg class="rt-icon"><use href="#rt-i-external"/></svg><span>View site</span></a><span class="rt-topbar-avatar" aria-label="Signed in as {{ $displayName }}">{{ $initial }}</span></div>
        </header>
        <main class="rt-main" id="main-content">
            @if(session('status'))<div class="rt-alert rt-alert-success" role="status"><span aria-hidden="true">✓</span><div><strong>Success</strong><span>{{ session('status') }}</span></div></div>@endif
            @if(session('warning'))<div class="rt-alert rt-alert-warning" role="status"><span aria-hidden="true">!</span><div><strong>Attention</strong><span>{{ session('warning') }}</span></div></div>@endif
            @if(session('info'))<div class="rt-alert rt-alert-info" role="status"><span aria-hidden="true">i</span><div><strong>Information</strong><span>{{ session('info') }}</span></div></div>@endif
            @if($errors->any())<div class="rt-alert rt-alert-error" role="alert"><span aria-hidden="true">!</span><div><strong>Please review:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
            @yield('content')
            <footer class="rt-app-footer"><span>© {{ now()->year }} RentTrack</span><span>Made for simpler property management.</span></footer>
        </main>
    </div>
</div>
<dialog class="rt-confirm-dialog" id="rt-confirm-dialog" aria-labelledby="rt-confirm-title" aria-describedby="rt-confirm-message"><div class="rt-confirm-inner"><span class="rt-confirm-icon" aria-hidden="true">?</span><h2 id="rt-confirm-title" data-confirm-dialog-title>Please confirm</h2><p id="rt-confirm-message" data-confirm-dialog-message>Continue with this action?</p><div class="rt-confirm-actions"><button type="button" class="rt-btn rt-btn-secondary" data-confirm-dialog-cancel>Cancel</button><button type="button" class="rt-btn" data-confirm-dialog-accept>Confirm</button></div></div></dialog>
<script src="{{ asset('js/rentrack-confirm-dialog.js') }}" defer></script>
<script src="{{ asset('js/rentrack-navigation.js') }}" defer></script>
@stack('scripts')
</body>
</html>
