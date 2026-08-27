<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Sign In') - {{ config('app.name', 'TICH ERP') }}</title>
    @include('partials.head-assets')
</head>
<body class="tich-body">
    <div class="flex min-h-screen">
        <aside class="tich-auth-aside" aria-label="Brand introduction">
            <div>
                @include('partials.brand-logo', ['variant' => 'light'])
            </div>

            <div class="max-w-md space-y-6">
                <h1 class="tich-h1">
                    @yield('headline', 'Manage your institution with confidence.')
                </h1>
                <p class="tich-auth-aside__lead">
                    @yield('subheadline', 'A unified platform for admissions, academics, finance, and human resources across TICH campuses.')
                </p>
                <ul class="tich-auth-aside__list">
                    <li>Secure role-based access for staff and students</li>
                    <li>Multi-campus operations in one system</li>
                    <li>Built for community health education</li>
                </ul>
            </div>

            <p class="tich-caption" style="color: rgba(255,255,255,0.7);">&copy; {{ date('Y') }} TICH in Africa. All rights reserved.</p>
        </aside>

        <main class="tich-auth-main" id="main-content">
            <div class="tich-auth-main__toolbar">
                @include('partials.theme-toggle')
            </div>
            <div class="tich-auth-form">
                <div class="mb-8 lg:hidden">
                    @include('partials.brand-logo')
                </div>

                @include('partials.alerts')

                @yield('content')
            </div>
        </main>
    </div>
    <x-asset.script path="js/tich-password-toggle.js" />
</body>
</html>
