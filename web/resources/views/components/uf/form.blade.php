@props([
    'ref' => null,
    'title' => null,
    'badge' => null,
    'action' => null,
    'method' => 'POST',
    'enctype' => null,
    'wide' => true,
])

@php
    $formMethod = strtoupper($method) === 'GET' ? 'GET' : 'POST';
    $spoof = in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE'], true) ? strtoupper($method) : null;
@endphp

<div {{ $attributes->class(['uf-form']) }}>
    <form
        method="{{ $formMethod }}"
        @if ($action) action="{{ $action }}" @endif
        @if ($enctype) enctype="{{ $enctype }}" @endif
        data-uf="ready"
    >
        @unless ($formMethod === 'GET')
            @csrf
        @endunless
        @if ($spoof)
            @method($spoof)
        @endif

        @if ($ref || $title || $badge || isset($bar))
            <div class="uf-amount-bar">
                <div>
                    @if ($ref)
                        <div class="uf-amount-bar__ref">{{ $ref }}</div>
                    @endif
                    @if ($title)
                        <div class="uf-amount-bar__sum">{{ $title }}</div>
                    @endif
                    {{ $bar ?? '' }}
                </div>
                @if ($badge)
                    <span class="uf-badge">{{ $badge }}</span>
                @endif
            </div>
        @endif

        {{ $slot }}
    </form>
</div>
