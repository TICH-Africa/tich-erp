<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') - {{ $siteMeta['short_name'] ?? config('app.name', 'TICH ERP') }}</title>
    @include('partials.head-assets')
</head>
<body class="tich-body{{ request()->routeIs('home') ? ' page-home' : '' }}">
    @include('partials.navigation.header')

    @php
        $hideAppFooter = request()->routeIs([
            'dashboard',
            'admin.*',
            'administration.*',
            'site-settings.*',
            'departments.*',
            'finance.*',
            'sis.*',
            'admissions.*',
            'portal.*',
            'staff.*',
            'employee.*',
            'hr.*',
            'qa.*',
            'procurement.*',
            'research.*',
            'ict.*',
        ]);

        $hideAppAlerts = request()->routeIs([
            'admin.*',
            'administration.*',
            'departments.*',
            'finance.*',
            'sis.*',
            'admissions.*',
            'portal.*',
            'staff.*',
            'employee.*',
            'hr.*',
            'qa.*',
            'procurement.*',
            'research.*',
            'ict.*',
        ]);
    @endphp

    <main>
        @unless ($hideAppAlerts)
            @include('partials.alerts')
        @endunless
        @yield('content')
    </main>

    @unless ($hideAppFooter)
        @include('partials.navigation.footer')
    @endunless

    <script src="{{ asset('js/tich-nav.js') }}" defer></script>
    <script src="{{ asset('js/tich-homepage.js') }}" defer></script>
    @yield('scripts')
</body>
</html>
