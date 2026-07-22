{{-- blade/pages/procurement/login.blade.php
    Standalone Procurement & Logistics module login.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Procurement & Logistics Login – TICH ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 flex items-center justify-center p-4">

<div class="absolute inset-0 opacity-[0.06]" style="background-image:radial-gradient(circle at 2px 2px,white 1px,transparent 0);background-size:28px 28px"></div>

<div class="relative w-full max-w-md">
    <a href="{{ route('home') }}" class="flex items-center gap-2 text-blue-300 text-sm mb-6 hover:text-white transition-colors">
        ← Back to Website
    </a>

    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-800 to-blue-700 px-8 py-7 text-white text-center">
            <div class="flex justify-center mb-3">
                <div class="bg-white rounded-full p-2 shadow-md">
                    <svg class="w-10 h-10 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 .75H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5h-15M3.375 14.25h15m-15 0V9.375c0-.621.504-1.125 1.125-1.125h9.375c.621 0 1.125.504 1.125 1.125v5.25m-9.375 0h9.375"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-xl font-extrabold">Procurement & Logistics</h1>
            <p class="text-blue-200 text-xs mt-1">Purchase Orders · Assets · Inventory</p>
        </div>

        {{-- Form --}}
        <div class="px-8 py-7">
            <h2 class="text-lg font-bold text-gray-900 mb-1">Staff Login</h2>
            <p class="text-sm text-gray-500 mb-6">Access requisitions, suppliers, and asset registry.</p>

            <form method="POST" action="{{ route('procurement.login') }}" class="space-y-4">
                @csrf

                {{-- Employee ID / Email --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="login_id">Employee ID or Email</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6A2.25 2.25 0 0019.5 3.75h-15A2.25 2.25 0 002.25 6v11.25A2.25 2.25 0 004.5 19.5z"/></svg>
                        <input id="login_id" type="text" name="login_id" value="{{ old('login_id') }}" required autofocus autocomplete="off"
                            placeholder="e.g. EMP-008 or d.kamau@tich.or.ke"
                            class="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="password">Password</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-700 text-white font-semibold py-3 rounded-lg hover:bg-blue-800 transition-colors text-sm">
                    Sign In to Procurement
                </button>
            </form>

            <div class="mt-5 pt-4 border-t border-gray-100 text-center">
                <p class="text-[10px] text-gray-400">TICH Procurement Office</p>
                <a href="{{ route('login') }}" class="text-xs text-blue-700 hover:underline mt-1 inline-block">Back to Staff Portal</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
