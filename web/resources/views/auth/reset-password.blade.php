@extends('layouts.auth')

@section('title', 'Reset Password')
@section('headline', 'Choose a new password.')
@section('subheadline', 'Set a strong password to secure your TICH ERP account.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Reset password</h2>
        <p class="mt-2 text-sm text-slate-600">Enter your new password below.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $email ?? '') }}"
                required
                autofocus
                autocomplete="email"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('email') border-red-400 @enderror"
            >
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
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
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm new password</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                placeholder="Repeat your new password"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        >
            Reset password
        </button>
    </form>
@endsection
