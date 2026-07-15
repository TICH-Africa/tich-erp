@extends('layouts.auth')

@section('title', 'Create Account')
@section('headline', 'Join the TICH community.')
@section('subheadline', 'Create a portal account to apply for programmes, access student services, or get started as staff.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Create account</h2>
        <p class="mt-2 text-sm text-slate-600">Register for access to the TICH ERP portal.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="username" class="mb-1.5 block text-sm font-medium text-slate-700">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="{{ old('username') }}"
                required
                autofocus
                autocomplete="username"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('username') border-red-400 @enderror"
                placeholder="Choose a unique username"
            >
            @error('username')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('email') border-red-400 @enderror"
                placeholder="you@example.com"
            >
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="user_type" class="mb-1.5 block text-sm font-medium text-slate-700">Account type</label>
            <select
                id="user_type"
                name="user_type"
                required
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('user_type') border-red-400 @enderror"
            >
                <option value="student" {{ old('user_type', 'student') === 'student' ? 'selected' : '' }}>Student / Applicant</option>
                <option value="staff" {{ old('user_type') === 'staff' ? 'selected' : '' }}>Staff</option>
                <option value="external" {{ old('user_type') === 'external' ? 'selected' : '' }}>External Partner</option>
            </select>
            @error('user_type')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="new-password"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('password') border-red-400 @enderror"
                placeholder="At least 8 characters"
            >
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm password</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                placeholder="Repeat your password"
            >
        </div>

        <div class="flex items-start gap-2">
            <input
                type="checkbox"
                id="terms"
                name="terms"
                value="1"
                required
                {{ old('terms') ? 'checked' : '' }}
                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500/20"
            >
            <label for="terms" class="text-sm text-slate-600">
                I agree to the institutional data use and privacy policies of TICH in Africa.
            </label>
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        >
            Create account
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-600">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:text-emerald-800">Sign in</a>
    </p>
@endsection
