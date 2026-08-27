@props([
    'animation' => 'bottom',
    'delay' => null,
])

@php
    $classes = ['tich-animate'];

    if ($animation === 'left') {
        $classes[] = 'tich-animate--left';
    } elseif ($animation === 'right') {
        $classes[] = 'tich-animate--right';
    } elseif ($animation === 'bottom') {
        $classes[] = 'tich-animate--bottom';
    } elseif ($animation === 'fade') {
        $classes[] = 'tich-animate--fade';
    } elseif ($animation === 'scale') {
        $classes[] = 'tich-animate--scale';
    }

    $style = $delay ? "transition-delay: {$delay}ms;" : '';
@endphp

<div {{ $attributes->merge(['class' => implode(' ', $classes), 'style' => $style]) }}>
    {{ $slot }}
</div>
