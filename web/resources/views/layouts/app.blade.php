<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.seo')
    @include('partials.head-assets')
</head>
@php
    $portalRoutePatterns = [
        'dashboard',
        'notifications.*',
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
        'ceo.*',
        'monitoring_evaluation.*',
    ];

    $autohideAppHeader = request()->routeIs($portalRoutePatterns);
    $hideAppFooter = request()->routeIs($portalRoutePatterns);
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
        'ceo.*',
        'monitoring_evaluation.*',
    ]);
@endphp
<body class="tich-body{{ request()->routeIs('home') ? ' page-home' : '' }}{{ $autohideAppHeader ? ' tich-body--autohide-header' : '' }}">
    <a class="tich-skip-link" href="#main-content">Skip to main content</a>
    @include('partials.states.global-banners')
    @include('partials.navigation.header')

    <main id="main-content">
        @unless ($hideAppAlerts)
            @include('partials.alerts')
        @endunless
        @yield('content')
    </main>

    @unless ($hideAppFooter)
        @include('partials.navigation.footer')
    @endunless

    <x-asset.script path="js/tich-nav.js" />
    <x-asset.script path="js/tich-admin-sidebar-mobile.js" />
    <x-asset.script path="js/tich-header-autohide.js" />
    <x-asset.script path="js/tich-sidebar-layout.js" />
    <x-asset.script path="js/tich-homepage.js" />
    <x-asset.script path="js/tich-states.js" />
    <x-asset.script path="js/tich-animations.js" />
    @yield('scripts')
    @stack('scripts')
</body>
</html>
