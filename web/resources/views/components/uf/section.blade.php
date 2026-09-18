@props([
    'title',
])

<div {{ $attributes->class(['uf-form-section']) }}>
    <div class="uf-section-head">{{ $title }}</div>
    <div class="uf-section-body">
        {{ $slot }}
    </div>
</div>
