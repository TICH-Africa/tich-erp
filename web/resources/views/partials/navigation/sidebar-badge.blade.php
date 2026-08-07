@php
    $badgeKey = $badgeKey ?? null;
    $badge = $badge ?? null;
    $sub = $sub ?? false;
    $menuLabel = $menuLabel ?? null;

    if ($badgeKey && $badge === null && isset($hrSidebarLabels)) {
        $badge = $hrSidebarLabels[$badgeKey] ?? null;
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
            data-hr-sidebar-badge="{{ $badgeKey }}"
            aria-live="polite"
        @endif
        @if ($menuLabel)
            data-hr-sidebar-badge-label="{{ $menuLabel }}"
        @endif
        @if (! $badge) hidden @endif
        @if ($badge && ! $badgeKey)
            aria-label="{{ $badge }} pending"
        @endif
    >{{ $badge }}</span>
@endif
