@extends('layouts.auth')

@section('title', 'Sign In')
@section('headline', 'Welcome back.')
@section('subheadline', 'Sign in to access admissions, academics, finance, and HR modules across your campus.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Sign in</h2>
        <p class="tich-text tich-mt-2">Enter your credentials to continue to the ERP portal.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" data-client-context data-uf="skip">
        @csrf
        @include('partials.client-context-fields')

        <div class="uf-field">
            <label for="login">Email address</label>
            <input
                type="email"
                id="login"
                name="login"
                value="{{ old('login') }}"
                required
                autofocus
                autocomplete="email"
                class="{{ $errors->has('login') ? 'is-invalid' : '' }}"
                placeholder="you@tich.ac.ke"
            >
            @error('login')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="uf-field">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.375rem;">
                <label for="password" style="margin: 0;">Password</label>
                <a href="{{ route('password.request') }}" class="tich-link" style="font-size: var(--text-body);">Forgot password?</a>
            </div>
            <x-password-input
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                :has-error="$errors->has('password')"
            />
            @error('password')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="uf-field tich-auth-remember">
            <input
                type="checkbox"
                id="remember"
                name="remember"
                value="1"
                {{ old('remember') ? 'checked' : '' }}
                class="tich-checkbox"
            >
            <label for="remember">Keep me signed in on this device</label>
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-btn-block">
            Sign in
        </button>
    </form>

    <p class="tich-text tich-mt-8 tich-text-center">
        Staff access is by invitation only. Contact ICT or HR if you need an ERP account.
    </p>
@endsection
