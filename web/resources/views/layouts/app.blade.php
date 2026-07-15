<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') — {{ config('app.name', 'TICH ERP') }}</title>
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-700 text-sm font-bold text-white">T</span>
                <div class="hidden sm:block">
                    <p class="text-sm font-semibold leading-none text-slate-900">TICH ERP</p>
                    <p class="text-xs text-slate-500">Community Health Platform</p>
                </div>
            </a>

            <nav class="flex items-center gap-2 sm:gap-4">
                <a href="{{ route('home') }}" class="hidden text-sm font-medium text-slate-600 hover:text-emerald-700 sm:inline">Home</a>

                @auth
                    <span class="hidden text-sm text-slate-500 sm:inline">{{ auth()->user()->username }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                            Sign out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Sign in
                    </a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-800">
                        Create account
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        @include('partials.alerts')
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} Tropical Institute of Community Health and Development in Africa</p>
            <p class="text-xs">ERP platform under active development</p>
        </div>
    </footer>
</body>
</html>
