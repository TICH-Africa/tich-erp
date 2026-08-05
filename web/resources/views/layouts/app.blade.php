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
    <x-ui.header
        :logoSrc="asset('images/logo.png')"
        :tagline="$siteMeta['tagline'] ?? 'Community health education for Africa'"
        :navGroups="[]"
        :standaloneLinks="collect($headerMenu)->map(function ($item) {
            return [
                'label' => $item['label'],
                'href' => $item['url'] ?? '#',
                'route' => $item['url_or_route'] ?? null,
            ];
        })->values()->all()"
    />

    @php
        $hideAppFooter = request()->routeIs([
            'dashboard',
            'admin.*',
            'departments.show',
            'departments.academics.*',
            'sis.*',
            'admissions.*',
            'portal.*',
            'staff.*',
            'employee.*',
            'hr.*',
        ]);

        $hideAppAlerts = request()->routeIs([
            'admin.*',
            'departments.show',
            'departments.academics.*',
            'sis.*',
            'admissions.*',
            'portal.*',
            'staff.*',
            'employee.*',
            'hr.*',
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

    <script src="{{ asset('js/tich-homepage.js') }}" defer></script>
    @yield('scripts')
</body>
</html>
