@extends('layouts.auth')

@section('title', 'Create ERP Account')
@section('headline', 'You have been invited.')
@section('subheadline', 'Complete your TICH ERP staff account registration.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Create your ERP account</h2>
        @if ($staff)
            <p class="tich-text tich-mt-2">Register as <strong>{{ $staff->fullName() }}</strong> ({{ $staff->employee_number }}).</p>
        @else
            <p class="tich-text tich-mt-2">Complete registration for <strong>{{ $invitation->email }}</strong>.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('register.invite.store', $invitation->token) }}" data-client-context data-uf="skip">
        @csrf
        @include('partials.client-context-fields')

        <div class="uf-field">
            <label for="email">Personal email</label>
            <input
                type="email"
                id="email"
                value="{{ $invitation->email }}"
                readonly
            >
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
        Already registered?
        <a href="{{ route('login') }}" class="tich-link">Sign in</a>
    </p>
@endsection
