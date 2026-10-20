@php
    $label = $label ?? '';
    $badgeKey = $badgeKey ?? null;
    $badge = $badge ?? null;
    $menuLabel = $menuLabel ?? $label;
    $sidebarLabels = $sidebarLabels ?? [];

    if ($badgeKey && $badge === null) {
        $badge = $sidebarLabels[$badgeKey] ?? null;
    }
@endphp

<p @class([
    'tich-admin-sidebar__section',
    'tich-admin-sidebar__section--counted' => $badgeKey || $badge,
])>
    <span>{{ $label }}</span>
    @if ($badgeKey || $badge)
        <span class="tich-admin-sidebar__meta">
            @include('partials.navigation.sidebar-badge', [
                'badgeKey' => $badgeKey,
                'badge' => $badge,
                'menuLabel' => $menuLabel,
                'sidebarLabels' => $sidebarLabels,
            ])
        </span>
    @endif
</p>
