@php
    $label = $label ?? '';
    $icon = $icon ?? 'circle';
    $open = $open ?? false;
    $active = $active ?? false;
    $items = $items ?? [];
    $badgeKey = $badgeKey ?? null;
    $groupBadge = $groupBadge ?? null;

    if ($badgeKey && $groupBadge === null && isset($hrSidebarLabels)) {
        $groupBadge = $hrSidebarLabels[$badgeKey] ?? null;
    }
@endphp

<div @class([
    'tich-admin-sidebar__group',
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
        @if ($badgeKey)
            <span
                class="tich-notification-badge"
                data-hr-sidebar-badge="{{ $badgeKey }}"
                aria-live="polite"
                @if (! $groupBadge) hidden @endif
            >{{ $groupBadge }}</span>
        @endif
        <span class="tich-admin-sidebar__chevron" aria-hidden="true">
            @include('partials.navigation.sidebar-icon', ['name' => 'chevron-down'])
        </span>
    </button>

    <div class="tich-admin-sidebar__subnav" data-sidebar-group-panel @unless($open) hidden @endunless>
        @foreach ($items as $item)
            @include('partials.navigation.sidebar-link', [
                'href' => $item['href'],
                'label' => $item['label'],
                'icon' => $item['icon'] ?? 'circle',
                'active' => $item['active'] ?? false,
                'badgeKey' => $item['badgeKey'] ?? null,
                'badge' => $item['badge'] ?? null,
                'sub' => true,
            ])
        @endforeach
    </div>
</div>
