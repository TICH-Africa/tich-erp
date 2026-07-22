{{-- blade/layouts/sacco.blade.php
    Standalone SACCO module layout.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'SACCO – TICH ERP')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-amber-50 font-body antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="w-60 flex-shrink-0 bg-white border-r border-gray-100 flex flex-col">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <div class="w-8 h-8 rounded-full bg-amber-700 flex items-center justify-center text-white font-bold text-xs">S</div>
            <div>
                <p class="text-sm font-extrabold text-amber-800 leading-tight">SACCO</p>
                <p class="text-[10px] text-gray-400 leading-tight">TICH Portal</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
            <a href="{{ route('sacco.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('sacco.dashboard') ? 'bg-amber-100 text-amber-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Dashboard
            </a>
            <a href="{{ route('sacco.members') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('sacco.members') ? 'bg-amber-100 text-amber-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 014 4V7.5h1.25a2.25 2.25 0 012.25 2.25v10.5A2.25 2.25 0 0117.25 22.5H6.75A2.25 2.25 0 014.5 20.25v-10.5A2.25 2.25 0 016.75 7.5H8V8.354a4 4 0 014-4z"/></svg>
                Members
            </a>
            <a href="{{ route('sacco.savings') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('sacco.savings') ? 'bg-amber-100 text-amber-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                Savings
            </a>
            <a href="{{ route('sacco.loans') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('sacco.loans') ? 'bg-amber-100 text-amber-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Loans
            </a>
        </nav>

        <div class="p-2 border-t border-gray-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-2.5 w-full px-3 py-2 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </button>
            </form>
        </div>
    </aside>

    {{-- MAIN --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header class="h-14 bg-white border-b border-gray-100 flex items-center justify-between px-5 flex-shrink-0">
            <div>
                <p class="text-sm font-bold text-gray-800">@yield('page_title', 'SACCO Dashboard')</p>
                <p class="text-xs text-gray-400">SACCO · {{ now()->format('D, d M Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-700 flex items-center justify-center text-white text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-5">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
