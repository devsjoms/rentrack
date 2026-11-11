@extends('layouts.rentrack-auth')
@section('title', 'Sign in')
@section('content')
<p class="rt-eyebrow">SECURE ACCOUNT ACCESS</p>
<h1>Welcome back</h1>
<p class="ra-desc">Sign in with your RentTrack account. You will verify your login with a one-time email code.</p>
<form method="POST" action="{{ route('login.submit') }}">
    @csrf
    <div class="rt-field"><label for="username">Username</label><input id="username" type="text" name="username" value="{{ old('username') }}" autocomplete="username" maxlength="30" required autofocus></div>
    <div class="rt-field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required></div>
    <button class="rt-btn" type="submit" data-login-submit>Login</button>
</form>
<div class="ra-bottom"><a href="{{ route('password.otp.request') }}">Forgot password?</a><a href="{{ route('register') }}">Request access</a></div>
<p class="ra-home"><a href="{{ route('home') }}">← Back to RentTrack V2</a></p>
@push('scripts')
<script>
document.querySelector('form[action="{{ route('login.submit') }}"]')?.addEventListener('submit', function () {
    const button = this.querySelector('[data-login-submit]');
    if (button && this.checkValidity()) { button.disabled = true; button.textContent = 'Logging in...'; }
});
</script>
@endpush
@endsection
