@php
    $action = $action ?? url()->current();
    $search = $search ?? '';
    $semesterId = (int) ($semesterId ?? 0);
    $academicYearId = (int) ($academicYearId ?? 0);
    $programId = (int) ($programId ?? 0);
    $showProgram = $showProgram ?? false;
    $searchPlaceholder = $searchPlaceholder ?? 'Search by name, reg no, programme…';
    $hasFilters = $search !== '' || $semesterId > 0 || $academicYearId > 0 || ($showProgram && $programId > 0);
@endphp

<div class="tich-live-filter-bar">
    <form method="get" action="{{ $action }}" class="tich-live-filter-bar__form" data-live-filter>
        @include('partials.search-field', [
            'placeholder' => $searchPlaceholder,
            'value' => $search,
            'hideButton' => true,
        ])
        <select name="semester_id" class="tich-input tich-input--compact" aria-label="Semester">
            <option value="0">All semesters</option>
            @foreach ($allSemesters as $sem)
                <option value="{{ $sem->id }}" @selected($semesterId === (int) $sem->id)>{{ $sem->displayLabel() }}</option>
            @endforeach
        </select>
        <select name="academic_year_id" class="tich-input tich-input--compact" aria-label="Academic year">
            <option value="0">All academic years</option>
            @foreach ($allAcademicYears as $year)
                <option value="{{ $year->id }}" @selected($academicYearId === (int) $year->id)>{{ $year->year_label ?? $year->year }}</option>
            @endforeach
        </select>
        @if ($showProgram && isset($allPrograms))
            <select name="program_id" class="tich-input tich-input--compact" aria-label="Programme">
                <option value="0">All programmes</option>
                @foreach ($allPrograms as $program)
                    <option value="{{ $program->id }}" @selected($programId === (int) $program->id)>
                        {{ $program->program_code }} - {{ $program->program_name }}
                    </option>
                @endforeach
            </select>
        @endif
        @if ($hasFilters)
            <a href="{{ $action }}" class="tich-btn tich-btn-ghost tich-btn--sm tich-live-filter-bar__clear">Clear</a>
        @endif
    </form>
</div>
