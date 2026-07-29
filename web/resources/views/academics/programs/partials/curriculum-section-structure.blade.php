<div class="tich-grid tich-grid--2" style="gap:1.5rem; align-items:start;">
    <article class="tich-card">
        <h2 class="tich-h3">Programme structure</h2>
        <p class="tich-text tich-mt-2">Set course length and how many semesters run in each academic year. This defines the teaching periods copied for every intake.</p>

        <form method="POST" action="{{ route('departments.academics.programs.update-format', array_merge($hub, ['program' => $program->id])) }}" class="tich-mt-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="return_section" value="structure">
            <div class="tich-form-group">
                <label class="tich-label">Curriculum format</label>
                <select name="curriculum_format" class="tich-input" required>
                    @foreach ($formats as $key => $label)
                        <option value="{{ $key }}" @selected(old('curriculum_format', $program->curriculum_format ?? 'trimester') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-grid tich-grid--3 tich-mt-4" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label">Course length (months)</label>
                    <input type="number" name="duration_months" class="tich-input" min="1" max="120" value="{{ old('duration_months', $program->duration_months ?? 12) }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Semesters / terms per academic year</label>
                    <input type="number" name="semester_count" class="tich-input" min="1" max="6" value="{{ old('semester_count', $program->semester_count ?: $program->termsPerYear()) }}" required>
                    <p class="tich-caption tich-mt-2">e.g. 2 or 3 semesters per academic year</p>
                </div>
                @if ($program->usesBlocks() || in_array(old('curriculum_format', $program->curriculum_format), ['block'], true))
                    <div class="tich-form-group">
                        <label class="tich-label">Nursing blocks</label>
                        <input type="number" name="block_count" class="tich-input" min="1" max="10" value="{{ old('block_count', $program->block_count ?: $blocks->count() ?: 4) }}">
                    </div>
                @else
                    <div class="tich-form-group">
                        <label class="tich-label">Total teaching periods</label>
                        <input type="text" class="tich-input" value="{{ $totalTeachingPeriods }}" disabled>
                        <p class="tich-caption tich-mt-2">{{ $programYears }} year(s) × {{ $termsPerYear }} term(s)/year</p>
                    </div>
                @endif
            </div>
            @can('academics.write')
                <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save structure</button>
            @endcan
        </form>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Published intake</h2>
        @if ($publishedVersion)
            <p class="tich-text tich-mt-4">
                <strong>{{ $publishedVersion->intakeLabel() }}</strong>
                · {{ $publishedVersion->items->count() }} units
                · {{ $publishedVersion->published_at?->format('d M Y') }}
            </p>
        @else
            <p class="tich-caption tich-mt-4">No published intake curriculum yet.</p>
        @endif

        <p class="tich-caption tich-mt-4">Each intake gets the same {{ $totalTeachingPeriods }} {{ $program->usesBlocks() ? 'blocks' : 'semesters' }} because cohorts overlap.</p>

        <p class="tich-mt-4">
            <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'timetable', 'intake' => $selectedIntake?->id])) }}" class="tich-btn tich-btn-secondary">Open timetable builder</a>
        </p>
    </article>
</div>
