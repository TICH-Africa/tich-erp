@props([
    'title',
    'meta' => null,
])

<div {{ $attributes->merge(['class' => 'tich-page-toolbar']) }}>
    <div class="tich-page-toolbar__main">
        <h1 class="tich-page-toolbar__title">{{ $title }}</h1>
        @if ($meta)
            <span class="tich-page-toolbar__meta">{{ $meta }}</span>
        @endif
    </div>

    @isset($actions)
        <div class="tich-page-toolbar__actions">
            {{ $actions }}
        </div>
    @endisset

    @isset($filters)
        <div class="tich-page-toolbar__filters">
            {{ $filters }}
        </div>
    @endisset
</div>
