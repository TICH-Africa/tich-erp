@php
    $label = $label ?? '';
    $icon = $icon ?? 'circle';
    $open = $open ?? false;
    $items = $items ?? [];
    $badgeKey = $badgeKey ?? null;
    $groupBadge = $groupBadge ?? null;
    $menuLabel = $menuLabel ?? $label;
    $sidebarLabels = $sidebarLabels ?? [];
@endphp

<div @class([
    'tich-admin-sidebar__group',
    'tich-admin-sidebar__subgroup',
    'is-open' => $open,
    'is-active' => $active,
]) data-sidebar-group>
    <button
        type="button"
        class="tich-admin-sidebar__group-toggle"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        data-sidebar-group-toggle
    >
        <span class="tich-admin-sidebar__icon">
            @include('partials.navigation.sidebar-icon', ['name' => $icon])
        </span>
        <span class="tich-admin-sidebar__label">{{ $label }}</span>
        <span class="tich-admin-sidebar__meta">
            @if ($badgeKey)
                @include('partials.navigation.sidebar-badge', [
                    'badgeKey' => $badgeKey,
                    'badge' => $groupBadge,
                    'menuLabel' => $menuLabel,
                    'sidebarLabels' => $sidebarLabels,
                ])
            @endif
            <span class="tich-admin-sidebar__chevron" aria-hidden="true">
                @include('partials.navigation.sidebar-icon', ['name' => 'chevron-down'])
            </span>
        </span>
    </button>

    <div class="tich-admin-sidebar__subnav tich-admin-sidebar__subnav--nested" data-sidebar-group-panel @unless($open) hidden @endunless>
        @foreach ($items as $item)
            @include('partials.navigation.sidebar-link', [
                'href' => $item['href'],
                'label' => $item['label'],
                'icon' => $item['icon'] ?? 'circle',
                'active' => $item['active'] ?? false,
                'badgeKey' => $item['badgeKey'] ?? null,
                'badge' => $item['badge'] ?? null,
                'menuLabel' => $item['menuLabel'] ?? ($item['label'] ?? null),
                'sub' => true,
                'sidebarLabels' => $sidebarLabels,
            ])
        @endforeach
    </div>
</div>
