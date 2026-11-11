@extends('layouts.rentrack-auth')
@section('title', 'Request access')
@section('content')
<p class="rt-eyebrow">TENANT REGISTRATION</p>
<h1>Request access</h1>
<p class="ra-desc">Submit the email address you want to use for RentTrack. Eligible requests are reviewed by an administrator before an invitation is issued.</p>
<form method="POST" action="{{ route('register.store') }}">
    @csrf
    <div class="rt-field"><label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" placeholder="you@example.com" required autofocus></div>
    <button class="rt-btn" type="submit">Submit registration request</button>
</form>
<div class="ra-bottom"><a href="{{ route('login') }}">Already have an account? Login</a></div>
<p class="ra-home"><a href="{{ route('home') }}">← Back to RentTrack V2</a></p>
@endsection
