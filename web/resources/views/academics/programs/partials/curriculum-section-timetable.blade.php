@if (! $selectedIntake)
    <article class="tich-card">
        <p class="tich-text">Select or <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">create an intake</a> before building a timetable.</p>
    </article>
@else
    @php
        $timetableDraftsByKind = $timetableDraftsByKind ?? collect();
        $templateService = app(\App\Services\TimetableTemplateService::class);
        $schedulingService = app(\App\Services\TimetableSchedulingService::class);
        if (in_array($timetableKind, ['exam', 'supplementary'], true)) {
            $templateService->ensureKindSegments($timetableTemplate, $timetableKind);
            $timetableTemplate->load('segments');
        }
        $timetableParams = fn (string $kind = null, ?int $period = null) => array_merge($curriculumParams, array_filter([
            'section' => 'timetable',
            'teaching_period' => $period ?? $timetableTeachingPeriod,
            'timetable_kind' => $kind ?? $timetableKind,
        ]));
        $slotsForKind = $schedulingService->scheduleSegmentsForKind($timetableTemplate, $timetableKind);
        $kindSlotRows = $slotsForKind->map(fn ($s) => [
            'label' => $s->label,
            'start_time' => substr((string) $s->start_time, 0, 5),
            'end_time' => substr((string) $s->end_time, 0, 5),
        ])->all();
        if ($kindSlotRows === [] && in_array($timetableKind, ['exam', 'supplementary'], true)) {
            $kindSlotRows = collect(\App\Services\TimetableTemplateService::defaultSegmentsForKind($timetableKind))
                ->map(fn ($row) => [
                    'label' => $row['label'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                ])->all();
        }
        $lessonSegmentRows = $timetableTemplate->segments
            ->filter(fn ($s) => in_array($s->segment_type, ['lesson', 'break'], true))
            ->map(fn ($s) => [
                'label' => $s->label,
                'start_time' => substr((string) $s->start_time, 0, 5),
                'end_time' => substr((string) $s->end_time, 0, 5),
                'segment_type' => $s->segment_type,
            ])->all();
        $activeDays = $timetableTemplate->days->where('is_active', 1)->pluck('day_of_week')->map(fn ($d) => (int) $d)->all();
        $timetableConflicts = $timetableDraft
            ? $schedulingService->detectConflicts($timetableDraft->sessions)
            : collect();
        $periodKey = $timetableTeachingPeriod.':';
        $semesterPeriod = $periodDates->get($periodKey);
        $displaySegmentType = match ($timetableKind) {
            'exam' => 'exam',
            'supplementary' => 'supplementary',
            default => 'lesson',
        };
        $gridSegments = match ($timetableKind) {
            'exam', 'supplementary' => $slotsForKind,
            default => $timetableTemplate->segments->filter(
                fn ($segment) => in_array($segment->segment_type, ['lesson', 'break'], true)
            ),
        };
        $canEditTimetable = auth()->user()?->can('academics.write') ?? false;
        // A semester timetable keeps moving after it is published, so an HOD or Academic officer
        // can add, edit, move and drop classes on the live one too. Archived ones stay frozen.
        $timetableEditable = $timetableDraft && $canEditTimetable && in_array($timetableDraft->status, ['draft', 'published'], true);
        $canEditInvigilator = $timetableDraft
            && $canEditTimetable
            && in_array($timetableKind, ['exam', 'supplementary'], true);
    @endphp

    <x-page-toolbar
        title="Timetable - {{ $selectedIntake->intakeLabel() }}"
        meta="Lesson bell schedule and semester timetables"
        class="tich-mb-6"
    />

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">1. Lesson bell schedule &amp; teaching days</h2>
        <p class="tich-text tich-mt-2">Define when regular classes run. Exam and retake slots are configured separately under each timetable type below.</p>

        @can('academics.write')
            <form method="POST" action="{{ route('departments.academics.programs.timetable.sync-template', array_merge($hub, ['program' => $program->id])) }}" class="tich-mt-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                <input type="hidden" name="teaching_period" value="{{ $timetableTeachingPeriod }}">
                <input type="hidden" name="timetable_kind" value="{{ $timetableKind }}">

                <div class="tich-form-group">
                    <label class="tich-label">Template name</label>
                    <input type="text" name="name" class="tich-input" value="{{ old('name', $timetableTemplate->name) }}" maxlength="120">
                </div>

                <fieldset class="tich-mt-4" style="border:1px solid var(--tich-border); border-radius:0.5rem; padding:1rem;">
                    <legend class="tich-label" style="padding:0 0.5rem;">Teaching days</legend>
                    <div style="display:flex; flex-wrap:wrap; gap:1rem;">
                        @foreach ($timetableDayLabels as $dayNum => $dayLabel)
                            <label style="display:flex; align-items:center; gap:0.35rem;">
                                <input type="checkbox" name="days[]" value="{{ $dayNum }}" @checked(in_array($dayNum, old('days', $activeDays), true))>
                                {{ $dayLabel }}
                            </label>
                        @endforeach
                    </div>
                    @error('days')<p class="tich-field-error">{{ $message }}</p>@enderror
                </fieldset>

                <div class="tich-table-wrap tich-mt-6">
                    <table class="tich-admin-table" id="timetable-segments-table">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Type</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $segmentRows = old('segments', $lessonSegmentRows); @endphp
                            @foreach ($segmentRows as $index => $segment)
                                <tr>
                                    <td><input type="text" name="segments[{{ $index }}][label]" class="tich-input" value="{{ $segment['label'] ?? '' }}" placeholder="Lesson 1"></td>
                                    <td><input type="time" name="segments[{{ $index }}][start_time]" class="tich-input" value="{{ $segment['start_time'] ?? '' }}"></td>
                                    <td><input type="time" name="segments[{{ $index }}][end_time]" class="tich-input" value="{{ $segment['end_time'] ?? '' }}"></td>
                                    <td>
                                        <select name="segments[{{ $index }}][segment_type]" class="tich-input">
                                            <option value="lesson" @selected(($segment['segment_type'] ?? 'lesson') === 'lesson')>Lesson</option>
                                            <option value="break" @selected(($segment['segment_type'] ?? '') === 'break')>Break</option>
                                        </select>
                                    </td>
                                    <td><button type="button" class="tich-link tich-remove-segment" style="border:none;background:none;cursor:pointer;">Remove</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('segments')<p class="tich-field-error">{{ $message }}</p>@enderror

                <div class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:0.75rem;">
                    <button type="button" class="tich-btn tich-btn-secondary" id="timetable-add-segment">Add segment</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save bell schedule</button>
                </div>
            </form>

            <template id="timetable-segment-row-template">
                <tr>
                    <td><input type="text" name="segments[__INDEX__][label]" class="tich-input" placeholder="Lesson"></td>
                    <td><input type="time" name="segments[__INDEX__][start_time]" class="tich-input"></td>
                    <td><input type="time" name="segments[__INDEX__][end_time]" class="tich-input"></td>
                    <td>
                        <select name="segments[__INDEX__][segment_type]" class="tich-input">
                            <option value="lesson">Lesson</option>
                            <option value="break">Break</option>
                        </select>
                    </td>
                    <td><button type="button" class="tich-link tich-remove-segment" style="border:none;background:none;cursor:pointer;">Remove</button></td>
                </tr>
            </template>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var tableBody = document.querySelector('#timetable-segments-table tbody');
                var template = document.getElementById('timetable-segment-row-template');
                var addBtn = document.getElementById('timetable-add-segment');
                if (!tableBody || !template || !addBtn) return;

                function nextIndex() {
                    return tableBody.querySelectorAll('tr').length;
                }

                addBtn.addEventListener('click', function () {
                    var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex()));
                    tableBody.insertAdjacentHTML('beforeend', html);
                });

                tableBody.addEventListener('click', function (event) {
                    if (event.target.classList.contains('tich-remove-segment')) {
                        event.target.closest('tr')?.remove();
                    }
                });
            });
            </script>
        @else
            <ul class="tich-semester-list tich-mt-4">
                @foreach ($timetableTemplate->segments->filter(fn ($s) => in_array($s->segment_type, ['lesson', 'break'], true)) as $segment)
                    <li class="tich-semester-list__item">
                        <span class="tich-semester-list__label">{{ $segment->label }}</span>
                        <span class="tich-semester-list__meta">{{ $segment->timeLabel() }} · {{ $timetableSegmentTypes[$segment->segment_type] ?? $segment->segment_type }}</span>
                    </li>
                @endforeach
            </ul>
        @endcan
    </article>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">2. Timetables by type</h2>
        <p class="tich-text tich-mt-2">Create lesson, exam, and supplementary/special exam timetables independently. Each type has its own time slots and is published separately.</p>

        <form method="GET" action="{{ route('departments.academics.programs.curriculum', array_merge($curriculumParams, ['section' => 'timetable', 'timetable_kind' => $timetableKind])) }}" class="tich-mt-4" style="max-width:18rem;">
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Teaching period</label>
                <select name="teaching_period" class="tich-input" onchange="this.form.submit()">
                    @foreach (range(1, $totalTeachingPeriods) as $periodNumber)
                        <option value="{{ $periodNumber }}" @selected($timetableTeachingPeriod === $periodNumber)>Semester {{ $periodNumber }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if ($semesterPeriod?->scheduleLabel())
            <div class="tich-inset-panel tich-mt-4">
                <p class="tich-text" style="margin:0 0 0.35rem;"><strong>Semester {{ $timetableTeachingPeriod }}</strong> · {{ $semesterPeriod->scheduleLabel() }}</p>
                @if ($semesterPeriod->effectiveLearningStart() || $semesterPeriod->learning_end_date)
                    <p class="tich-caption" style="margin:0 0 0.25rem;">Learning: {{ $semesterPeriod->effectiveLearningStart()?->format('d M Y') ?? '-' }} - {{ $semesterPeriod->learning_end_date?->format('d M Y') ?? '-' }}</p>
                @endif
                @if ($semesterPeriod->exam_start_date || $semesterPeriod->effectiveExamEnd())
                    <p class="tich-caption" style="margin:0;">Exams: {{ $semesterPeriod->exam_start_date?->format('d M Y') ?? '-' }} - {{ $semesterPeriod->effectiveExamEnd()?->format('d M Y') ?? '-' }}</p>
                @endif
                @if (! $semesterPeriod->effectiveLearningStart() && ! $semesterPeriod->exam_start_date)
                    <p class="tich-caption" style="margin:0.35rem 0 0;">Set learning and exam dates on the <a href="{{ route('departments.academics.programs.curriculum', array_merge($curriculumParams, ['section' => 'semesters'])) }}" class="tich-link">Semester units</a> page for more accurate scheduling.</p>
                @endif
            </div>
        @else
            <p class="tich-caption tich-mt-4">Set semester dates on the <a href="{{ route('departments.academics.programs.curriculum', array_merge($curriculumParams, ['section' => 'semesters'])) }}" class="tich-link">Semester units</a> page before generating timetables.</p>
        @endif

        <nav class="tich-card tich-mt-6" style="display:flex; flex-wrap:wrap; gap:0.5rem; padding:0.75rem 1rem; box-shadow:none; border:1px solid var(--tich-border);">
            @foreach ($timetableKinds as $kindKey => $kindLabel)
                @php
                    $kindTimetable = $timetableDraftsByKind[$kindKey] ?? null;
                    $kindStatus = $kindTimetable ? ucfirst($kindTimetable->status) : 'Not created';
                @endphp
                <a
                    href="{{ route('departments.academics.programs.curriculum', $timetableParams($kindKey)) }}"
                    class="tich-btn {{ $timetableKind === $kindKey ? 'tich-btn-primary' : 'tich-btn-secondary' }}"
                    style="font-size:0.875rem;"
                >
                    {{ $kindLabel }}
                    <span class="tich-caption" style="display:block; font-size:0.75rem; opacity:0.85;">{{ $kindStatus }}</span>
                </a>
            @endforeach
        </nav>

        <div class="tich-mt-6">
            <h3 class="tich-h3">{{ $timetableKinds[$timetableKind] ?? 'Timetable' }}</h3>
            <p class="tich-text tich-mt-2">
                @if ($timetableKind === 'lesson')
                    Uses lesson slots from the bell schedule above and unit contact hours across the semester learning period.
                @elseif ($timetableKind === 'exam')
                    End-of-semester exams for units taught this semester. Default slots are 8-10, 11-1, and 2-4 - edit below if needed.
                @else
                    Combined supplementary and special exam sittings, generated as one timetable. Default slots are 8-10, 11-1, and 2-4 - edit below if needed.
                @endif
            </p>

            @if (in_array($timetableKind, ['exam', 'supplementary'], true))
                @can('academics.write')
                    <form method="POST" action="{{ route('departments.academics.programs.timetable.sync-kind-slots', array_merge($hub, ['program' => $program->id])) }}" class="tich-mt-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                        <input type="hidden" name="teaching_period" value="{{ $timetableTeachingPeriod }}">
                        <input type="hidden" name="timetable_kind" value="{{ $timetableKind }}">
                        <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">

                        <div class="tich-table-wrap">
                            <table class="tich-admin-table" id="kind-slots-table">
                                <thead>
                                    <tr>
                                        <th>Label</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $slotRows = old('segments', $kindSlotRows); @endphp
                                    @foreach ($slotRows as $index => $slot)
                                        <tr>
                                            <td><input type="text" name="segments[{{ $index }}][label]" class="tich-input" value="{{ $slot['label'] ?? '' }}"></td>
                                            <td><input type="time" name="segments[{{ $index }}][start_time]" class="tich-input" value="{{ $slot['start_time'] ?? '' }}"></td>
                                            <td><input type="time" name="segments[{{ $index }}][end_time]" class="tich-input" value="{{ $slot['end_time'] ?? '' }}"></td>
                                            <td><button type="button" class="tich-link kind-remove-slot" style="border:none;background:none;cursor:pointer;">Remove</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @error('segments')<p class="tich-field-error">{{ $message }}</p>@enderror

                        <div class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:0.75rem;">
                            <button type="button" class="tich-btn tich-btn-secondary" id="kind-add-slot">Add slot</button>
                            <button type="submit" class="tich-btn tich-btn-secondary">Save slots</button>
                        </div>
                    </form>

                    <template id="kind-slot-row-template">
                        <tr>
                            <td><input type="text" name="segments[__INDEX__][label]" class="tich-input" placeholder="Exam session"></td>
                            <td><input type="time" name="segments[__INDEX__][start_time]" class="tich-input"></td>
                            <td><input type="time" name="segments[__INDEX__][end_time]" class="tich-input"></td>
                            <td><button type="button" class="tich-link kind-remove-slot" style="border:none;background:none;cursor:pointer;">Remove</button></td>
                        </tr>
                    </template>

                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var tableBody = document.querySelector('#kind-slots-table tbody');
                        var template = document.getElementById('kind-slot-row-template');
                        var addBtn = document.getElementById('kind-add-slot');
                        if (!tableBody || !template || !addBtn) return;

                        addBtn.addEventListener('click', function () {
                            var index = tableBody.querySelectorAll('tr').length;
                            tableBody.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(index)));
                        });

                        tableBody.addEventListener('click', function (event) {
                            if (event.target.classList.contains('kind-remove-slot')) {
                                event.target.closest('tr')?.remove();
                            }
                        });
                    });
                    </script>
                @endcan
            @endif

            @can('academics.write')
                @if (! $timetableDraft)
                    <form method="POST" action="{{ route('departments.academics.programs.timetable.start', array_merge($hub, ['program' => $program->id, 'version' => $selectedIntake->id])) }}" class="tich-mt-4" data-scroll-target="timetable-display">
                        @csrf
                        <input type="hidden" name="teaching_period" value="{{ $timetableTeachingPeriod }}">
                        <input type="hidden" name="timetable_kind" value="{{ $timetableKind }}">
                        <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                        <div class="tich-form-group" style="max-width:28rem;">
                            <label class="tich-label">Timetable title</label>
                            <input type="text" name="title" class="tich-input" value="{{ old('title') }}" placeholder="{{ $timetableKinds[$timetableKind] ?? 'Timetable' }} - Semester {{ $timetableTeachingPeriod }}" maxlength="200">
                        </div>
                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Start an empty {{ strtolower($timetableKinds[$timetableKind] ?? 'timetable') }}</button>
                        <p class="tich-caption tich-mt-2">Opens a blank timetable for this semester. Nothing is filled in for you - add each class yourself below.</p>
                    </form>
                @else
                    <p class="tich-caption tich-mt-4">This timetable is built by hand. Use <strong>Edit timetable</strong> below the grid to add a class, change one, or remove one.</p>
                @endif
            @endcan
            @error('timetable')<p class="tich-field-error tich-mt-4">{{ $message }}</p>@enderror
        </div>
    </article>

    @if ($timetableDraft)
        <article class="tich-card tich-mb-8" id="timetable-display">
            <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:start;">
                <div>
                    <h2 class="tich-h3">3. {{ $timetableKinds[$timetableKind] ?? 'Timetable' }} display</h2>
                    <p class="tich-text tich-mt-2">
                        <strong>{{ $timetableDraft->displayTitle() }}</strong><br>
                        Semester {{ $timetableDraft->teaching_period }} · {{ ucfirst($timetableDraft->status) }}
                        @if ($timetableDraft->generation_notes)
                            · {{ $timetableDraft->generation_notes }}
                        @endif
                    </p>
                    @if ($timetableEditable && $timetableDraft->status === 'published')
                        <p class="tich-caption tich-mt-2">This timetable is published and stays open for the changes a semester brings - Edit timetable below, save once, and lecturers and students see it immediately.</p>
                    @endif
                    @if ($timetableDraft->progress_note || $timetableDraft->week_span)
                        <p class="tich-text tich-mt-2" style="margin:0;">
                            <strong>Progress:</strong>
                            {{ $timetableDraft->progress_note ?: 'posted up to week or month '.$timetableDraft->week_span }}
                            @if ($timetableDraft->week_span)
                                <span class="tich-caption">(of {{ $timetableDraft->week_span }} in this semester)</span>
                            @endif
                        </p>
                    @endif
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
                    @if ($timetableEditable)
                        @can('academics.write')
                                <button type="button" class="tich-btn tich-btn-primary" id="timetable-editor-open">Edit timetable</button>
                        @endcan
                    @endif
                    @if ($timetableDraft->status === 'draft')
                        @can('academics.write')
                            <form method="POST" action="{{ route('departments.academics.programs.timetable.publish', array_merge($hub, ['program' => $program->id, 'timetable' => $timetableDraft->id])) }}">
                                @csrf
                                <button type="submit" class="tich-btn tich-btn-primary">Publish timetable</button>
                            </form>
                        @endcan
                    @endif
                    <a href="{{ route('departments.academics.programs.timetable.print', array_merge($hub, ['program' => $program->id, 'timetable' => $timetableDraft->id])) }}" target="_blank" class="tich-btn tich-btn-secondary">Print / preview</a>
                    <a href="{{ route('departments.academics.programs.timetable.pdf', array_merge($hub, ['program' => $program->id, 'timetable' => $timetableDraft->id])) }}" class="tich-btn tich-btn-secondary">Download PDF</a>
                </div>
            </div>

            @if ($timetableConflicts->isNotEmpty())
                <div class="tich-notice tich-notice--warning tich-mt-4">
                    <p class="tich-text" style="margin:0 0 0.5rem;"><strong>Conflicts detected</strong></p>
                    <ul class="tich-text" style="margin:0; padding-left:1.25rem;">
                        @foreach ($timetableConflicts as $conflict)
                            <li>{{ $conflict['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('academics.programs.partials.timetable-grid', [
                'sessions' => $timetableDraft->sessions,
                'dayLabels' => $timetableDayLabels,
                'segmentTypes' => $timetableSegmentTypes,
                'activeDays' => $activeDays,
                'segments' => $gridSegments,
                'editable' => $timetableEditable,
                'canEditInvigilator' => $canEditInvigilator,
                'moveSessionUrl' => $timetableEditable
                    ? route('departments.academics.programs.timetable.move-session', array_merge($hub, [
                        'program' => $program->id,
                        'timetable' => $timetableDraft->id,
                        'session' => '__SESSION__',
                    ]))
                    : null,
            ])

            @if ($timetableEditable)
                <script src="{{ asset('js/tich-timetable-drag.js') }}" defer></script>
            @endif

            @if ($canEditInvigilator)
                @include('admin.partials.tich-modal-assets')
                <div class="tich-modal" id="timetable-invigilator-modal" hidden aria-hidden="true">
                    <div class="tich-modal__backdrop" data-close-modal="timetable-invigilator-modal"></div>
                    <div class="tich-modal__dialog">
                        <header class="tich-modal__header">
                            <h2 class="tich-h3" style="margin:0;">Assign invigilator</h2>
                            <button type="button" class="tich-modal__close" data-close-modal="timetable-invigilator-modal" aria-label="Close">&times;</button>
                        </header>
                        <form
                            method="POST"
                            id="timetable-invigilator-form"
                            action="#"
                            class="tich-modal__body"
                            data-scroll-target="timetable-display"
                        >
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                            <p class="tich-text tich-mb-4" id="timetable-invigilator-session-label"></p>
                            <div class="tich-form-group">
                                <label class="tich-label" for="timetable-invigilator-staff">Invigilator</label>
                                <select name="staff_id" id="timetable-invigilator-staff" class="tich-input">
                                    <option value="">Unassigned</option>
                                    @foreach ($timetableStaff as $member)
                                        <option value="{{ $member->id }}">{{ $member->first_name }} {{ $member->surname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="tich-caption tich-mt-2">The assigned invigilator is notified in-app and by email immediately.</p>
                            <footer class="tich-modal__footer">
                                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="timetable-invigilator-modal">Cancel</button>
                                <button type="submit" class="tich-btn tich-btn-primary">Save invigilator</button>
                            </footer>
                        </form>
                    </div>
                </div>
                @php
                    $invigilatorUpdateUrlTemplate = route(
                        'departments.academics.programs.timetable.update-invigilator',
                        array_merge($hub, [
                            'program' => $program->id,
                            'timetable' => $timetableDraft->id,
                            'session' => '__SESSION__',
                        ])
                    );
                @endphp
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var form = document.getElementById('timetable-invigilator-form');
                    var staffSelect = document.getElementById('timetable-invigilator-staff');
                    var labelEl = document.getElementById('timetable-invigilator-session-label');
                    var urlTemplate = @json($invigilatorUpdateUrlTemplate);

                    document.querySelectorAll('[data-open-invigilator-modal]').forEach(function (button) {
                        button.addEventListener('click', function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            if (!form || !staffSelect) return;
                            form.action = urlTemplate.replace('__SESSION__', button.getAttribute('data-session-id'));
                            staffSelect.value = button.getAttribute('data-staff-id') || '';
                            if (labelEl) {
                                labelEl.textContent = button.getAttribute('data-session-label') || '';
                            }
                            window.tichOpenModal('timetable-invigilator-modal');
                        });
                    });
                });
                </script>
            @endif

            @if ($timetableEditable)
                @can('academics.write')
                    @php
                        $manualTypes = match ($timetableKind) {
                            'exam' => ['exam'],
                            'supplementary' => ['supplementary', 'special_exam'],
                            default => ['lesson', 'break', 'lunch'],
                        };
                        $staffLabel = in_array($timetableKind, ['exam', 'supplementary'], true) ? 'Invigilator' : 'Lecturer';
                        $addSessionUrl = route('departments.academics.programs.timetable.add-session', array_merge($hub, ['program' => $program->id, 'timetable' => $timetableDraft->id]));
                        $cycleLabel = $timetableKind === 'lesson' ? 'Week or month of the semester' : 'Sitting number';
                        $panelSessions = $timetableDraft->sessions->sortBy([['day_of_week', 'asc'], ['start_time', 'asc']]);
                    @endphp
                    <details class="tich-mt-6 tich-inset-panel" id="timetable-session-panel">
                        <summary class="tich-label" style="cursor:pointer;" id="timetable-session-panel-label">Add session manually</summary>
                        <form method="POST" id="timetable-session-form" action="{{ $addSessionUrl }}" class="tich-mt-4" data-scroll-target="timetable-display" data-add-action="{{ $addSessionUrl }}">
                            @csrf
                            <input type="hidden" name="_method" value="PUT" id="timetable-session-method" disabled>
                            <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">

                            @if ($panelSessions->isNotEmpty())
                                <div class="tich-form-group" style="max-width:34rem;">
                                    <label class="tich-label" for="timetable-session-pick">Class to change</label>
                                    <select id="timetable-session-pick" class="tich-input" data-edit-url="{{ str_replace('__SESSION__', '__ID__', route('departments.academics.programs.timetable.update-session', array_merge($hub, ['program' => $program->id, 'timetable' => $timetableDraft->id, 'session' => '__SESSION__']))) }}" data-remove-url="{{ str_replace('__SESSION__', '__ID__', route('departments.academics.programs.timetable.remove-session', array_merge($hub, ['program' => $program->id, 'timetable' => $timetableDraft->id, 'session' => '__SESSION__']))) }}">
                                        <option value="">- none, I am adding a new class -</option>
                                        @foreach ($panelSessions as $session)
                                            <option value="{{ $session->id }}"
                                                data-session="{{ json_encode([
                                                    'id' => $session->id,
                                                    'label' => $session->displayTitle().' - '.($timetableDayLabels[$session->day_of_week] ?? $session->day_of_week).' '.substr((string) $session->start_time, 0, 5),
                                                    'session_type' => $session->session_type,
                                                    'unit_id' => $session->unit_id,
                                                    'unit_label' => $session->unit?->displayLabel(),
                                                    'delivery_mode' => $session->delivery_mode ?? 'physical',
                                                    'recurrence' => $session->recurrence ?? 'weekly',
                                                    'week_no' => $session->week_no,
                                                    'contact_hours' => $session->contact_hours,
                                                    'staff_id' => $session->staff_id,
                                                    'day_of_week' => $session->day_of_week,
                                                    'start_time' => substr((string) $session->start_time, 0, 5),
                                                    'end_time' => substr((string) $session->end_time, 0, 5),
                                                    'venue' => $session->venue,
                                                ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                                            >{{ $session->displayTitle() }} - {{ $timetableDayLabels[$session->day_of_week] ?? $session->day_of_week }} {{ substr((string) $session->start_time, 0, 5) }}@if ($session->cycleLabel()) ({{ $session->cycleLabel() }}) @endif</option>
                                        @endforeach
                                    </select>
                                    <p class="tich-caption">Pick a class to load it into the form below, then save the change. Nothing else on the timetable moves.</p>
                                </div>
                            @endif

                            <p class="tich-caption tich-mb-3" id="timetable-session-edit-note" hidden>
                                Changing <strong id="timetable-session-edit-label"></strong>.
                                <button type="button" class="tich-link" id="timetable-session-cancel-edit">back to adding a new class</button>
                            </p>

                            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                                <div class="tich-form-group">
                                    <label class="tich-label" for="timetable-session-unit">Unit</label>
                                    <select name="unit_id" id="timetable-session-unit" class="tich-input" required>
                                        <option value="">- no unit on this slot -</option>
                                        @foreach ($catalogUnits as $unit)
                                            <option value="{{ $unit->id }}" data-hours="{{ $timetableContactHours[$unit->id] ?? $unit->contact_hours ?? '' }}">{{ $unit->unit_code }} - {{ $unit->unit_name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="tich-caption">The unit is what shows in the cell, on the print view and PDF, and on the lecturer and student screens. Pick Break or Lunch for a rest period - those need no unit.</p>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label" for="timetable-session-type">Session type</label>
                                    <select name="session_type" id="timetable-session-type" class="tich-input" required>
                                        @foreach ($manualTypes as $typeKey)
                                            <option value="{{ $typeKey }}">{{ $timetableSegmentTypes[$typeKey] ?? ucfirst($typeKey) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label" for="timetable-session-day">Day</label>
                                    <select name="day_of_week" id="timetable-session-day" class="tich-input" required>
                                        @foreach ($timetableDayLabels as $dayNum => $dayLabel)
                                            @if (in_array($dayNum, $activeDays, true))
                                                <option value="{{ $dayNum }}">{{ $dayLabel }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label" for="timetable-session-start">Start</label>
                                    <input type="time" id="timetable-session-start" name="start_time" class="tich-input" required>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label" for="timetable-session-end">End</label>
                                    <input type="time" id="timetable-session-end" name="end_time" class="tich-input" required>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label" for="timetable-session-recurrence">Repeats</label>
                                    <select name="recurrence" id="timetable-session-recurrence" class="tich-input" required>
                                        @foreach (\App\Models\ProgramTimetableSession::recurrences() as $recurrenceKey => $recurrenceLabel)
                                            <option value="{{ $recurrenceKey }}">{{ $recurrenceLabel }}</option>
                                        @endforeach
                                    </select>
                                    <p class="tich-caption">Weekly and monthly classes are only one posting of many in the semester, so give the week or month it belongs to below.</p>
                                </div>
                                <div class="tich-form-group" id="timetable-session-cycle-group">
                                    <label class="tich-label" for="timetable-session-cycle">{{ $cycleLabel }}</label>
                                    <input type="number" id="timetable-session-cycle" name="week_no" class="tich-input" min="1" max="60" placeholder="e.g. 3">
                                    <p class="tich-caption">Leave empty when the same slots run for the whole semester.</p>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label" for="timetable-session-staff">{{ $staffLabel }}</label>
                                    <select name="staff_id" id="timetable-session-staff" class="tich-input">
                                        <option value="">-</option>
                                        @foreach ($timetableStaff as $member)
                                            <option value="{{ $member->id }}">{{ $member->first_name }} {{ $member->surname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label" for="timetable-session-venue">Venue / online link</label>
                                    <input type="text" id="timetable-session-venue" name="venue" class="tich-input" placeholder="e.g. Skills lab or the meeting link">
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label" for="timetable-session-mode">Mode</label>
                                    <select name="delivery_mode" id="timetable-session-mode" class="tich-input" required>
                                        @foreach (\App\Models\ProgramTimetableSession::deliveryModes() as $modeKey => $modeLabel)
                                            <option value="{{ $modeKey }}">{{ $modeLabel }}</option>
                                        @endforeach
                                    </select>
                                    <p class="tich-caption">Online classes show as online on the lecturer and student timetables, so nobody turns up to an empty room.</p>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label" for="timetable-session-hours">Contact hours</label>
                                    <input type="number" step="0.5" min="0" id="timetable-session-hours" name="contact_hours" class="tich-input" readonly placeholder="-">
                                    <p class="tich-caption">Comes from the contact hours captured with the unit. Choose a unit and it fills in - it is not typed again here.</p>
                                </div>
                            </div>
                            @error('session')<p class="tich-field-error">{{ $message }}</p>@enderror
                            @error('unit_id')<p class="tich-field-error">{{ $message }}</p>@enderror
                            <div style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;" class="tich-mt-4">
                                <button type="submit" class="tich-btn tich-btn-secondary" id="timetable-session-submit">Add session</button>
                                <button type="button" class="tich-link" id="timetable-session-remove" hidden>Remove this class</button>
                            </div>
                        </form>

                        <form method="POST" id="timetable-session-remove-form" action="#" class="tich-mt-2" data-scroll-target="timetable-display" hidden>
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                        </form>
                    </details>

                    <form method="POST" action="{{ route('departments.academics.programs.timetable.save-progress', array_merge($hub, ['program' => $program->id, 'timetable' => $timetableDraft->id])) }}" class="tich-mt-4 tich-inset-panel" data-scroll-target="timetable-display">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                        <div class="tich-grid tich-grid--2" style="gap:1rem; max-width:44rem;">
                            <div class="tich-form-group">
                                <label class="tich-label" for="timetable-progress-note">Working up to</label>
                                <input type="text" id="timetable-progress-note" name="progress_note" class="tich-input" maxlength="255" value="{{ old('progress_note', $timetableDraft->progress_note) }}" placeholder="e.g. Timetable posted up to week 6">
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label" for="timetable-week-span">Postings in the semester</label>
                                <input type="number" id="timetable-week-span" name="week_span" class="tich-input" min="1" max="60" value="{{ old('week_span', $timetableDraft->week_span) }}" placeholder="e.g. 16">
                            </div>
                        </div>
                        <p class="tich-caption">Lecturers and students read this next to the timetable, so they know which weeks are already out.</p>
                        <button type="submit" class="tich-btn tich-btn-secondary tich-mt-2">Save semester progress</button>
                    </form>

                    <script>
                    (function () {
                        var form = document.getElementById('timetable-session-form');
                        if (!form) return;

                        var panel = document.getElementById('timetable-session-panel');
                        var panelLabel = document.getElementById('timetable-session-panel-label');
                        var note = document.getElementById('timetable-session-edit-note');
                        var noteLabel = document.getElementById('timetable-session-edit-label');
                        var submit = document.getElementById('timetable-session-submit');
                        var cancel = document.getElementById('timetable-session-cancel-edit');
                        var method = document.getElementById('timetable-session-method');
                        var pick = document.getElementById('timetable-session-pick');
                        var removeForm = document.getElementById('timetable-session-remove-form');
                        var removeBtn = document.getElementById('timetable-session-remove');
                        var addAction = form.getAttribute('data-add-action');
                        var fields = {
                            unit_id: 'timetable-session-unit',
                            session_type: 'timetable-session-type',
                            day_of_week: 'timetable-session-day',
                            start_time: 'timetable-session-start',
                            end_time: 'timetable-session-end',
                            staff_id: 'timetable-session-staff',
                            venue: 'timetable-session-venue',
                            delivery_mode: 'timetable-session-mode',
                            recurrence: 'timetable-session-recurrence',
                            week_no: 'timetable-session-cycle',
                            contact_hours: 'timetable-session-hours'
                        };
                        var type = document.getElementById(fields.session_type);
                        var unit = document.getElementById(fields.unit_id);
                        var hours = document.getElementById(fields.contact_hours);
                        var recurrence = document.getElementById(fields.recurrence);
                        var cycle = document.getElementById(fields.week_no);
                        var cycleGroup = document.getElementById('timetable-session-cycle-group');

                        // Break and Lunch belong to everybody at once, so they carry no unit.
                        function unitNeeded() {
                            return ['break', 'lunch', 'other'].indexOf(type ? type.value : '') === -1;
                        }

                        function syncUnit() {
                            if (unit) unit.required = unitNeeded();
                        }

                        function syncCycle() {
                            if (!recurrence || !cycleGroup) return;
                            cycleGroup.hidden = recurrence.value === 'semester';
                            if (cycleGroup.hidden && cycle) cycle.value = '';
                        }

                        // The hours live on the unit already - pull them across instead of asking twice.
                        function syncHours() {
                            if (!hours) return;
                            var option = unit ? unit.options[unit.selectedIndex] : null;
                            var value = option ? option.getAttribute('data-hours') : '';

                            if (!unitNeeded() || !unit || unit.value === '' || !value) {
                                hours.value = '';

                                return;
                            }

                            hours.value = value;
                        }

                        function resetToAdd() {
                            form.action = addAction;
                            method.disabled = true;
                            note.hidden = true;
                            panelLabel.textContent = 'Add session manually';
                            submit.textContent = 'Add session';
                            if (removeBtn) removeBtn.hidden = true;
                            if (removeForm) removeForm.hidden = true;
                            if (pick) pick.value = '';
                        }

                        function loadSession(data) {
                            panel.open = true;
                            resetToAdd();
                            form.action = pick.getAttribute('data-edit-url').replace('__ID__', String(data.id));
                            method.disabled = false;

                            if (type && data.session_type) {
                                type.value = data.session_type;
                            }

                            syncUnit();
                            syncCycle();

                            Object.keys(fields).forEach(function (name) {
                                var el = document.getElementById(fields[name]);
                                if (!el || name === 'unit_id') return;
                                var value = data[name];

                                if (value === null || value === undefined) {
                                    if (el.tagName === 'SELECT') {
                                        el.selectedIndex = 0;
                                    } else {
                                        el.value = '';
                                    }

                                    return;
                                }

                                el.value = String(value);
                            });

                            if (unit && data.unit_id && unitNeeded()) {
                                unit.value = String(data.unit_id);

                                if (unit.value !== String(data.unit_id)) {
                                    var option = document.createElement('option');
                                    option.value = data.unit_id;
                                    option.textContent = data.unit_label || ('Unit '+data.unit_id);
                                    option.setAttribute('data-hours', data.contact_hours === undefined || data.contact_hours === null ? '' : String(data.contact_hours));
                                    unit.insertBefore(option, unit.options[0] || null);
                                    unit.value = String(data.unit_id);
                                }
                            }

                            if (data.recurrence === 'semester' && cycle) cycle.value = '';

                            if (data.contact_hours === null || data.contact_hours === undefined || data.contact_hours === '') {
                                syncHours();
                            }

                            if (removeBtn && removeForm) {
                                removeForm.action = pick.getAttribute('data-remove-url').replace('__ID__', String(data.id));
                                removeBtn.hidden = false;
                                removeForm.hidden = false;
                            }

                            noteLabel.textContent = data.label || '';
                            note.hidden = false;
                            panelLabel.textContent = 'Change session - ' + (data.label || '');
                            submit.textContent = 'Save changes';
                        }

                        if (pick) {
                            pick.addEventListener('change', function () {
                                var option = pick.options[pick.selectedIndex];
                                if (!option || !option.value) {
                                    resetToAdd();

                                    return;
                                }

                                var data = {};
                                try { data = JSON.parse(option.getAttribute('data-session') || '{}'); } catch (e) { return; }

                                loadSession(data);
                            });
                        }

                        if (removeBtn) {
                            removeBtn.addEventListener('click', function (event) {
                                event.preventDefault();
                                var option = pick ? pick.options[pick.selectedIndex] : null;
                                var data = {};
                                try { data = JSON.parse(option.getAttribute('data-session') || '{}'); } catch (e) {}

                                if (window.confirm('Remove ' + (data.label || 'this class') + ' from the timetable? Lecturers and students stop seeing it right away.')) {
                                    removeForm.submit();
                                }
                            });
                        }

                        if (cancel) cancel.addEventListener('click', resetToAdd);
                        if (type) type.addEventListener('change', function () {
                            syncUnit();
                            if (unit && !unitNeeded()) unit.selectedIndex = 0;
                            syncHours();
                        });
                        if (unit) unit.addEventListener('change', function () { syncHours(); });
                        if (recurrence) recurrence.addEventListener('change', syncCycle);
                        syncUnit();
                        syncCycle();
                        syncHours();

                        var openBtn = document.getElementById('timetable-editor-open');
                        if (openBtn) {
                            openBtn.addEventListener('click', function () {
                                panel.open = true;
                                panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            });
                        }
                        form.addEventListener('submit', function () {
                            if (unit && unit.disabled) unit.disabled = false;
                            if (cycle && cycleGroup && cycleGroup.hidden) cycle.disabled = true;
                        });
                    })();
                    </script>
                @endcan
            @endif
        </article>
    @endif
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-scroll-target]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var target = form.getAttribute('data-scroll-target');
            if (target) {
                try { sessionStorage.setItem('tich-timetable-scroll', target); } catch (e) {}
            }
        });
    });

    var scrollTarget = null;
    try {
        scrollTarget = sessionStorage.getItem('tich-timetable-scroll');
        if (scrollTarget) {
            sessionStorage.removeItem('tich-timetable-scroll');
        }
    } catch (e) {}

    if (!scrollTarget && window.location.hash) {
        scrollTarget = window.location.hash.replace(/^#/, '');
    }

    if (!scrollTarget) {
        return;
    }

    var el = document.getElementById(scrollTarget);
    if (!el) {
        return;
    }

    requestAnimationFrame(function () {
        el.scrollIntoView({ behavior: 'auto', block: 'start' });
    });
});
</script>
