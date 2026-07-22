{{-- blade/pages/login.blade.php
    Route: GET|POST /login  →  AuthController@showLogin, AuthController@login
    Standard Laravel Breeze / Fortify auth flow.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign In – TICH ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-green-950 via-green-900 to-teal-900 flex items-center justify-center p-4">

<div class="absolute inset-0 opacity-[0.06]" style="background-image:radial-gradient(circle at 2px 2px,white 1px,transparent 0);background-size:28px 28px"></div>

<div class="relative w-full max-w-md">
    <a href="{{ route('home') }}" class="flex items-center gap-2 text-green-300 text-sm mb-6 hover:text-white transition-colors">
        ← Back to Website
    </a>

    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-green-800 to-green-700 px-8 py-7 text-white text-center">
            <div class="flex justify-center mb-3">
                <div class="bg-white rounded-full p-2 shadow-md">
                    <img src="{{ asset('images/logo.png') }}" alt="TICH Logo" class="h-12 w-12 object-contain">
                </div>
            </div>
            <h1 class="text-xl font-extrabold">TICH Staff Portal</h1>
            <p class="text-green-200 text-xs mt-1">Enterprise Resource Planning System</p>
        </div>

        {{-- Form --}}
        <div class="px-8 py-7">
            <h2 class="text-lg font-bold text-gray-900 mb-1">Sign in to your account</h2>
            <p class="text-sm text-gray-500 mb-6">Enter your institutional credentials to continue.</p>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-2.5 mb-4">
                    @foreach($errors->all() as $error) <p>{{ $error }}</p> @endforeach
                </div>
            @endif

            @if(session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-2.5 mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="email">Institutional Email</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                            placeholder="yourname@tich.ac.tz"
                            class="w-full border @error('email') border-red-300 @else border-gray-200 @enderror rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100">
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="password">Password</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100">
                    </div>
                    <div class="flex justify-end mt-1">
                        @if(Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-xs text-green-600 hover:underline">Forgot password?</a>
                        @endif
                    </div>
                </div>

                {{-- Remember me --}}
                <div class="flex items-center gap-2 mb-5">
                    <input id="remember" type="checkbox" name="remember" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                    <label for="remember" class="text-xs text-gray-600">Remember this device</label>
                </div>

                <button type="submit" class="w-full bg-green-700 text-white font-semibold py-3 rounded-lg hover:bg-green-800 transition-colors text-sm">
                    Sign In to ERP
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-green-400 text-xs mt-4">
        © {{ date('Y') }} TICH ERP System · v1.2 · Authorised Access Only
    </p>
</div>

</body>
</html>
