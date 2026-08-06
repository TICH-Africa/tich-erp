@props([
    'href' => '#',
    'label' => '',
    'active' => false,
    'icon' => null,
])

@php
$classes = 'inline-flex items-center flex-shrink-0 px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors whitespace-nowrap';
if ($active) {
    $classes .= ' bg-green-50 text-green-700';
} else {
    $classes .= ' text-gray-700 hover:bg-gray-50 hover:text-green-700';
}
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        {!! $icon !!}
    @endif
    {{ $label }}
</a>
