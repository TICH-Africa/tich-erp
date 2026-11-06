@php
    $timetableDraftsByKind = $timetableDraftsByKind ?? collect();
    $periodKey = $timetableTeachingPeriod.':';
    $semesterPeriod = $periodDates->get($periodKey);
    $viewKinds = ['lesson' => 'Lesson timetable', 'exam' => 'Exam timetable'];
    $timetableViewParams = fn (string $kind) => array_merge($curriculumParams, array_filter([
        'section' => 'timetable',
        'teaching_period' => $timetableTeachingPeriod,
        'timetable_kind' => $kind,
        'timetable_edit' => 1,
    ]));
    $ttTerms = max(1, (int) $termsPerYear);
@endphp

<x-page-toolbar
    title="Timetable - {{ $selectedIntake->intakeLabel() }}"
    meta="Published lesson and exam timetables for this semester"
    class="tich-mb-6"
/>

<article class="tich-card tich-mb-8" id="timetable-display">
    @if ($program->usesBlocks())
        <form method="GET" action="{{ route('departments.academics.programs.curriculum', array_merge($curriculumParams, ['section' => 'timetable'])) }}" class="tich-mt-2" style="max-width:22rem;">
            <input type="hidden" name="section" value="timetable">
            <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
            @if ($learningDepartment)
                <input type="hidden" name="learning_department" value="{{ $learningDepartment->id }}">
            @endif
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Block</label>
                <select name="teaching_period" class="tich-input" onchange="this.form.submit()">
                    @foreach (range(1, $timetablePeriods ?? $totalTeachingPeriods) as $periodNumber)
                        <option value="{{ $periodNumber }}" @selected($timetableTeachingPeriod === $periodNumber)>{{ $program->periodLabel($periodNumber) }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    @else
        @php
            $ttYearCounts = collect($timetableYearCounts ?? [])->mapWithKeys(fn ($count, $year) => [(int) $year => (int) $count]);
            $ttYearsToShow = max(
                (int) $program->studyYears(),
                (int) $ttYearCounts->keys()->max(),
                $program->yearOfPeriod($timetableTeachingPeriod),
                (int) ceil(($timetablePeriods ?? $totalTeachingPeriods) / $ttTerms),
            );
        @endphp
        <form method="GET" action="{{ route('departments.academics.programs.curriculum', array_merge($curriculumParams, ['section' => 'timetable'])) }}" class="tich-mt-2" style="max-width:22rem;">
            <input type="hidden" name="section" value="timetable">
            <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
            @if ($learningDepartment)
                <input type="hidden" name="learning_department" value="{{ $learningDepartment->id }}">
            @endif
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label" for="tt-view-year-semester">Year and semester</label>
                <select id="tt-view-year-semester" name="teaching_period" class="tich-input" onchange="this.form.submit()">
                    @foreach (range(1, $ttYearsToShow) as $yearNumber)
                        @php $yearStudents = (int) ($ttYearCounts[$yearNumber] ?? 0); @endphp
                        <optgroup label="Year {{ $yearNumber }} - {{ $yearStudents > 0 ? $yearStudents.' student'.($yearStudents === 1 ? '' : 's') : 'no students yet' }}">
                            @foreach (range(1, $ttTerms) as $termNumber)
                                @php $periodNumber = (($yearNumber - 1) * $ttTerms) + $termNumber; @endphp
                                <option value="{{ $periodNumber }}" @selected($timetableTeachingPeriod === $periodNumber)>
                                    Year {{ $yearNumber }} · Semester {{ $termNumber }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        </form>
    @endif

    @if ($semesterPeriod?->scheduleLabel())
        <div class="tich-inset-panel tich-mt-4">
            <p class="tich-text" style="margin:0 0 0.35rem;"><strong>{{ $program->periodLabel($timetableTeachingPeriod) }}</strong> · {{ $semesterPeriod->scheduleLabel() }}</p>
            @if ($semesterPeriod->effectiveLearningStart() || $semesterPeriod->learning_end_date)
                <p class="tich-caption" style="margin:0 0 0.25rem;">Learning: {{ $semesterPeriod->effectiveLearningStart()?->format('d M Y') ?? '-' }} - {{ $semesterPeriod->learning_end_date?->format('d M Y') ?? '-' }}</p>
            @endif
            @if ($semesterPeriod->exam_start_date || $semesterPeriod->effectiveExamEnd())
                <p class="tich-caption" style="margin:0;">Exams: {{ $semesterPeriod->exam_start_date?->format('d M Y') ?? '-' }} - {{ $semesterPeriod->effectiveExamEnd()?->format('d M Y') ?? '-' }}</p>
            @endif
        </div>
    @endif

    <p class="tich-text tich-mt-4" style="margin-bottom:0;">
        Timetables appear here as documents. Use <strong>Edit</strong> to open the builder or upload workspace for each type.
    </p>
</article>

@foreach ($viewKinds as $kindKey => $kindLabel)
    @php
        $kindTimetable = $timetableDraftsByKind[$kindKey] ?? null;
        $pdfUrl = $kindTimetable
            ? route('departments.academics.programs.timetable.pdf', array_merge($hub, [
                'program' => $program->id,
                'timetable' => $kindTimetable->id,
                'inline' => 1,
            ]))
            : null;
        $canEdit = auth()->user()?->can('academics.write') ?? false;
    @endphp
    <article class="tich-card timetable-view-card tich-mb-8" id="timetable-view-{{ $kindKey }}">
        <div style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-start; justify-content:space-between;">
            <div>
                <h2 class="tich-h3">{{ $kindLabel }}</h2>
                @if ($kindTimetable)
                    <p class="tich-caption tich-mt-1" style="margin-bottom:0;">
                        {{ $kindTimetable->displayTitle() }} · {{ ucfirst($kindTimetable->status) }}
                        @if ($kindTimetable->hasUploadedDocument())
                            · Uploaded document
                        @elseif ($kindTimetable->hasViewableDocument())
                            · Built on platform
                        @else
                            · Draft (no classes yet)
                        @endif
                    </p>
                @else
                    <p class="tich-caption tich-mt-1" style="margin-bottom:0;">Not created for {{ $program->periodLabel($timetableTeachingPeriod) }}.</p>
                @endif
            </div>
            @if ($canEdit)
                <a
                    href="{{ route('departments.academics.programs.curriculum', $timetableViewParams($kindKey)) }}#timetable-edit"
                    class="tich-btn tich-btn-primary"
                >
                    {{ $kindTimetable ? 'Edit timetable' : 'Create timetable' }}
                </a>
            @endif
        </div>

        @if ($kindTimetable && $kindTimetable->hasViewableDocument())
            @if ($kindTimetable->hasUploadedDocument())
                @include('partials.timetable-uploaded-document', [
                    'timetable' => $kindTimetable,
                    'pdfUrl' => route('departments.academics.programs.timetable.pdf', array_merge($hub, [
                        'program' => $program->id,
                        'timetable' => $kindTimetable->id,
                    ])),
                    'fileLabel' => $kindTimetable->source_name,
                ])
            @else
                <p class="tich-caption tich-mt-4 tich-mb-2">Preview generated from the timetable builder (PDF).</p>
                <div class="doc-viewer doc-viewer--single doc-viewer--timetable">
                    <div class="doc-viewer__panel doc-viewer__panel--full">
                        <div class="doc-viewer__stage">
                            <iframe
                                src="{{ $pdfUrl }}"
                                title="{{ $kindTimetable->displayTitle() }}"
                                class="doc-viewer__frame"
                            ></iframe>
                        </div>
                    </div>
                </div>
                <p class="tich-caption tich-mt-2">
                    <a href="{{ route('departments.academics.programs.timetable.pdf', array_merge($hub, ['program' => $program->id, 'timetable' => $kindTimetable->id])) }}" class="tich-link">Download PDF</a>
                    ·
                    <a href="{{ route('departments.academics.programs.timetable.print', array_merge($hub, ['program' => $program->id, 'timetable' => $kindTimetable->id])) }}" target="_blank" class="tich-link">Print view</a>
                </p>
            @endif
        @elseif ($kindTimetable)
            <p class="tich-text tich-mt-4">This timetable is still empty. Open edit to add classes in the builder or upload a document.</p>
        @else
            <p class="tich-text tich-mt-4">No {{ strtolower($kindLabel) }} for this semester yet.</p>
        @endif
    </article>
@endforeach
