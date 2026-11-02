@props([
    'tierKey' => null,
    'as' => 'text',
])

@php
    $tierKey = filled($tierKey) ? (string) $tierKey : null;
    $label = $tierKey ? (config('tich-program-tiers.tier_labels.'.$tierKey) ?? $tierKey) : null;
@endphp

@if ($label)
    @if ($as === 'badge')
        <span {{ $attributes->merge(['class' => 'tich-badge']) }}>{{ $label }}</span>
    @else
        <span {{ $attributes }}>{{ $label }}</span>
    @endif
@endif
