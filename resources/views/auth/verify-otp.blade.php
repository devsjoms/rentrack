@extends('layouts.rentrack-auth')
@section('title', 'Verify login')
@section('content')
<p class="rt-eyebrow">SECURITY VERIFICATION</p>
<h1>Verify your login</h1>
<p class="ra-desc">Enter the six-digit verification code sent to the email address linked to your account. Codes expire after five minutes.</p>
<form action="{{ route('otp.verify') }}" method="POST">
    @csrf
    <div class="rt-field"><label for="otp">Verification code</label><input class="ra-code" id="otp" type="text" name="otp" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" required autofocus></div>
    <button class="rt-btn" type="submit">Verify code</button>
</form>
<form action="{{ route('otp.resend') }}" method="POST" class="ra-secondary-form">@csrf<button class="rt-btn rt-btn-secondary" type="submit">Resend verification code</button></form>
<div class="ra-bottom"><a href="{{ route('login') }}">Back to login</a></div>
@endsection
