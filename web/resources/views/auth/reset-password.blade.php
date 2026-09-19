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

    <form method="POST" action="{{ route('password.update') }}" data-uf="skip">
        @csrf

        <div class="uf-field">
            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $email ?? '') }}"
                required
                autofocus
                autocomplete="email"
                class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
            >
            @error('email')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="uf-field">
            <label for="otp">Reset code</label>
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
                class="tich-input--code {{ $errors->has('otp') ? 'is-invalid' : '' }}"
                placeholder="6-digit code"
            >
            @error('otp')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="uf-field">
            <label for="password">New password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="new-password"
                class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                placeholder="At least 8 characters"
            >
            @error('password')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="uf-field">
            <label for="password_confirmation">Confirm new password</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
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
