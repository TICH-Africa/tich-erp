<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign In') — {{ config('app.name', 'TICH ERP') }}</title>
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen">
        {{-- Brand panel --}}
        <aside class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-emerald-800 via-teal-800 to-slate-900 p-12 text-white lg:flex">
            <div class="absolute inset-0 opacity-20">
                <div class="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-emerald-400 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-teal-300 blur-3xl"></div>
            </div>

            <div class="relative">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-lg font-bold backdrop-blur">T</span>
                    <div>
                        <p class="text-sm font-medium text-emerald-100">Tropical Institute of Community Health</p>
                        <p class="text-lg font-semibold tracking-tight">TICH ERP</p>
                    </div>
                </a>
            </div>

            <div class="relative max-w-md space-y-6">
                <h1 class="text-4xl font-bold leading-tight tracking-tight">
                    @yield('headline', 'Manage your institution with confidence.')
                </h1>
                <p class="text-base leading-relaxed text-emerald-100/90">
                    @yield('subheadline', 'A unified platform for admissions, academics, finance, and human resources across TICH campuses.')
                </p>
                <ul class="space-y-3 text-sm text-emerald-50/90">
                    <li class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                        Secure role-based access for staff and students
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                        Multi-campus operations in one system
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                        Built for community health education
                    </li>
                </ul>
            </div>

            <p class="relative text-xs text-emerald-200/70">&copy; {{ date('Y') }} TICH in Africa. All rights reserved.</p>
        </aside>

        {{-- Form panel --}}
        <main class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-700 text-sm font-bold text-white">T</span>
                        <span class="font-semibold text-slate-900">TICH ERP</span>
                    </a>
                </div>

                @include('partials.alerts')

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
