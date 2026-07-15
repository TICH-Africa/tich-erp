@extends('layouts.auth')

@section('title', 'Forgot Password')
@section('headline', 'Reset your password.')
@section('subheadline', 'Enter the email linked to your account and we will send you a reset link.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Forgot password</h2>
        <p class="mt-2 text-sm text-slate-600">We'll email you instructions to set a new password.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('email') border-red-400 @enderror"
                placeholder="you@tich.ac.ke"
            >
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        >
            Send reset link
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-600">
        Remember your password?
        <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:text-emerald-800">Back to sign in</a>
    </p>
@endsection
