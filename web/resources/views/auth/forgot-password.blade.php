@extends('layouts.auth')

@section('title', 'Forgot Password')
@section('headline', 'Reset your password.')
@section('subheadline', 'Enter the email linked to your account and we will send a one-time reset code.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Forgot password</h2>
        <p class="tich-text tich-mt-2">We'll email a 6-digit reset code so you can set a new password.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" data-uf="skip">
        @csrf

        <div class="uf-field">
            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                placeholder="you@example.com"
            >
            @error('email')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-btn-block">
            Send reset code
        </button>
    </form>

    <p class="tich-text tich-mt-8 tich-text-center">
        Remember your password?
        <a href="{{ route('login') }}" class="tich-link">Back to sign in</a>
    </p>
@endsection
