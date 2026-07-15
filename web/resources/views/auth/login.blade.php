@extends('layouts.auth')

@section('title', 'Sign In')
@section('headline', 'Welcome back.')
@section('subheadline', 'Sign in to access admissions, academics, finance, and HR modules across your campus.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Sign in</h2>
        <p class="tich-text tich-mt-2">Enter your credentials to continue to the ERP portal.</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="tich-form-group">
            <label for="login" class="tich-label">Email or username</label>
            <input
                type="text"
                id="login"
                name="login"
                value="{{ old('login') }}"
                required
                autofocus
                autocomplete="username"
                class="tich-input @error('login') tich-input--error @enderror"
                placeholder="you@tich.ac.ke or username"
            >
            @error('login')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="tich-form-group">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.375rem;">
                <label for="password" class="tich-label">Password</label>
                <a href="{{ route('password.request') }}" class="tich-link" style="font-size: var(--text-body);">Forgot password?</a>
            </div>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                class="tich-input @error('password') tich-input--error @enderror"
                placeholder="Enter your password"
            >
            @error('password')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="tich-form-group" style="display: flex; align-items: center; gap: 0.5rem;">
            <input
                type="checkbox"
                id="remember"
                name="remember"
                value="1"
                {{ old('remember') ? 'checked' : '' }}
                class="tich-checkbox"
            >
            <label for="remember" class="tich-text">Keep me signed in on this device</label>
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-btn-block">
            Sign in
        </button>
    </form>

    <p class="tich-text tich-mt-8 tich-text-center">
        Don't have an account?
        <a href="{{ route('register') }}" class="tich-link">Create one</a>
    </p>
@endsection
