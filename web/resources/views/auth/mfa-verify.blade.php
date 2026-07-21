@extends('layouts.auth')

@section('title', 'Verify Identity')
@section('headline', 'Two-factor verification.')
@section('subheadline', 'Enter the verification code from your authenticator app or SMS to complete sign-in.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Verify your identity</h2>
        <p class="tich-text tich-mt-2">
            Multi-factor authentication is enabled on your account.
            @if (!empty($mfaMethod))
                <span class="tich-caption" style="display: block; margin-top: 0.25rem;">Method: {{ ucfirst(str_replace('_', ' ', $mfaMethod)) }}</span>
            @endif
        </p>
    </div>

    @if (config('app.debug') && session('mfa_dev_code'))
        <div class="tich-notice tich-notice--warning tich-mb-6">
            <p class="tich-caption tich-mb-2">Development only — your verification code</p>
            <p class="tich-text tich-notice__code">{{ session('mfa_dev_code') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('mfa.verify') }}" data-client-context>
        @csrf
        @include('partials.client-context-fields')

        <div class="tich-form-group">
            <label for="code" class="tich-label">Verification code</label>
            <input
                type="text"
                id="code"
                name="code"
                value="{{ old('code') }}"
                required
                autofocus
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                class="tich-input tich-input--code @error('code') tich-input--error @enderror"
                placeholder="000000"
            >
            @error('code')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-btn-block">
            Verify and continue
        </button>
    </form>

    @if (($mfaMethod ?? '') === 'email')
        <form method="POST" action="{{ route('mfa.resend') }}" class="tich-mt-4 tich-text-center">
            @csrf
            <button type="submit" class="tich-link" style="background: none; border: none; cursor: pointer; font-weight: 500;">
                Resend email code
            </button>
        </form>
    @endif

    <form method="POST" action="{{ route('logout') }}" class="tich-mt-6 tich-text-center">
        @csrf
        <button type="submit" class="tich-link" style="background: none; border: none; cursor: pointer; font-weight: 500;">
            Cancel and sign out
        </button>
    </form>
@endsection
