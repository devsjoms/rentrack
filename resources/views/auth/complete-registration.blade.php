@extends('layouts.rentrack-auth')
@section('title', 'Complete registration')
@section('content')
<p class="rt-eyebrow">APPROVED INVITATION</p>
<h1>Complete your registration</h1>
<p class="ra-desc">Your request has been approved. Finish your tenant profile and create a secure RentTrack account.</p>
<div class="ra-notice"><strong>Approved email</strong><span>{{ $registration->email }}</span></div>
<form method="POST" action="{{ route('registration.complete.store', ['token' => $token]) }}">
    @csrf
    <h2 class="ra-form-heading">Personal information</h2>
    <div class="rt-field"><label for="tenant_name">Full name</label><input id="tenant_name" type="text" name="tenant_name" maxlength="50" value="{{ old('tenant_name') }}" required></div>
    <div class="ra-two-column"><div class="rt-field"><label for="tenant_age">Age</label><input id="tenant_age" type="number" name="tenant_age" min="1" max="120" value="{{ old('tenant_age') }}" required></div><div class="rt-field"><label for="tenant_gender">Gender</label><select id="tenant_gender" name="tenant_gender" required><option value="">Select gender</option>@foreach(['Male','Female','Other'] as $gender)<option value="{{ $gender }}" @selected(old('tenant_gender') === $gender)>{{ $gender }}</option>@endforeach</select></div></div>
    <div class="rt-field"><label for="tenant_address">Address</label><input id="tenant_address" type="text" name="tenant_address" maxlength="70" value="{{ old('tenant_address') }}" required></div>
    <div class="rt-field"><label for="tenant_number">Contact number</label><input id="tenant_number" type="tel" name="tenant_number" maxlength="50" value="{{ old('tenant_number') }}" autocomplete="tel" required></div>
    <h2 class="ra-form-heading">Account credentials</h2>
    <div class="rt-field"><label for="username">Username</label><input id="username" type="text" name="username" minlength="3" maxlength="30" autocomplete="username" value="{{ old('username') }}" required></div>
    <div class="rt-field"><label for="password">Password</label><input id="password" type="password" name="password" minlength="12" autocomplete="new-password" required><small>At least 12 characters, with uppercase, lowercase, a number, and a symbol.</small></div>
    <div class="rt-field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required></div>
    <button class="rt-btn" type="submit">Create tenant account</button>
</form>
@endsection
