@extends('layouts.rentrack-room-panel')
@section('title', 'Admin Dashboard')
@section('section-name', 'Overview')
@section('content')
<section class="rt-dashboard-hero">
    <div class="rt-dashboard-hero-copy">
        <span class="rt-dashboard-eyebrow"><span></span> YOUR CONTROL CENTER</span>
        <h1>Good {{ now('Asia/Manila')->hour < 12 ? 'morning' : (now('Asia/Manila')->hour < 17 ? 'afternoon' : 'evening') }}, {{ $account->admin?->admin_name ?? $account->username }}<span class="rt-title-period">.</span></h1>
        <p>Here's what's happening across your property. Stay organized, make decisions, and keep things moving.</p>
        <div class="rt-dashboard-hero-actions"><a class="rt-btn rt-btn-white" href="{{ route('admin.rooms.index') }}#create-room">Add a new room <svg class="rt-icon"><use href="#rt-i-arrow"/></svg></a><a class="rt-btn rt-btn-outline-light" href="{{ route('admin.registrations.index') }}">Review registrations</a></div>
    </div>
    <div class="rt-hero-decoration" aria-hidden="true"><div class="rt-hero-circle rt-hero-circle-one"></div><div class="rt-hero-circle rt-hero-circle-two"></div><div class="rt-deco-window"><span></span><span></span><span></span><span></span></div></div>
    <div class="rt-hero-date"><svg class="rt-icon"><use href="#rt-i-calendar"/></svg>{{ now('Asia/Manila')->format('l, F j, Y') }}</div>
</section>
<div class="rt-section-heading-row"><div><p class="rt-eyebrow">AT A GLANCE</p><h2>Property overview</h2></div><span class="rt-heading-caption">Live data from your workspace</span></div>
<div class="rt-stats rt-stats-dashboard">
    <a class="rt-stat rt-stat-premium" href="{{ route('admin.rooms.index') }}"><div class="rt-stat-head"><span class="rt-stat-label">Active rooms</span><span class="rt-stat-icon"><svg class="rt-icon"><use href="#rt-i-door"/></svg></span></div><span class="rt-stat-value">{{ number_format($overview['rooms']) }}</span><div class="rt-stat-sub"><span class="rt-stat-indicator"></span> Excludes archived rooms</div><span class="rt-stat-corner">↗</span></a>
    <a class="rt-stat" href="{{ route('admin.room-requests.index') }}"><div class="rt-stat-head"><span class="rt-stat-label">Pending room requests</span><span class="rt-stat-icon"><svg class="rt-icon"><use href="#rt-i-inbox"/></svg></span></div><span class="rt-stat-value">{{ number_format($overview['requests']) }}</span><div class="rt-stat-sub">Awaiting your review</div><span class="rt-stat-corner">↗</span></a>
    <a class="rt-stat" href="{{ route('admin.billings.index') }}"><div class="rt-stat-head"><span class="rt-stat-label">Unpaid statements</span><span class="rt-stat-icon"><svg class="rt-icon"><use href="#rt-i-receipt"/></svg></span></div><span class="rt-stat-value">{{ number_format($overview['billings']) }}</span><div class="rt-stat-sub">Statements to follow up</div><span class="rt-stat-corner">↗</span></a>
    <a class="rt-stat" href="{{ route('admin.registrations.index') }}"><div class="rt-stat-head"><span class="rt-stat-label">Pending registrations</span><span class="rt-stat-icon"><svg class="rt-icon"><use href="#rt-i-users"/></svg></span></div><span class="rt-stat-value">{{ number_format($overview['registrations']) }}</span><div class="rt-stat-sub">New access requests</div><span class="rt-stat-corner">↗</span></a>
</div>
<div class="rt-dashboard-grid rt-overview-panels">
    <section class="rt-card"><div class="rt-card-header"><div><p class="rt-eyebrow">GET THINGS DONE</p><h2>Quick actions</h2><p>One click away from your everyday tasks.</p></div><span class="rt-card-header-mark">↗</span></div><div class="rt-quick-actions rt-quick-actions-premium">
        <a href="{{ route('admin.rooms.index') }}"><span class="rt-action-icon"><svg class="rt-icon"><use href="#rt-i-door"/></svg></span><strong>Manage rooms</strong><span>Inventory, occupancy & availability</span><em>↗</em></a>
        <a href="{{ route('admin.room-requests.index') }}"><span class="rt-action-icon"><svg class="rt-icon"><use href="#rt-i-inbox"/></svg></span><strong>Room requests</strong><span>Review & manage applications</span><em>↗</em></a>
        <a href="{{ route('admin.billings.index') }}"><span class="rt-action-icon"><svg class="rt-icon"><use href="#rt-i-receipt"/></svg></span><strong>Billing center</strong><span>Charges, payments & balances</span><em>↗</em></a>
        <a href="{{ route('admin.advance-payments.index') }}"><span class="rt-action-icon"><svg class="rt-icon"><use href="#rt-i-wallet"/></svg></span><strong>Advance payments</strong><span>Record & allocate verified credit</span><em>↗</em></a>
    </div></section>
    <aside class="rt-card rt-dashboard-note rt-workspace-note"><span class="rt-note-icon"><svg class="rt-icon"><use href="#rt-i-spark"/></svg></span><span class="rt-tag rt-tag-green">SECURE WORKSPACE</span><h2>Everything, under control.</h2><p>A focused home for your property. Your admin tools are protected by role-based access and server-side validation.</p><div class="rt-note-divider"></div><a href="{{ route('admin.registrations.index') }}">View access requests <svg class="rt-icon"><use href="#rt-i-arrow"/></svg></a></aside>
</div>
@endsection
