@props([
    'path',
    'defer' => true,
])
<script
    src="{{ \App\Support\PublicAsset::url($path) }}"
    type="text/javascript"
    @if ($defer) defer @endif
    {{ $attributes }}
></script>
