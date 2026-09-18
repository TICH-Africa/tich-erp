@extends('layouts.auth')

@section('title', 'Reset Password')
@section('headline', 'Enter your reset code.')
@section('subheadline', 'Use the one-time code from your email, then choose a new password.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Reset password</h2>
        <p class="tich-text tich-mt-2">Enter the 6-digit code from your email, then set your new password.</p>
        @if (session('password_reset_dev_otp'))
            <p class="tich-caption tich-mt-2" style="color:#b45309;">Development code: {{ session('password_reset_dev_otp') }}</p>
        @endif
    </div>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <div class="tich-form-group">
            <label for="email" class="tich-label">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $email ?? '') }}"
                required
                autofocus
                autocomplete="email"
                class="tich-input @error('email') tich-input--error @enderror"
            >
            @error('email')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="tich-form-group">
            <label for="otp" class="tich-label">Reset code</label>
            <input
                type="text"
                id="otp"
                name="otp"
                value="{{ old('otp') }}"
                required
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                autocomplete="one-time-code"
                class="tich-input @error('otp') tich-input--error @enderror"
                placeholder="6-digit code"
            >
            @error('otp')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="tich-form-group">
            <label for="password" class="tich-label">New password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="new-password"
                class="tich-input @error('password') tich-input--error @enderror"
                placeholder="At least 8 characters"
            >
            @error('password')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="tich-form-group">
            <label for="password_confirmation" class="tich-label">Confirm new password</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="tich-input"
                placeholder="Repeat your new password"
            >
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-btn-block">
            Reset password
        </button>
    </form>

    <p class="tich-text tich-mt-8 tich-text-center">
        Didn’t get a code?
        <a href="{{ route('password.request') }}" class="tich-link">Request a new one</a>
    </p>
@endsection
