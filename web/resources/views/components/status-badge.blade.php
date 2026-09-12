@props([
    'status' => null,
    'label' => null,
    'variant' => 'badge', // badge | status
])

@php
    $tone = \App\Support\StatusTone::for($status);
    $text = $label ?? \App\Support\StatusTone::label($status);
    $class = $variant === 'status'
        ? \App\Support\StatusTone::statusBadgeClass($status)
        : \App\Support\StatusTone::badgeClass($status);
@endphp

<span {{ $attributes->class([$class]) }} data-status="{{ $tone }}" data-status-raw="{{ strtolower((string) $status) }}">{{ $text }}</span>
