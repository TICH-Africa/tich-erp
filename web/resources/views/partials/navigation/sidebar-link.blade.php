@php
    $href = $href ?? '#';
    $label = $label ?? '';
    $icon = $icon ?? 'circle';
    $active = $active ?? false;
    $muted = $muted ?? false;
    $badge = $badge ?? null;
@endphp

<a href="{{ $href }}" @class([
    'tich-admin-sidebar__link',
    'is-active' => $active,
    'is-muted' => $muted,
])>
    <span class="tich-admin-sidebar__icon">
        @include('partials.navigation.sidebar-icon', ['name' => $icon])
    </span>
    <span class="tich-admin-sidebar__label">{{ $label }}</span>
    @if ($badge)
        <span class="tich-notification-badge" aria-label="{{ $badge }} pending">{{ $badge }}</span>
    @endif
</a>
