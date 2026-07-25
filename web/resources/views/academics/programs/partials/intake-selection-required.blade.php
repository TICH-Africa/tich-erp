@php
    $sectionLabels = \App\Services\ProgramCurriculumService::curriculumSections();
    $currentSectionLabel = $sectionLabels[$section] ?? ucfirst($section);
    $intakeListParams = array_filter(array_merge($hub, [
        'program' => $program->id,
        'section' => 'intakes',
    ]));
@endphp

<article class="tich-card">
    <div class="tich-notice tich-notice--warning tich-mb-4">
        <p class="tich-text" style="margin:0;">
            <strong>Intake required.</strong>
            {{ $currentSectionLabel }} is tied to a specific intake cohort. Select the intake you are working on before continuing.
        </p>
    </div>

    <p class="tich-text">
        This prevents semester units, timetables, applications, and enrolled students from being viewed or edited against the wrong cohort.
    </p>

    @if ($intakes->isEmpty())
        <p class="tich-text tich-mt-4">
            No intakes exist for this programme yet.
            <a href="{{ route('departments.academics.programs.curriculum', $intakeListParams) }}" class="tich-link">Create an intake</a> first.
        </p>
    @else
        <form method="GET" action="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => $section])) }}" class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
            <div class="tich-form-group" style="margin:0; min-width:16rem;">
                <label for="required-intake-select" class="tich-label">Select working intake</label>
                <select
                    id="required-intake-select"
                    name="intake"
                    class="tich-input"
                    required
                    onchange="if (this.value) this.form.submit()"
                >
                    <option value="" disabled selected>Choose intake…</option>
                    @foreach ($intakes as $intakeOption)
                        <option value="{{ $intakeOption->id }}">
                            {{ $intakeOption->intakeLabel() }}
                            @if ($intakeOption->status !== 'published')
                                ({{ ucfirst($intakeOption->status) }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('departments.academics.programs.curriculum', $intakeListParams) }}" class="tich-link">Manage intakes</a>
        </form>
    @endif
</article>
