<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#142f27">
  <title>@yield('title', 'Account Security') | RentTrack</title>
  <link rel="stylesheet" href="{{ asset('css/rentrack-rooms.css') }}">
  <link rel="stylesheet" href="{{ asset('css/rentrack-auth-premium.css') }}">
</head>
<body class="rt-auth-page">
<div class="ra-wrap">
  <aside class="ra-story" aria-label="About RentTrack">
    <a href="{{ route('home') }}" class="ra-story-brand"><span class="ra-brand-symbol">R</span><strong>renttrack<span>.</span></strong></a>
    <div class="ra-story-content"><p class="ra-story-kicker"><span></span> YOUR SPACE, SIMPLIFIED</p><h2>Better spaces.<br>Better living<span>.</span></h2><p>A thoughtful way to manage rooms, track payments and keep everyone connected.</p><div class="ra-story-features"><span>✓ &nbsp; One beautifully organized workspace</span><span>✓ &nbsp; Simple, clear rental information</span><span>✓ &nbsp; Secure access for every account</span></div></div>
    <div class="ra-story-orbit orbit-one" aria-hidden="true"></div><div class="ra-story-orbit orbit-two" aria-hidden="true"></div>
    <p class="ra-story-footer">© {{ now()->year }} RentTrack &nbsp;•&nbsp; Property management, made simple.</p>
  </aside>
  <div class="ra-main"><div class="ra-card">
    <a href="{{ route('home') }}" class="ra-mobile-brand"><span class="ra-brand-symbol">R</span><strong>renttrack<span>.</span></strong></a>
    <div class="ra-card-mark" aria-hidden="true"><svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"><rect x="4" y="9" width="16" height="12" rx="2"/><path d="M8 9V6a4 4 0 0 1 8 0v3"/><circle cx="12" cy="15" r="1.5"/></svg></div>
    @if(session('status')) <div class="rt-alert rt-alert-success" role="status"><span aria-hidden="true">✓</span><div><strong>Success</strong><span>{{ session('status') }}</span></div></div> @endif
    @if($errors->any())<div class="rt-alert rt-alert-error" role="alert"><span aria-hidden="true">!</span><div><strong>Please review</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    @yield('content')
  </div><p class="ra-main-footer">Protected access &nbsp;·&nbsp; Built for peace of mind</p></div>
</div>
<dialog class="rt-confirm-dialog" id="rt-confirm-dialog" aria-labelledby="rt-confirm-title" aria-describedby="rt-confirm-message"><div class="rt-confirm-inner"><span class="rt-confirm-icon" aria-hidden="true">?</span><h2 id="rt-confirm-title" data-confirm-dialog-title>Please confirm</h2><p id="rt-confirm-message" data-confirm-dialog-message>Continue?</p><div class="rt-confirm-actions"><button type="button" class="rt-btn rt-btn-secondary" data-confirm-dialog-cancel>Cancel</button><button type="button" class="rt-btn" data-confirm-dialog-accept>Confirm</button></div></div></dialog>
<script src="{{ asset('js/rentrack-confirm-dialog.js') }}" defer></script>
@stack('scripts')
</body>
</html>
