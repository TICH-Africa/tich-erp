@extends('layouts.auth')

@section('title', 'Forgot Password')
@section('headline', 'Reset your password.')
@section('subheadline', 'Enter the email linked to your account and we will send you a reset link.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Forgot password</h2>
        <p class="tich-text tich-mt-2">We'll email you instructions to set a new password.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="tich-form-group">
            <label for="email" class="tich-label">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                class="tich-input @error('email') tich-input--error @enderror"
                placeholder="you@tich.ac.ke"
            >
            @error('email')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-btn-block">
            Send reset link
        </button>
    </form>

    <p class="tich-text tich-mt-8 tich-text-center">
        Remember your password?
        <a href="{{ route('login') }}" class="tich-link">Back to sign in</a>
    </p>
@endsection
