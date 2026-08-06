@extends('layouts.app')

@section('title', 'HR')

@section('content')
<div class="tich-admin">
    @include('hr.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('hr-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @php
        $reverb = config('broadcasting.connections.reverb');
        $broadcastEnabled = config('broadcasting.default') === 'reverb' && filled($reverb['key'] ?? null);
    @endphp
    <script id="hr-sidebar-realtime-config" type="application/json">
        {!! json_encode([
            'enabled' => $broadcastEnabled,
            'key' => $reverb['key'] ?? null,
            'host' => $reverb['options']['host'] ?? 'localhost',
            'port' => (int) ($reverb['options']['port'] ?? 8080),
            'scheme' => $reverb['options']['scheme'] ?? 'http',
            'authEndpoint' => url('/broadcasting/auth'),
            'csrfToken' => csrf_token(),
            'pollUrl' => route('hr.sidebar-notifications'),
            'initialCounts' => $hrSidebarCounts ?? [],
            'initialLabels' => $hrSidebarLabels ?? [],
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    </script>
    @if ($broadcastEnabled)
        <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js" defer></script>
    @endif
    <script src="{{ asset('js/tich-hr-sidebar-badges.js') }}" defer></script>
    <script src="{{ asset('js/tich-sidebar.js') }}" defer></script>
@endsection
