@extends('layouts.auth')

@section('title', 'Create Account')
@section('headline', 'Join the TICH community.')
@section('subheadline', 'Create a portal account to apply for programmes, access student services, or get started as staff.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Create account</h2>
        <p class="tich-text tich-mt-2">Register for access to the TICH ERP portal.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" data-client-context data-uf="skip">
        @csrf
        @include('partials.client-context-fields')

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

        <div class="uf-field">
            <label for="user_type">Account type</label>
            <select
                id="user_type"
                name="user_type"
                required
                class="{{ $errors->has('user_type') ? 'is-invalid' : '' }}"
            >
                <option value="student" {{ old('user_type', 'student') === 'student' ? 'selected' : '' }}>Student / Applicant</option>
                <option value="staff" {{ old('user_type') === 'staff' ? 'selected' : '' }}>Staff</option>
                <option value="external" {{ old('user_type') === 'external' ? 'selected' : '' }}>External partner</option>
            </select>
            @error('user_type')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="uf-field">
            <label for="password">Password</label>
            <x-password-input
                id="password"
                name="password"
                placeholder="At least 8 characters"
                autocomplete="new-password"
                :has-error="$errors->has('password')"
            />
            @error('password')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="uf-field">
            <label for="password_confirmation">Confirm password</label>
            <x-password-input
                id="password_confirmation"
                name="password_confirmation"
                placeholder="Repeat your password"
                autocomplete="new-password"
            />
        </div>

        <div class="uf-field" style="flex-direction: row; align-items: flex-start; gap: 0.5rem;">
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
            <label for="terms" class="tich-text" style="font-weight: 400;">
                I agree to the
                <a href="{{ route('privacy') }}" class="tich-link" target="_blank" rel="noopener noreferrer">Privacy Policy</a>
                and
                <a href="{{ route('terms') }}" class="tich-link" target="_blank" rel="noopener noreferrer">Terms and Conditions</a>
                of TICH in Africa.
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
