@php
    $href = $item['url'] ?? '#';
    $target = ($item['target'] ?? 'self') === 'blank' ? '_blank' : '_self';
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
@endphp

@include('partials.navigation.nav-link', [
    'href' => $href,
    'label' => $item['label'],
    'target' => $target,
    'icon' => \App\Support\NavIcon::forItem($item),
    'active' => $isActive,
    'mobile' => $mobile ?? false,
    'item' => $item,
])

@if (! empty($item['children']))
    <div class="{{ ! empty($mobile) ? 'tich-nav-drawer__children' : 'tich-nav__children' }}">
        @foreach ($item['children'] as $child)
            @include('partials.navigation.menu-item', ['item' => $child, 'mobile' => $mobile ?? false])
        @endforeach
    </div>
@endif
