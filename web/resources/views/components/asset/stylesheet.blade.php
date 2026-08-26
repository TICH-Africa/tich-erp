@props([
    'path',
    'media' => 'all',
])
<link
    rel="stylesheet"
    type="text/css"
    media="{{ $media }}"
    href="{{ \App\Support\PublicAsset::url($path) }}"
    {{ $attributes }}
>
