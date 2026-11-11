@extends('layouts.rentrack-auth')
@section('title', 'Forgot Password')
@section('content')
<h1>Forgot your password?</h1>
<p class="ra-desc">Enter the email linked to your RentTrack account. We'll send a six-digit verification code if the account exists.</p>
<form method="POST" action="{{ route('password.otp.send') }}">
 @csrf
 <div class="rt-field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" maxlength="255" required autofocus></div>
 <button class="rt-btn" type="submit">Send reset code</button>
</form>
<div class="ra-bottom"><a href="{{ route('password.otp.form') }}">Already have a code?</a><a href="{{ route('login') }}">Back to login</a></div>
@endsection
