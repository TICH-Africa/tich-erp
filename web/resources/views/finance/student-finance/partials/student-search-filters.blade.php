@php
    $action = $action ?? url()->current();
    $search = $search ?? '';
    $programId = (int) ($programId ?? 0);
    $hasFilters = $search !== '' || $programId > 0;
@endphp

<div class="tich-live-filter-bar">
    <form method="get" action="{{ $action }}" class="tich-live-filter-bar__form" data-live-filter>
        @include('partials.search-field', [
            'placeholder' => $searchPlaceholder ?? 'Search name, reg no, programme…',
            'value' => $search,
            'hideButton' => true,
        ])
        <select name="program_id" class="tich-input tich-input--compact" aria-label="Programme">
            <option value="0">All programmes</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected($programId === (int) $program->id)>
                    {{ $program->program_code ?? '' }}@if ($program->program_code && $program->program_name) - @endif{{ $program->program_name }}
                </option>
            @endforeach
        </select>
        @if ($hasFilters)
            <a href="{{ $action }}" class="tich-btn tich-btn-ghost tich-btn--sm tich-live-filter-bar__clear">Clear</a>
        @endif
    </form>
</div>
