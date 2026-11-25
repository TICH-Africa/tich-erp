@php
    $href = $item['url'] ?? '#';
    $target = ($item['target'] ?? 'self') === 'blank' ? '_blank' : '_self';
    $children = $item['children'] ?? [];
    $isMobile = ! empty($mobile);
    $path = parse_url($href, PHP_URL_PATH) ?? '';
    $fragment = parse_url($href, PHP_URL_FRAGMENT);

    $isActive = false;

    if ($path && $path !== '/' && ! str_starts_with($href, '#')) {
        $trimmed = ltrim($path, '/');
        $isActive = request()->is($trimmed) || request()->is($trimmed.'/*');
    } elseif ($path === '/' && request()->routeIs('home')) {
        $isActive = empty($fragment);
    } elseif ($fragment && request()->routeIs('home')) {
        $isActive = false;
    }

    if (! $isActive && str_contains($href, '/careers')) {
        $isActive = request()->routeIs('careers.*') || request()->routeIs('vacancies.*');
    }

    if (! $isActive && str_contains($href, '/programs')) {
        $isActive = request()->routeIs('programs.*');
    }

    if (! $isActive && str_contains($href, '/e-fundi')) {
        $isActive = request()->routeIs('e-fundi.*');
    }

    if (! $isActive && str_contains($href, '/mobile-app')) {
        $isActive = request()->routeIs('mobile-app.*');
    }

    // The parent stays lit while one of its own links is the page being read.
    $childActive = collect($children)->contains(function ($child): bool {
        $childHref = $child['url'] ?? '#';
        $childPath = parse_url($childHref, PHP_URL_PATH) ?? '';

        if ($childPath === '' || str_starts_with($childHref, '#')) {
            return false;
        }

        $trimmed = ltrim($childPath, '/');

        return request()->is($trimmed) || request()->is($trimmed.'/*');
    });

    $isActive = $isActive || $childActive;
    $submenuId = $children ? 'nav-submenu-'.\Illuminate\Support\Str::slug($item['label'] ?? 'links') : null;
@endphp

@include('partials.navigation.nav-link', [
    'href' => $href,
    'label' => $item['label'],
    'target' => $target,
    'icon' => \App\Support\NavIcon::forItem($item),
    'active' => $isActive,
    'mobile' => $isMobile,
    'item' => $item,
])

@if ($children && ! $isMobile)
    <button type="button" class="tich-nav__link tich-nav__submenu-toggle" data-nav-submenu-toggle aria-expanded="false" aria-controls="{{ $submenuId }}" aria-label="Show more {{ $item['label'] }} links">
        <span class="tich-nav__chevron" aria-hidden="true">
            @include('partials.navigation.sidebar-icon', ['name' => 'chevron-down'])
        </span>
    </button>
@endif

@if (! empty($children))
    <div
        id="{{ $submenuId }}"
        class="{{ $isMobile ? 'tich-nav-drawer__children' : 'tich-nav__children tich-nav__submenu' }}"
        @if (! $isMobile) data-nav-submenu hidden @endif
    >
        @foreach ($children as $child)
            @include('partials.navigation.menu-item', ['item' => $child, 'mobile' => $isMobile])
        @endforeach
    </div>
@endif
