@can('academics.write')
    @php
        // A GET form replaces the action's query string, so carry the section state across.
        $campusFilterState = collect($curriculumParams)
            ->except(['program', 'lecturer_campus'])
            ->filter(fn ($value) => $value !== null && $value !== '');
    @endphp

    <form method="GET" action="{{ route('departments.academics.programs.curriculum', $curriculumParams) }}" class="tich-flex-wrap tich-mt-4" style="gap:0.75rem; align-items:flex-end;">
        @foreach ($campusFilterState as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        <div class="tich-form-group" style="margin:0; min-width:13rem;">
            <label for="lecturer_campus" class="tich-label">Lecturers from</label>
            <select id="lecturer_campus" name="lecturer_campus" class="tich-select">
                <option value="">All campuses</option>
                @foreach ($allocationCampuses as $campus)
                    <option value="{{ $campus->id }}" @selected($lecturerCampusId == $campus->id)>{{ $campus->campus_name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="tich-btn tich-btn-secondary">Apply</button>

        @if ($lecturerCampusId)
            <a href="{{ route('departments.academics.programs.curriculum', $campusFilterState->add('program', $program->id)->all()) }}" class="tich-link">Show all campuses</a>
        @endif

        <p class="tich-text" style="margin:0 0 0.15rem auto; font-size:0.8125rem;">
            {{ $allocationStaffList->count() }} {{ str('lecturer')->plural($allocationStaffList->count()) }} to choose from
            @if ($allocationStaffList->whereNull('campus_id')->isNotEmpty())
                · {{ $allocationStaffList->whereNull('campus_id')->count() }} have no campus on their staff profile
            @endif
        </p>
    </form>
@endcan
