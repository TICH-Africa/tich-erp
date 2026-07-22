@extends('layouts.auth')

@section('title', 'Activate student portal')
@section('headline', 'Welcome to TICH.')
@section('subheadline', 'Create your password to access the student portal and your enrolment records.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Activate your student portal</h2>
        <p class="tich-text tich-mt-2">
            You have been admitted to <strong>{{ $student->program?->program_name ?? 'your programme' }}</strong>.
            Set a username and password to sign in.
        </p>
    </div>

    <div class="tich-card tich-mb-6" style="padding: 1rem 1.25rem;">
        <p class="tich-caption">Registration number</p>
        <p class="tich-text" style="font-weight: 600;">{{ $student->registration_number }}</p>
        <p class="tich-caption tich-mt-3">Application number</p>
        <p class="tich-text">{{ $applicant?->application_number }}</p>
        <p class="tich-caption tich-mt-3">Email</p>
        <p class="tich-text">{{ $applicant?->email }}</p>
    </div>

    <form method="POST" action="{{ route('portal.activate.store', $student->portal_invite_token) }}" data-client-context>
        @csrf
        @include('partials.client-context-fields')

        <div class="tich-form-group">
            <label for="username" class="tich-label">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="{{ old('username', $suggestedUsername) }}"
                required
                autofocus
                autocomplete="username"
                class="tich-input @error('username') tich-input--error @enderror"
            >
            @error('username')
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
            >
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-btn-block">Create account &amp; open portal</button>
    </form>

    <p class="tich-text tich-mt-8 tich-text-center">
        Already activated?
        <a href="{{ route('login') }}" class="tich-link">Sign in</a>
    </p>
@endsection
