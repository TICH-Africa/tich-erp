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
        $timetableEditable = $timetableDraft && $timetableDraft->status === 'draft' && $canEditTimetable;
    @endphp

    <div class="tich-section__intro tich-mb-6" style="text-align:left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Timetable - {{ $selectedIntake->intakeLabel() }}</h1>
        <p class="tich-text">Configure the lesson bell schedule, then create lesson, exam, and supplementary/special exam timetables independently for each semester.</p>
    </div>

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
        <p class="tich-text tich-mt-2">Create lesson, exam, and supplementary/special exam timetables independently. Each type has its own time slots and can be generated and published separately.</p>

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
                <form method="POST" action="{{ route('departments.academics.programs.timetable.generate', array_merge($hub, ['program' => $program->id, 'version' => $selectedIntake->id])) }}" class="tich-mt-4">
                    @csrf
                    <input type="hidden" name="teaching_period" value="{{ $timetableTeachingPeriod }}">
                    <input type="hidden" name="timetable_kind" value="{{ $timetableKind }}">
                    <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                    <div class="tich-form-group" style="max-width:28rem;">
                        <label class="tich-label">Timetable title</label>
                        <input type="text" name="title" class="tich-input" value="{{ old('title', $timetableDraft?->title) }}" placeholder="{{ $timetableKinds[$timetableKind] ?? 'Timetable' }} - Semester {{ $timetableTeachingPeriod }}" maxlength="200">
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Generate {{ strtolower($timetableKinds[$timetableKind] ?? 'timetable') }}</button>
                </form>
            @endcan
            @error('timetable')<p class="tich-field-error tich-mt-4">{{ $message }}</p>@enderror
        </div>
    </article>

    @if ($timetableDraft)
        <article class="tich-card tich-mb-8">
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
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
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

            @if ($timetableDraft->status === 'draft')
                @can('academics.write')
                    <details class="tich-mt-6 tich-inset-panel">
                        <summary class="tich-label" style="cursor:pointer;">Add session manually</summary>
                        <form method="POST" action="{{ route('departments.academics.programs.timetable.add-session', array_merge($hub, ['program' => $program->id, 'timetable' => $timetableDraft->id])) }}" class="tich-mt-4">
                            @csrf
                            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                                <div class="tich-form-group">
                                    <label class="tich-label">Session type</label>
                                    <select name="session_type" class="tich-input" required>
                                        @php
                                            $manualTypes = match ($timetableKind) {
                                                'exam' => ['exam'],
                                                'supplementary' => ['supplementary', 'special_exam'],
                                                default => ['lesson', 'other'],
                                            };
                                        @endphp
                                        @foreach ($manualTypes as $typeKey)
                                            <option value="{{ $typeKey }}">{{ $timetableSegmentTypes[$typeKey] ?? ucfirst($typeKey) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">Title</label>
                                    <input type="text" name="title" class="tich-input" placeholder="e.g. HMDCC-01 end-of-semester exam">
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">Day</label>
                                    <select name="day_of_week" class="tich-input" required>
                                        @foreach ($timetableDayLabels as $dayNum => $dayLabel)
                                            @if (in_array($dayNum, $activeDays, true))
                                                <option value="{{ $dayNum }}">{{ $dayLabel }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">Unit (optional)</label>
                                    <select name="unit_id" class="tich-input">
                                        <option value="">-</option>
                                        @foreach ($catalogUnits as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->unit_code }} - {{ $unit->unit_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">Start</label>
                                    <input type="time" name="start_time" class="tich-input" required>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">End</label>
                                    <input type="time" name="end_time" class="tich-input" required>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">Room</label>
                                    <select name="room_id" class="tich-input">
                                        <option value="">-</option>
                                        @foreach ($timetableRooms as $room)
                                            <option value="{{ $room->id }}">{{ $room->room_code }} - {{ $room->room_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">Lecturer</label>
                                    <select name="staff_id" class="tich-input">
                                        <option value="">-</option>
                                        @foreach ($timetableStaff as $member)
                                            <option value="{{ $member->id }}">{{ $member->first_name }} {{ $member->surname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">Venue (if no room)</label>
                                    <input type="text" name="venue" class="tich-input" placeholder="e.g. Skills lab">
                                </div>
                            </div>
                            @error('session')<p class="tich-field-error">{{ $message }}</p>@enderror
                            <button type="submit" class="tich-btn tich-btn-secondary tich-mt-4">Add session</button>
                        </form>
                    </details>
                @endcan
            @endif
        </article>
    @endif
@endif
