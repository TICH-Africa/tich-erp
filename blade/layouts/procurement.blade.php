{{-- blade/layouts/procurement.blade.php
    Standalone Procurement & Logistics module layout.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Procurement – TICH ERP')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-blue-50 font-body antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="w-60 flex-shrink-0 bg-white border-r border-gray-100 flex flex-col">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <div class="w-8 h-8 rounded-full bg-blue-700 flex items-center justify-center text-white font-bold text-xs">P</div>
            <div>
                <p class="text-sm font-extrabold text-blue-800 leading-tight">Procurement</p>
                <p class="text-[10px] text-gray-400 leading-tight">TICH Portal</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
            <a href="{{ route('procurement.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.dashboard') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Dashboard
            </a>
            <a href="{{ route('procurement.requisitions') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.requisitions') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v11.25A2.25 2.25 0 006 19.5h.75m9 0h3.75"/></svg>
                Requisitions
            </a>
            <a href="{{ route('procurement.suppliers') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.suppliers') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 21v-7.5a2.25 2.25 0 00-2.25-2.25H9l-3 3V21h10.5zM15.75 21h-9"/></svg>
                Suppliers
            </a>
            <a href="{{ route('procurement.orders') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.orders') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                Purchase Orders
            </a>
            <a href="{{ route('procurement.invoices') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.invoices') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V3.375c0-.621-.504-1.125-1.125-1.125z"/></svg>
                Invoices
            </a>
            <a href="{{ route('procurement.assets') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.assets') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                Assets
            </a>
            <a href="{{ route('procurement.inventory') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.inventory') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 .75H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5h-15M3.375 14.25h15m-15 0V9.375c0-.621.504-1.125 1.125-1.125h9.375c.621 0 1.125.504 1.125 1.125v5.25m-9.375 0h9.375"/></svg>
                Inventory
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
                <p class="text-sm font-bold text-gray-800">@yield('page_title', 'Procurement Dashboard')</p>
                <p class="text-xs text-gray-400">Procurement · {{ now()->format('D, d M Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-700 flex items-center justify-center text-white text-xs font-bold">
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
