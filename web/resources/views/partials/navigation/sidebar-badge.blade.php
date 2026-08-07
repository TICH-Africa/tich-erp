@php
    $badgeKey = $badgeKey ?? null;
    $badge = $badge ?? null;
    $sub = $sub ?? false;
    $menuLabel = $menuLabel ?? null;
    $sidebarLabels = $sidebarLabels ?? $hrSidebarLabels ?? [];

    if ($badgeKey && $badge === null && isset($sidebarLabels)) {
        $badge = $sidebarLabels[$badgeKey] ?? null;
    }
@endphp

@if ($badgeKey || $badge)
    <span
        @class([
            'tich-notification-badge',
            'tich-notification-badge--sidebar',
            'tich-notification-badge--sub' => $sub,
        ])
        @if ($badgeKey)
            data-sidebar-badge="{{ $badgeKey }}"
            aria-live="polite"
        @endif
        @if ($menuLabel)
            data-sidebar-badge-label="{{ $menuLabel }}"
        @endif
        @if (! $badge) hidden @endif
        @if ($badge && ! $badgeKey)
            aria-label="{{ $badge }} pending"
        @endif
    >{{ $badge }}</span>
@endif
