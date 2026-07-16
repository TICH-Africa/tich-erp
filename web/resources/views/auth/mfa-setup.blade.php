@extends('layouts.auth')

@section('title', 'Set Up MFA')
@section('headline', 'Secure your account.')
@section('subheadline', 'Multi-factor authentication is required for all staff and students on the TICH ERP platform.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Configure multi-factor authentication</h2>
        <p class="tich-text tich-mt-2">
            Choose email verification or an authenticator app. This step is mandatory for
            <span class="tich-caption">{{ ucfirst($userType) }}</span> accounts.
        </p>
    </div>

    @if (session('totp_uri'))
        <div class="tich-mb-6" style="padding: 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem;">
            <p class="tich-caption tich-mb-2">Scan with Google Authenticator, Microsoft Authenticator, or similar:</p>
            <p class="tich-text" style="word-break: break-all; font-size: 0.85rem;">{{ session('totp_uri') }}</p>
            @if (session('totp_secret'))
                <p class="tich-caption tich-mt-2">Manual key: <strong>{{ session('totp_secret') }}</strong></p>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('mfa.setup') }}">
        @csrf

        <div class="tich-form-group">
            <label class="tich-label">Verification method</label>
            <div class="tich-mt-2" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="method" value="email" {{ old('method', 'email') === 'email' ? 'checked' : '' }}>
                    <span>Email one-time code</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="method" value="auth_app" {{ old('method') === 'auth_app' ? 'checked' : '' }}>
                    <span>Authenticator app (TOTP)</span>
                </label>
            </div>
        </div>

        @if (session('totp_uri') || old('method') === 'auth_app')
            <div class="tich-form-group">
                <label for="code" class="tich-label">Authenticator code</label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    value="{{ old('code') }}"
                    inputmode="numeric"
                    maxlength="6"
                    class="tich-input tich-input--code @error('code') tich-input--error @enderror"
                    placeholder="000000"
                >
                @error('code')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
        @else
            <div class="tich-form-group">
                <label for="code" class="tich-label">Email verification code <span class="tich-caption">(after requesting)</span></label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    value="{{ old('code') }}"
                    inputmode="numeric"
                    maxlength="6"
                    class="tich-input tich-input--code @error('code') tich-input--error @enderror"
                    placeholder="000000"
                >
                @error('code')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <button type="submit" class="tich-btn tich-btn-primary tich-btn-block">
            @if (session('totp_uri'))
                Confirm authenticator and continue
            @else
                Send code / continue setup
            @endif
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="tich-mt-6 tich-text-center">
        @csrf
        <button type="submit" class="tich-link" style="background: none; border: none; cursor: pointer; font-weight: 500;">
            Cancel and sign out
        </button>
    </form>
@endsection
