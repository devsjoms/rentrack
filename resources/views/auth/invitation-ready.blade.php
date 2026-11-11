@extends('layouts.rentrack-auth')
@section('title', 'Invitation ready')
@section('content')
<p class="rt-eyebrow">REGISTRATION INVITATION</p>
<h1>Your invitation is ready</h1>
<p class="ra-desc">The registration invitation for {{ $registration->email }} is valid until {{ $registration->expires_at->format('M d, Y h:i A') }}.</p>
<p class="ra-home"><a href="{{ route('login') }}">Back to login</a></p>
@endsection
