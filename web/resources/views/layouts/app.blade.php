<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') — {{ $siteMeta['short_name'] ?? config('app.name', 'TICH ERP') }}</title>
    @include('partials.head-assets')
</head>
<body class="tich-body{{ request()->routeIs('home') ? ' page-home' : '' }}">
    @include('partials.navigation.header')

    <main>
        @include('partials.alerts')
        @yield('content')
    </main>

    @include('partials.navigation.footer')

    <script src="{{ asset('js/tich-homepage.js') }}" defer></script>
</body>
</html>
