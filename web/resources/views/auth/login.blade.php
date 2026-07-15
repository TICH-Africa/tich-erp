@extends('layouts.auth')

@section('title', 'Sign In')
@section('headline', 'Welcome back.')
@section('subheadline', 'Sign in to access admissions, academics, finance, and HR modules across your campus.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Sign in</h2>
        <p class="mt-2 text-sm text-slate-600">Enter your credentials to continue to the ERP portal.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="login" class="mb-1.5 block text-sm font-medium text-slate-700">Email or username</label>
            <input
                type="text"
                id="login"
                name="login"
                value="{{ old('login') }}"
                required
                autofocus
                autocomplete="username"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('login') border-red-400 @enderror"
                placeholder="you@tich.ac.ke or username"
            >
            @error('login')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">
                    Forgot password?
                </a>
            </div>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('password') border-red-400 @enderror"
                placeholder="Enter your password"
            >
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2">
            <input
                type="checkbox"
                id="remember"
                name="remember"
                value="1"
                {{ old('remember') ? 'checked' : '' }}
                class="h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500/20"
            >
            <label for="remember" class="text-sm text-slate-600">Keep me signed in on this device</label>
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        >
            Sign in
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-600">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-semibold text-emerald-700 hover:text-emerald-800">Create one</a>
    </p>
@endsection
