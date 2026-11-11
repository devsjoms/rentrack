@extends('layouts.rentrack-auth')
@section('title', 'Verification required')
@section('content')
<p class="rt-eyebrow">SECURITY VERIFICATION</p>
<h1>Verification required</h1>
<p class="ra-desc">Your credentials were accepted, but you are not signed in until you enter the email verification code.</p>
<a class="rt-btn" href="{{ route('otp.form') }}">Verify code</a>
<p class="ra-home"><a href="{{ route('login') }}">Back to login</a></p>
@endsection
