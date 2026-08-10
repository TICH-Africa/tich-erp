@props([
    'src' => null,
    'alt' => '',
    'eager' => false,
])

<img
    @if ($eager)
        src="{{ $src }}"
        data-lazy="eager"
        fetchpriority="high"
    @else
        data-src="{{ $src }}"
        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
    @endif
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => 'tich-lazy-media']) }}
/>
