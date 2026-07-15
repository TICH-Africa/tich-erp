@extends('layouts.auth')

@section('title', 'Create Account')
@section('headline', 'Join the TICH community.')
@section('subheadline', 'Create a portal account to apply for programmes, access student services, or get started as staff.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Create account</h2>
        <p class="tich-text tich-mt-2">Register for access to the TICH ERP portal.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="tich-form-group">
            <label for="username" class="tich-label">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="{{ old('username') }}"
                required
                autofocus
                autocomplete="username"
                class="tich-input @error('username') tich-input--error @enderror"
                placeholder="Choose a unique username"
            >
            @error('username')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="tich-form-group">
            <label for="email" class="tich-label">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                class="tich-input @error('email') tich-input--error @enderror"
                placeholder="you@example.com"
            >
            @error('email')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="tich-form-group">
            <label for="user_type" class="tich-label">Account type</label>
            <select
                id="user_type"
                name="user_type"
                required
                class="tich-select @error('user_type') tich-input--error @enderror"
            >
                <option value="student" {{ old('user_type', 'student') === 'student' ? 'selected' : '' }}>Student / Applicant</option>
                <option value="staff" {{ old('user_type') === 'staff' ? 'selected' : '' }}>Staff</option>
                <option value="external" {{ old('user_type') === 'external' ? 'selected' : '' }}>External partner</option>
            </select>
            @error('user_type')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="tich-form-group">
            <label for="password" class="tich-label">Password</label>
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
            <label for="password_confirmation" class="tich-label">Confirm password</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="tich-input"
                placeholder="Repeat your password"
            >
        </div>

        <div class="tich-form-group" style="display: flex; align-items: flex-start; gap: 0.5rem;">
            <input
                type="checkbox"
                id="terms"
                name="terms"
                value="1"
                required
                {{ old('terms') ? 'checked' : '' }}
                class="tich-checkbox"
                style="margin-top: 0.2rem;"
            >
            <label for="terms" class="tich-text">
                I agree to the institutional data use and privacy policies of TICH in Africa.
            </label>
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-btn-block">
            Create account
        </button>
    </form>

    <p class="tich-text tich-mt-8 tich-text-center">
        Already have an account?
        <a href="{{ route('login') }}" class="tich-link">Sign in</a>
    </p>
@endsection
