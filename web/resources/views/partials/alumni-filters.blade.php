{{-- Alumni register filters. Expects: $filters, $years, $programs, $action --}}
<form method="GET" action="{{ $action }}" class="tich-page-toolbar__filters-form" data-live-filter>
    @include('partials.search-field', ['placeholder' => 'Name, admission no., email…', 'value' => $filters['search'] ?? ''])
    <select name="graduation_year" class="tich-input tich-input--compact" title="Graduation year" aria-label="Graduation year">
        <option value="">Any graduation year</option>
        @foreach ($years as $year)
            <option value="{{ $year }}" @selected((int) ($filters['graduation_year'] ?? 0) === $year)>{{ $year }}</option>
        @endforeach
    </select>
    <select name="program_id" class="tich-input tich-input--compact" title="Programme" aria-label="Programme">
        <option value="">Any programme</option>
        @foreach ($programs as $program)
            <option value="{{ $program->id }}" @selected((int) ($filters['program_id'] ?? 0) === $program->id)>
                {{ $program->program_code ? $program->program_code.' - ' : '' }}{{ $program->program_name }}
            </option>
        @endforeach
    </select>
</form>
