@extends('layouts.auth')

@section('title', 'Verify Identity')
@section('headline', 'Two-factor verification.')
@section('subheadline', 'Enter the verification code from your authenticator app or SMS to complete sign-in.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Verify your identity</h2>
        <p class="mt-2 text-sm text-slate-600">
            Multi-factor authentication is enabled on your account.
            @if (!empty($mfaMethod))
                <span class="block mt-1 text-slate-500">Method: {{ ucfirst(str_replace('_', ' ', $mfaMethod)) }}</span>
            @endif
        </p>
    </div>

    <form method="POST" action="{{ route('mfa.verify') }}" class="space-y-5">
        @csrf

        <div>
            <label for="code" class="mb-1.5 block text-sm font-medium text-slate-700">Verification code</label>
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
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center text-lg font-semibold tracking-[0.3em] text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('code') border-red-400 @enderror"
                placeholder="000000"
            >
            @error('code')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        >
            Verify and continue
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="text-sm font-medium text-slate-500 hover:text-slate-700">
            Cancel and sign out
        </button>
    </form>
@endsection
