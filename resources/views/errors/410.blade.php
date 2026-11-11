@extends('layouts.rentrack-auth')
@section('title', 'Invitation unavailable')
@section('content')
<p class="rt-eyebrow">INVITATION UNAVAILABLE</p>
<h1 class="ra-error-code">410</h1>
<p class="ra-desc">This registration invitation has already been used, has expired, or is invalid. Request a new registration review if you still need access.</p>
<a class="rt-btn" href="{{ route('register') }}">Request registration</a>
<div class="ra-bottom"><a href="{{ route('login') }}">Go to login</a></div>
@endsection
