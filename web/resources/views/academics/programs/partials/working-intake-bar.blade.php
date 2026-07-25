@php
    $intakeSwitchParams = array_filter(array_merge($hub, [
        'program' => $program->id,
        'section' => $section,
    ]));
    $sectionLabels = \App\Services\ProgramCurriculumService::curriculumSections();
    $currentSectionLabel = $sectionLabels[$section] ?? ucfirst($section);
@endphp

@if ($intakes->isNotEmpty())
    <section class="tich-working-intake @if (! $selectedIntake) tich-working-intake--unset @endif tich-mb-6">
        <form method="GET" action="{{ route('departments.academics.programs.curriculum', $intakeSwitchParams) }}" class="tich-working-intake__form" id="working-intake-form">
            @foreach (request()->except(['intake']) as $name => $value)
                @if (is_scalar($value) && $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="tich-working-intake__copy">
                <p class="tich-caption">Working intake</p>
                <p class="tich-text" style="margin:0;">
                    @if ($selectedIntake)
                        You are working on <strong>{{ $selectedIntake->intakeLabel() }}</strong>
                        for {{ $program->program_code }}.
                        Current page: {{ $currentSectionLabel }}.
                    @else
                        Select the intake you are working on before changing semester units, timetables, applications, or enrolled students.
                    @endif
                </p>
            </div>

            <div class="tich-working-intake__controls">
                <div class="tich-form-group" style="margin:0;">
                    <label for="working-intake-select" class="tich-label">Intake</label>
                    <select
                        id="working-intake-select"
                        name="intake"
                        class="tich-input"
                        required
                        onchange="if (this.value) this.form.submit()"
                    >
                        <option value="" disabled @selected(! $selectedIntake)>Choose intake…</option>
                        @foreach ($intakes as $intakeOption)
                            <option value="{{ $intakeOption->id }}" @selected($selectedIntake?->id === $intakeOption->id)>
                                {{ $intakeOption->intakeLabel() }}
                                @if ($intakeOption->status !== 'published')
                                    ({{ ucfirst($intakeOption->status) }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if (! $selectedIntake)
            <p class="tich-working-intake__warning">
                No intake is selected. Intake-specific work is blocked until you choose one above.
            </p>
        @endif
    </section>
@endif
