{{-- blade/pages/sacco/login.blade.php
    Standalone SACCO module login.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SACCO Login – TICH ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-amber-900 via-amber-800 to-orange-900 flex items-center justify-center p-4">

<div class="absolute inset-0 opacity-[0.06]" style="background-image:radial-gradient(circle at 2px 2px,white 1px,transparent 0);background-size:28px 28px"></div>

<div class="relative w-full max-w-md">
    <a href="{{ route('home') }}" class="flex items-center gap-2 text-amber-300 text-sm mb-6 hover:text-white transition-colors">
        ← Back to Website
    </a>

    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-amber-700 to-amber-600 px-8 py-7 text-white text-center">
            <div class="flex justify-center mb-3">
                <div class="bg-white rounded-full p-2 shadow-md">
                    <svg class="w-10 h-10 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-xl font-extrabold">SACCO Portal</h1>
            <p class="text-amber-200 text-xs mt-1">Savings & Credit Cooperative</p>
        </div>

        {{-- Form --}}
        <div class="px-8 py-7">
            <h2 class="text-lg font-bold text-gray-900 mb-1">SACCO Member Login</h2>
            <p class="text-sm text-gray-500 mb-6">Access your savings, contributions, and loan account.</p>

            <form method="POST" action="{{ route('sacco.login') }}" class="space-y-4">
                @csrf

                {{-- Employee ID --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="employee_id">Employee ID</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-9-1V3m0 0h9m-9 0h9"/></svg>
                        <input id="employee_id" type="text" name="employee_id" value="{{ old('employee_id') }}" required autofocus autocomplete="off"
                            placeholder="e.g. EMP-008"
                            class="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100">
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="password">Password</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100">
                    </div>
                </div>

                <button type="submit" class="w-full bg-amber-700 text-white font-semibold py-3 rounded-lg hover:bg-amber-800 transition-colors text-sm">
                    Sign In to SACCO
                </button>
            </form>

            <div class="mt-5 pt-4 border-t border-gray-100 text-center">
                <p class="text-[10px] text-gray-400">TICH Staff SACCO · Managed by HR</p>
                <a href="{{ route('login') }}" class="text-xs text-amber-700 hover:underline mt-1 inline-block">Back to Staff Portal</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
