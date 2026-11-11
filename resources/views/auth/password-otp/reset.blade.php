@extends('layouts.rentrack-auth')
@section('title', 'Reset Password with OTP')
@section('content')
<h1>Reset password</h1>
<p class="ra-desc">Enter your email and six-digit OTP. Codes expire in 10 minutes, are single-use, and allow at most five failed attempts.</p>
<form method="POST" action="{{ route('password.otp.reset') }}" data-confirm-title="Reset your password" data-confirm-message="Change your RentTrack password using this one-time verification code?" data-confirm-button="Reset password">
 @csrf
 <div class="rt-field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email', $email) }}" maxlength="255" required></div>
 <div class="rt-field"><label for="otp">Six-digit OTP</label><input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" placeholder="000000" required></div>
 <div class="rt-field"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required><small>At least 12 characters, with uppercase, lowercase, a number, and a symbol.</small></div>
 <div class="rt-field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required></div>
 <button class="rt-btn" type="submit">Reset Password</button>
</form>
<div class="ra-bottom"><a href="{{ route('password.otp.request') }}">Request a new OTP</a><a href="{{ route('login') }}">Back to login</a></div>
@endsection
