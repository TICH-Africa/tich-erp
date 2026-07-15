@extends('layouts.auth')

@section('title', 'Reset Password')
@section('headline', 'Choose a new password.')
@section('subheadline', 'Set a strong password to secure your TICH ERP account.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Reset password</h2>
        <p class="tich-text tich-mt-2">Enter your new password below.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

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
@endsection
