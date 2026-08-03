@php
    $href = $item['url'] ?? '#';
    $target = ($item['target'] ?? 'self') === 'blank' ? '_blank' : '_self';
    $class = !empty($mobile) ? 'tich-nav-drawer__link' : 'tich-nav__link';
    $isCareersLink = str_contains($href, '/careers') || ($item['label'] ?? '') === 'Careers';
    $isActive = $isCareersLink && (request()->routeIs('careers.*') || request()->routeIs('vacancies.*'));

    if ($isActive) {
        $class .= ' is-active';
    }
@endphp

<a href="{{ $href }}" class="{{ $class }}" target="{{ $target }}" @if($target === '_blank') rel="noopener noreferrer" @endif>
    {{ $item['label'] }}
</a>

@if (!empty($item['children']))
    <div class="tich-nav__children">
        @foreach ($item['children'] as $child)
            @include('partials.navigation.menu-item', ['item' => $child, 'mobile' => $mobile ?? false])
        @endforeach
    </div>
@endif
