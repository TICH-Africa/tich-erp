{{-- blade/layouts/admin.blade.php
    Admin portal shell layout — sidebar + topbar.
    Middleware: auth, role (via gate or policy).
    Pass: $user (Auth::user()), $navItems (array), $pageTitle (string)
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'TICH ERP Admin')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-50 font-body antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- ── SIDEBAR ── --}}
    <aside class="w-60 flex-shrink-0 bg-white border-r border-gray-100 flex flex-col">
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <img src="{{ asset('images/logo.png') }}" alt="TICH" class="h-9 w-9 object-contain">
            <div>
                <p class="text-sm font-extrabold text-green-800 leading-tight">TICH ERP</p>
                <p class="text-xs text-gray-400 leading-tight">Admin Portal</p>
            </div>
        </div>

        {{-- Role badge --}}
        <div class="mx-3 mt-3 px-3 py-2 rounded-lg bg-green-50">
            <p class="text-xs text-gray-500">Signed in as</p>
            <p class="text-xs font-bold text-green-700 mt-0.5">{{ auth()->user()->role_label ?? 'Administrator' }}</p>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
            @foreach($navItems ?? [] as $item)
                <a href="{{ $item['url'] }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs($item['route']) ? 'bg-green-100 text-green-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                    @if(isset($item['icon']))
                        <span class="w-4.5 h-4.5">{!! $item['icon'] !!}</span>
                    @endif
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Logout --}}
        <div class="p-2 border-t border-gray-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex items-center gap-2.5 w-full px-3 py-2 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </button>
            </form>
        </div>
    </aside>

    {{-- ── MAIN AREA ── --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top bar --}}
        <header class="h-14 bg-white border-b border-gray-100 flex items-center justify-between px-5 flex-shrink-0">
            <div>
                <p class="text-sm font-bold text-gray-800">@yield('page_title', 'Dashboard')</p>
                <p class="text-xs text-gray-400">TICH ERP · {{ now()->format('D, d M Y') }}</p>
            </div>

            <div class="flex items-center gap-3">
                {{-- Notifications bell --}}
                <button class="relative w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500">
                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                {{-- User avatar --}}
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-green-700 flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-xs font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400">{{ auth()->user()->role_label ?? '' }}</p>
                    </div>
                </div>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-5">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
