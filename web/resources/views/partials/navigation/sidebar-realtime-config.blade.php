@php
    $reverb = config('broadcasting.connections.reverb');
    $broadcastEnabled = filled($sidebarBroadcastChannel ?? null)
        && ($sidebarBroadcastEnabled ?? true)
        && config('broadcasting.default') === 'reverb'
        && filled($reverb['key'] ?? null);
@endphp
<script id="sidebar-realtime-config" type="application/json">
    {!! json_encode([
        'sidebarId' => $sidebarId ?? null,
        'pollUrl' => $sidebarPollUrl ?? null,
        'initialCounts' => $sidebarCounts ?? [],
        'initialLabels' => $sidebarLabels ?? [],
        'menuLabels' => $sidebarMenuLabels ?? [],
        'broadcast' => [
            'enabled' => $broadcastEnabled,
            'channel' => $sidebarBroadcastChannel ?? null,
            'event' => $sidebarBroadcastEvent ?? '.sidebar.counts.updated',
            'key' => $reverb['key'] ?? null,
            'host' => $reverb['options']['host'] ?? 'localhost',
            'port' => (int) ($reverb['options']['port'] ?? 8080),
            'scheme' => $reverb['options']['scheme'] ?? 'http',
            'authEndpoint' => url('/broadcasting/auth'),
            'csrfToken' => csrf_token(),
        ],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
@if ($broadcastEnabled)
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js" defer></script>
@endif
<script src="{{ \App\Support\PublicAsset::url('js/tich-sidebar-badges.js') }}" type="text/javascript" defer></script>
