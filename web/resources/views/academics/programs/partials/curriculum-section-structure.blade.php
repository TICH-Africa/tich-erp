<div class="tich-grid tich-grid--2" style="gap:1.5rem; align-items:start;">
    <article class="tich-card">
        @if ($program->isTieredProgram())
            <p class="tich-caption"><span class="tich-badge">Institutional tiered programme</span></p>
            <h2 class="tich-h3 tich-mt-2">Tier ladder &amp; teaching periods</h2>
            <p class="tich-text tich-mt-2">
                One programme code with exit levels (Certificate → Diploma → Advanced Diploma). Semesters are derived from
                each tier’s cumulative months and a fixed semester length — not from “years × terms per year”.
            </p>

            <dl class="tich-mt-4" style="display:grid; grid-template-columns: auto 1fr; gap:0.35rem 1rem; font-size:0.9375rem;">
                <dt class="tich-caption">Format</dt>
                <dd>{{ $formats[$program->curriculum_format] ?? ucfirst($program->curriculum_format ?? 'semester') }}</dd>
                <dt class="tich-caption">Semester length</dt>
                <dd>{{ (int) ($program->tier_semester_months ?? 0) }} month(s)</dd>
                <dt class="tich-caption">Full ladder duration</dt>
                <dd>{{ (int) ($program->duration_months ?? 0) }} month(s)</dd>
                <dt class="tich-caption">Teaching periods</dt>
                <dd><strong>{{ $scheduledPeriods }}</strong> semesters for the top tier</dd>
            </dl>

            @if (! empty($tierPlan))
                <ol class="tich-program-show__tier-list tich-mt-6">
                    @foreach ($tierPlan as $tierRow)
                        @php
                            $tierKey = $tierRow['tier_key'] ?? '';
                            $unitCount = (int) ($unitsCountByTier[$tierKey] ?? 0);
                            $semFrom = (int) ($tierRow['semester_from'] ?? 0);
                            $semTo = (int) ($tierRow['semester_to'] ?? 0);
                            $topMonths = (int) collect($tierPlan)->max('cumulative_months');
                            $barWidth = $topMonths > 0
                                ? min(100, round(((int) ($tierRow['cumulative_months'] ?? 0) / $topMonths) * 100))
                                : 0;
                        @endphp
                        <li class="tich-program-show__tier-item">
                            <div class="tich-program-show__tier-head">
                                <h3 class="tich-h4">{{ $tierRow['label'] ?? ($tierLabels[$tierKey] ?? $tierKey) }}</h3>
                                <span class="tich-caption">
                                    {{ (int) ($tierRow['cumulative_months'] ?? 0) }} mo cumulative
                                    · semesters {{ $semFrom }}–{{ $semTo }}
                                    @if ($unitCount > 0)
                                        · {{ $unitCount }} unit(s) in catalog
                                    @endif
                                </span>
                            </div>
                            <div class="tich-program-show__tier-track" aria-hidden="true">
                                <div class="tich-program-show__tier-track-fill" style="width: {{ $barWidth }}%;"></div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="tich-field-error tich-mt-4">Tier definitions are missing. Configure tiers in ICT programme settings.</p>
            @endif

            <p class="tich-caption tich-mt-6">
                Edit tier months, marketing copy, and fees in
                <a href="{{ route('admin.programs.index') }}" class="tich-link">ICT → Programmes</a>
                ({{ $program->program_code }}). Map units to semesters on the
                <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'semesters', 'intake' => $selectedIntake?->id])) }}" class="tich-link">Semester units</a>
                tab once an intake is selected.
            </p>
        @else
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
                            <p class="tich-caption tich-mt-2">{{ $programYears }} year(s) × {{ $termsPerYear }} term(s)/year. The curriculum, semester and timetable screens list {{ $scheduledPeriods }} semesters - Years 1 to {{ $program->studyYears() }} - so a cohort moving up still has its own year.</p>
                        </div>
                    @endif
                </div>
                @can('academics.write')
                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save structure</button>
                @endcan
            </form>
        @endif
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

        @if ($program->isTieredProgram())
            <p class="tich-caption tich-mt-4">
                Each intake uses the same <strong>{{ $scheduledPeriods }}</strong> semesters across the full tier ladder.
                Applicants choose an exit tier; units must not repeat across tiers.
            </p>
        @else
            <p class="tich-caption tich-mt-4">Each intake gets the same {{ $scheduledPeriods }} {{ $program->usesBlocks() ? 'blocks' : 'semesters' }} - Years 1 to {{ $program->studyYears() }} - because cohorts overlap.</p>
        @endif

        <p class="tich-mt-4">
            <a href="{{ route('departments.academics.programs.curriculum', array_filter(array_merge($hub, ['program' => $program->id, 'section' => 'timetable', 'intake' => $selectedIntake?->id, 'teaching_period' => request()->integer('teaching_period') ?: null]))) }}" class="tich-btn tich-btn-secondary">Open timetable builder</a>
        </p>
    </article>
</div>
