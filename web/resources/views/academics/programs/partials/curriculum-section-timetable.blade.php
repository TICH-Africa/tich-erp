@if (! $selectedIntake)
    <article class="tich-card">
        <p class="tich-text">Select or <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">create an intake</a> before building a timetable.</p>
    </article>
@else
    @php
        $activeDays = $timetableTemplate->days->where('is_active', 1)->pluck('day_of_week')->map(fn ($d) => (int) $d)->all();
        $timetableConflicts = $timetableDraft
            ? app(\App\Services\TimetableSchedulingService::class)->detectConflicts($timetableDraft->sessions)
            : collect();
    @endphp

    <div class="tich-section__intro tich-mb-6" style="text-align:left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Timetable — {{ $selectedIntake->intakeLabel() }}</h1>
        <p class="tich-text">Configure the daily bell schedule, generate lesson slots, add exams, and publish for students.</p>
    </div>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">1. Bell schedule &amp; teaching days</h2>
        <p class="tich-text tich-mt-2">Define when classes can run. Add lessons, breaks, and exam windows with any start/end times you need.</p>

        @can('academics.write')
            <form method="POST" action="{{ route('departments.academics.programs.timetable.sync-template', array_merge($hub, ['program' => $program->id])) }}" class="tich-mt-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                <input type="hidden" name="teaching_period" value="{{ $timetableTeachingPeriod }}">

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

                <div class="tich-mt-6" style="overflow-x:auto;">
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
                            @php $segmentRows = old('segments', $timetableTemplate->segments->map(fn ($s) => [
                                'label' => $s->label,
                                'start_time' => substr((string) $s->start_time, 0, 5),
                                'end_time' => substr((string) $s->end_time, 0, 5),
                                'segment_type' => $s->segment_type,
                            ])->all()); @endphp
                            @foreach ($segmentRows as $index => $segment)
                                <tr>
                                    <td><input type="text" name="segments[{{ $index }}][label]" class="tich-input" value="{{ $segment['label'] ?? '' }}" placeholder="Lesson 1"></td>
                                    <td><input type="time" name="segments[{{ $index }}][start_time]" class="tich-input" value="{{ $segment['start_time'] ?? '' }}"></td>
                                    <td><input type="time" name="segments[{{ $index }}][end_time]" class="tich-input" value="{{ $segment['end_time'] ?? '' }}"></td>
                                    <td>
                                        <select name="segments[{{ $index }}][segment_type]" class="tich-input">
                                            @foreach ($timetableSegmentTypes as $typeKey => $typeLabel)
                                                <option value="{{ $typeKey }}" @selected(($segment['segment_type'] ?? 'lesson') === $typeKey)>{{ $typeLabel }}</option>
                                            @endforeach
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
                            @foreach ($timetableSegmentTypes as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                            @endforeach
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
                @foreach ($timetableTemplate->segments as $segment)
                    <li class="tich-semester-list__item">
                        <span class="tich-semester-list__label">{{ $segment->label }}</span>
                        <span class="tich-semester-list__meta">{{ $segment->timeLabel() }} · {{ $timetableSegmentTypes[$segment->segment_type] ?? $segment->segment_type }}</span>
                    </li>
                @endforeach
            </ul>
        @endcan
    </article>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">2. Generate timetable</h2>
        <p class="tich-text tich-mt-2">Auto-schedule units from Semester {{ $timetableTeachingPeriod }} into lesson slots. Conflict checks cover rooms, lecturers, units, and class groups.</p>

        <form method="GET" action="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'timetable', 'intake' => $selectedIntake->id])) }}" class="tich-form-group tich-mt-4" style="max-width:16rem;">
            <label class="tich-label">Teaching period</label>
            <select name="teaching_period" class="tich-input" onchange="this.form.submit()">
                @foreach (range(1, $totalTeachingPeriods) as $periodNumber)
                    <option value="{{ $periodNumber }}" @selected($timetableTeachingPeriod === $periodNumber)>Semester {{ $periodNumber }}</option>
                @endforeach
            </select>
        </form>

        @can('academics.write')
            <form method="POST" action="{{ route('departments.academics.programs.timetable.generate', array_merge($hub, ['program' => $program->id, 'version' => $selectedIntake->id])) }}" class="tich-mt-4">
                @csrf
                <input type="hidden" name="teaching_period" value="{{ $timetableTeachingPeriod }}">
                <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                <button type="submit" class="tich-btn tich-btn-primary">Generate draft timetable</button>
            </form>
        @endcan
        @error('timetable')<p class="tich-field-error tich-mt-4">{{ $message }}</p>@enderror
    </article>

    @if ($timetableDraft)
        <article class="tich-card tich-mb-8">
            <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:start;">
                <div>
                    <h2 class="tich-h3">3. Timetable display</h2>
                    <p class="tich-text tich-mt-2">
                        Semester {{ $timetableDraft->teaching_period }} · {{ ucfirst($timetableDraft->status) }}
                        @if ($timetableDraft->generation_notes)
                            · {{ $timetableDraft->generation_notes }}
                        @endif
                    </p>
                </div>
                @if ($timetableDraft->status === 'draft')
                    @can('academics.write')
                        <form method="POST" action="{{ route('departments.academics.programs.timetable.publish', array_merge($hub, ['program' => $program->id, 'timetable' => $timetableDraft->id])) }}">
                            @csrf
                            <button type="submit" class="tich-btn tich-btn-primary">Publish timetable</button>
                        </form>
                    @endcan
                @endif
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
                'segments' => $timetableTemplate->segments,
            ])

            @if ($timetableDraft->status === 'draft')
                @can('academics.write')
                    <details class="tich-mt-6 tich-inset-panel">
                        <summary class="tich-label" style="cursor:pointer;">Add exam / special session</summary>
                        <form method="POST" action="{{ route('departments.academics.programs.timetable.add-session', array_merge($hub, ['program' => $program->id, 'timetable' => $timetableDraft->id])) }}" class="tich-mt-4">
                            @csrf
                            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                                <div class="tich-form-group">
                                    <label class="tich-label">Session type</label>
                                    <select name="session_type" class="tich-input" required>
                                        @foreach (['exam', 'supplementary', 'special_exam', 'lesson', 'other'] as $typeKey)
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
                                        <option value="">—</option>
                                        @foreach ($catalogUnits as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->unit_code }} — {{ $unit->unit_name }}</option>
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
                                        <option value="">—</option>
                                        @foreach ($timetableRooms as $room)
                                            <option value="{{ $room->id }}">{{ $room->room_code }} — {{ $room->room_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">Lecturer</label>
                                    <select name="staff_id" class="tich-input">
                                        <option value="">—</option>
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
