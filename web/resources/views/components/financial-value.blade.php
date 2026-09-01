@props([
    'value',
    'prefix' => 'KES ',
    'suffix' => '',
])

<span {{ $attributes->merge(['class' => 'tich-financial-value']) }}>
    <span class="tich-financial-value__content">{{ $prefix }}{{ $value }}{{ $suffix }}</span>
    <button type="button" class="tich-financial-value__toggle" aria-label="Show amount" title="Show/hide amount">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    </button>
</span>
