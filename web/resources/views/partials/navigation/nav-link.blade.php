@php
    use App\Support\NavIcon;

    $href = $href ?? '#';
    $label = $label ?? '';
    $target = $target ?? '_self';
    $icon = $icon ?? NavIcon::forItem($item ?? ['label' => $label, 'url' => $href]);
    $active = $active ?? false;
    $mobile = $mobile ?? false;
    $class = $mobile ? 'tich-nav-drawer__link' : 'tich-nav__link';

    if ($active) {
        $class .= ' is-active';
    }
@endphp

<a
    href="{{ $href }}"
    class="{{ $class }}"
    target="{{ $target }}"
    @if ($target === '_blank') rel="noopener noreferrer" @endif
>
    <span class="tich-nav__icon" aria-hidden="true">
        @include('partials.navigation.sidebar-icon', ['name' => $icon])
    </span>
    <span class="tich-nav__label">{{ $label }}</span>
</a>
