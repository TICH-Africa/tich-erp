@if (! $selectedIntake)
    <article class="tich-card">
        <p class="tich-text">Select an <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">intake</a> to manage exams, grading, and results for this cohort.</p>
    </article>
@else
    @php
        $examHub = $examHub ?? ['summary' => [], 'eligibility' => []];
        $summary = $examHub['summary'] ?? [];
        $eligibility = $examHub['eligibility'] ?? [];
        $examTeachingPeriod = (int) ($examHub['teaching_period'] ?? 1);
        $examPeriod = $examHub['period'] ?? null;
        $deptHub = array_filter(array_merge($hub, ['learning_department' => $learningDepartment?->id]));
        $examParams = fn (string $tab = 'overview') => array_merge($curriculumParams, [
            'section' => 'exams',
            'exam_tab' => $tab,
            'teaching_period' => $examTeachingPeriod,
        ]);
        $timetableExamParams = array_merge($curriculumParams, [
            'section' => 'timetable',
            'timetable_kind' => 'exam',
            'teaching_period' => $examTeachingPeriod,
        ]);
        $semesterUnitCount = ($examHub['units'] ?? collect())->count();
        $examScheduleUpdateBase = route('departments.academics.programs.exam-schedules.update', array_merge($hub, ['program' => $program->id, 'schedule' => '__ID__']));
        $unitAssessmentUpdateBase = route('departments.academics.programs.units.assessment-weights.update', array_merge($hub, ['program' => $program->id, 'unit' => '__ID__']));
    @endphp

    <x-page-toolbar title="Exams &amp; grading - {{ $selectedIntake->intakeLabel() }}" meta="Semester exams, schedules, and results" class="tich-mb-6">
        <x-slot:filters>
            <form method="GET" action="{{ route('departments.academics.programs.curriculum', array_merge($curriculumParams, ['section' => 'exams', 'exam_tab' => $examTab ?? 'overview'])) }}" class="tich-page-toolbar__filters-form">
                <select name="teaching_period" class="tich-input tich-input--compact" onchange="this.form.submit()">
                    @foreach (range(1, $totalTeachingPeriods) as $periodNumber)
                        @php
                            $periodUnitCount = $mappings->where('semester', $periodNumber)->count();
                        @endphp
                        <option value="{{ $periodNumber }}" @selected($examTeachingPeriod === $periodNumber)>
                            Semester {{ $periodNumber }}@if($periodUnitCount > 0) ({{ $periodUnitCount }} {{ str('unit')->plural($periodUnitCount) }})@endif
                        </option>
                    @endforeach
                </select>
                @if ($examPeriod?->scheduleLabel())
                    <span class="tich-page-toolbar__meta">
                        Semester {{ $examTeachingPeriod }} · {{ $examPeriod->scheduleLabel() }}
                        @if ($examPeriod->exam_start_date || $examPeriod->effectiveExamEnd())
                            · Exams: {{ $examPeriod->exam_start_date?->format('d M Y') ?? '-' }} - {{ $examPeriod->effectiveExamEnd()?->format('d M Y') ?? '-' }}
                        @endif
                    </span>
                @endif
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <nav class="tich-card tich-mb-6" style="display:flex; flex-wrap:wrap; gap:0.5rem; padding:0.75rem 1rem;">
        @foreach ([
            'overview' => 'Overview',
            'grading' => 'Assessment & grading',
            'schedule' => 'Exam schedule',
            'papers' => 'Exam papers',
            'results' => 'Results & transcripts',
        ] as $tabKey => $tabLabel)
            <a href="{{ route('departments.academics.programs.curriculum', $examParams($tabKey)) }}"
               @class(['tich-btn', 'tich-btn-secondary', 'is-active' => ($examTab ?? 'overview') === $tabKey])
               style="{{ ($examTab ?? 'overview') === $tabKey ? 'background:var(--tich-blue); color:#fff; border-color:var(--tich-blue);' : '' }}">
                {{ $tabLabel }}
            </a>
        @endforeach
    </nav>

    @if (($examTab ?? 'overview') === 'overview')
        <div class="tich-grid tich-grid--4 tich-dept-stats tich-mb-6">
            <article class="tich-card tich-stat">
                <p class="tich-caption">Enrolled students</p>
                <p class="tich-stat__value">{{ $summary['students'] ?? 0 }}</p>
            </article>
            <article class="tich-card tich-stat">
                <p class="tich-caption">Semester {{ $examTeachingPeriod }} units</p>
                <p class="tich-stat__value">{{ $semesterUnitCount }}</p>
            </article>
            <article class="tich-card tich-stat">
                <p class="tich-caption">CAT / assessment entries</p>
                <p class="tich-stat__value">{{ $summary['cat_entries'] ?? 0 }}</p>
            </article>
            <article class="tich-card tich-stat">
                <p class="tich-caption">Cumulative grades compiled</p>
                <p class="tich-stat__value">{{ $summary['grade_records'] ?? 0 }}</p>
            </article>
        </div>

        <div class="tich-grid tich-grid--2 tich-mb-6" style="align-items:start; gap:1.5rem;">
            <article class="tich-card">
                <h2 class="tich-h3">Exam readiness</h2>
                <dl class="tich-dl tich-mt-4">
                    <dt>Exam schedules</dt><dd>{{ $summary['schedules'] ?? 0 }}</dd>
                    <dt>Approved exam papers</dt><dd>{{ $summary['papers_ready'] ?? 0 }}</dd>
                    <dt>Exam results captured</dt><dd>{{ $summary['exam_results'] ?? 0 }}</dd>
                    <dt>Eligibility blocked</dt><dd>{{ $summary['blocked_eligibility'] ?? 0 }}</dd>
                </dl>
                <p class="tich-caption tich-mt-4">
                    Eligible: {{ $eligibility['eligible'] ?? 0 }} · Blocked: {{ $eligibility['blocked'] ?? 0 }} · Pending review: {{ $eligibility['pending'] ?? 0 }}
                </p>
            </article>

            <article class="tich-card">
                <h2 class="tich-h3">Department tools</h2>
                <ul class="tich-mt-4" style="list-style:none; padding:0; display:grid; gap:0.65rem;">
                    <li><a href="{{ route('departments.academics.performance.index', $deptHub) }}" class="tich-link">Performance mapping terminal (HOD analytics)</a></li>
                    <li><a href="{{ route('departments.academics.attendance-ledger.index', $deptHub) }}" class="tich-link">Attendance ledger (exam eligibility)</a></li>
                    <li><a href="{{ route('staff.dashboard', ['section' => 'grading']) }}" class="tich-link">Staff grading portal (lecturers)</a></li>
                    <li><a href="{{ route('departments.academics.programs.curriculum', $timetableExamParams) }}" class="tich-link">Exam timetable builder</a></li>
                    <li><a href="{{ route('sis.students.index', ['program_id' => $program->id]) }}" class="tich-link">Student records &amp; transcripts (SIS)</a></li>
                </ul>
            </article>
        </div>

        @if ($semesterUnitCount === 0)
            <article class="tich-card tich-mb-6">
                <p class="tich-text">No units are mapped to Semester {{ $examTeachingPeriod }} yet. Assign units on the <a href="{{ route('departments.academics.programs.curriculum', array_merge($curriculumParams, ['section' => 'semesters'])) }}" class="tich-link">Semester units</a> page first.</p>
            </article>
        @endif

        @if (($examHub['exam_periods'] ?? collect())->isNotEmpty())
            <article class="tich-card tich-mb-6">
                <h2 class="tich-h3">Exam window - Semester {{ $examTeachingPeriod }}</h2>
                <table class="tich-admin-table tich-mt-4">
                    <thead><tr><th>Period</th><th>Exam start</th><th>Exam end</th></tr></thead>
                    <tbody>
                        @foreach ($examHub['exam_periods'] as $period)
                            <tr>
                                <td>Semester {{ $period->semester }}</td>
                                <td>{{ $period->exam_start_date ? \Illuminate\Support\Carbon::parse($period->exam_start_date)->format('d M Y') : '-' }}</td>
                                <td>{{ $period->exam_end_date ? \Illuminate\Support\Carbon::parse($period->exam_end_date)->format('d M Y') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="tich-caption tich-mt-2">Set exam dates under <a href="{{ route('departments.academics.programs.curriculum', array_merge($curriculumParams, ['section' => 'semesters'])) }}" class="tich-link">Semester units</a>.</p>
            </article>
        @endif

        @if (($examHub['at_risk_students'] ?? collect())->isNotEmpty())
            <article class="tich-card">
                <h2 class="tich-h3">At-risk students (cumulative &lt; 40%)</h2>
                <table class="tich-admin-table tich-mt-4">
                    <thead><tr><th>Student</th><th>Unit</th><th>Cumulative</th><th>Grade</th></tr></thead>
                    <tbody>
                        @foreach ($examHub['at_risk_students'] as $row)
                            <tr>
                                <td>{{ $row->registration_number }}</td>
                                <td>{{ $row->unit_code }}</td>
                                <td>{{ number_format((float) $row->final_score, 1) }}%</td>
                                <td>{{ $row->grade_letter }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </article>
        @endif

    @elseif ($examTab === 'grading')
        <article class="tich-card tich-mb-6">
            <h2 class="tich-h3">Continuous assessment &amp; objective auto-grading</h2>
            <p class="tich-text tich-mt-2">Lecturers enter competency spreadsheet marks and run objective auto-grading in the staff portal. Cumulative scores compile automatically into grade records used on transcripts.</p>
            <div class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:1rem;">
                <a href="{{ route('staff.dashboard', ['section' => 'grading']) }}" class="tich-btn tich-btn-primary">Open marks &amp; assessments</a>
                <a href="{{ route('departments.academics.performance.index', $deptHub) }}" class="tich-btn tich-btn-secondary">HOD performance terminal</a>
            </div>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Unit assessment weighting - Semester {{ $examTeachingPeriod }}</h2>
            <p class="tich-caption">Only units taught in this semester. Click Edit to adjust weights in a popup.</p>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>CAT %</th>
                            <th>Practical %</th>
                            <th>Attendance %</th>
                            <th>Exam %</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($examHub['units'] as $unit)
                            <tr>
                                <td>{{ $unit->unit_code }} - {{ $unit->unit_name }}</td>
                                <td>{{ $unit->semester }}</td>
                                <td>{{ $unit->cat_weight }}%</td>
                                <td>{{ $unit->practical_weight }}%</td>
                                <td>{{ $unit->attendance_weight }}%</td>
                                <td>{{ $unit->exam_weight }}%</td>
                                <td>
                                    @can('academics.write')
                                        <button
                                            type="button"
                                            class="tich-link"
                                            data-open-modal="unit-assessment-edit-modal"
                                            data-unit-id="{{ $unit->unit_id }}"
                                            data-unit-label="{{ $unit->unit_code }} - {{ $unit->unit_name }}"
                                            data-cat-weight="{{ $unit->cat_weight }}"
                                            data-practical-weight="{{ $unit->practical_weight }}"
                                            data-attendance-weight="{{ $unit->attendance_weight }}"
                                            data-exam-weight="{{ $unit->exam_weight }}"
                                        >Edit</button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No units mapped to Semester {{ $examTeachingPeriod }} yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        @can('academics.write')
            <div class="tich-modal" id="unit-assessment-edit-modal" hidden aria-hidden="true">
                <div class="tich-modal__backdrop" data-close-modal="unit-assessment-edit-modal"></div>
                <div class="tich-modal__dialog">
                    <header class="tich-modal__header">
                        <h2 class="tich-h3" style="margin:0;">Edit assessment weights</h2>
                        <button type="button" class="tich-modal__close" data-close-modal="unit-assessment-edit-modal" aria-label="Close">&times;</button>
                    </header>
                    <form method="POST" id="unit-assessment-edit-form" action="#" class="tich-modal__body">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                        <input type="hidden" name="teaching_period" value="{{ $examTeachingPeriod }}">
                        <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                        <p class="tich-text" id="unit-assessment-edit-label" style="margin:0 0 1rem;"></p>
                        <div class="tich-grid tich-grid--2" style="gap:1rem;">
                            <div class="tich-form-group">
                                <label class="tich-label">CAT %</label>
                                <input type="number" name="cat_weight" id="unit-assessment-cat" class="tich-input" min="0" max="100" step="0.01" required>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Practical %</label>
                                <input type="number" name="practical_weight" id="unit-assessment-practical" class="tich-input" min="0" max="100" step="0.01" required>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Attendance %</label>
                                <input type="number" name="attendance_weight" id="unit-assessment-attendance" class="tich-input" min="0" max="100" step="0.01" required>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Exam %</label>
                                <input type="number" name="exam_weight" id="unit-assessment-exam" class="tich-input" min="0" max="100" step="0.01" required>
                            </div>
                        </div>
                        @error('weights')<p class="tich-field-error">{{ $message }}</p>@enderror
                        <footer class="tich-modal__footer">
                            <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="unit-assessment-edit-modal">Cancel</button>
                            <button type="submit" class="tich-btn tich-btn-primary">Save weights</button>
                        </footer>
                    </form>
                </div>
            </div>
        @endcan

    @elseif ($examTab === 'schedule')
        <article class="tich-card tich-mb-6">
            <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:start;">
                <div>
                    <h2 class="tich-h3">Exam schedule - Semester {{ $examTeachingPeriod }}</h2>
                    <p class="tich-text tich-mt-2">Exam sessions for units taught in this semester. Generate the visual exam timetable from the units mapped to the same teaching period.</p>
                </div>
                <a href="{{ route('departments.academics.programs.curriculum', $timetableExamParams) }}" class="tich-btn tich-btn-secondary">Exam timetable builder</a>
            </div>

            @if (($examHub['exam_periods'] ?? collect())->isNotEmpty())
                <h3 class="tich-h3 tich-mt-6">Intake exam periods</h3>
                <table class="tich-admin-table tich-mt-2">
                    <thead><tr><th>Period</th><th>Start</th><th>End</th></tr></thead>
                    <tbody>
                        @foreach ($examHub['exam_periods'] as $period)
                            <tr>
                                <td>Semester {{ $period->semester }}</td>
                                <td>{{ $period->exam_start_date ? \Illuminate\Support\Carbon::parse($period->exam_start_date)->format('d M Y') : '-' }}</td>
                                <td>{{ $period->exam_end_date ? \Illuminate\Support\Carbon::parse($period->exam_end_date)->format('d M Y') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </article>

        <article class="tich-card tich-mb-6">
            <h3 class="tich-h3">Semester {{ $examTeachingPeriod }} units</h3>
            <table class="tich-admin-table tich-mt-2">
                <thead><tr><th>Unit</th><th>Scheduled</th><th>Paper</th></tr></thead>
                <tbody>
                    @forelse ($examHub['units'] as $unit)
                        @php
                            $hasSchedule = ($examHub['schedules'] ?? collect())->contains(fn ($s) => (int) $s->unit_id === (int) $unit->unit_id);
                            $hasPaper = ($examHub['papers'] ?? collect())->contains(fn ($p) => (int) $p->unit_id === (int) $unit->unit_id);
                        @endphp
                        <tr>
                            <td>{{ $unit->unit_code }} - {{ $unit->unit_name }}</td>
                            <td>{{ $hasSchedule ? 'Yes' : 'Pending' }}</td>
                            <td>{{ $hasPaper ? 'Yes' : 'Pending' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No units in this semester.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Scheduled sessions</h3>
            @if (($examHub['timetable_synced'] ?? 0) > 0)
                <p class="tich-caption tich-mt-2">Synced {{ $examHub['timetable_synced'] }} session(s) from the exam timetable. Unit, semester, date, time, and type were filled automatically.</p>
            @endif
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Venue</th>
                            <th>Type</th>
                            <th>Invigilator</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($examHub['schedules'] as $schedule)
                            <tr>
                                <td>{{ $schedule->unit_code }}</td>
                                <td>{{ $schedule->semester_label ?? 'Semester '.$examTeachingPeriod }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($schedule->exam_date)->format('d M Y') }}</td>
                                <td>{{ substr($schedule->start_time, 0, 5) }}-{{ substr($schedule->end_time, 0, 5) }}</td>
                                <td>{{ $schedule->venue }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $schedule->exam_type)) }}</td>
                                <td>{{ trim(($schedule->invigilator_first ?? '').' '.($schedule->invigilator_surname ?? '')) ?: '-' }}</td>
                                <td>{{ ucfirst($schedule->status) }}</td>
                                <td>
                                    @can('academics.write')
                                        <button
                                            type="button"
                                            class="tich-link"
                                            data-open-modal="exam-schedule-edit-modal"
                                            data-schedule-id="{{ $schedule->id }}"
                                            data-unit-label="{{ $schedule->unit_code }} - {{ $schedule->unit_name }}"
                                            data-semester-label="{{ $schedule->semester_label ?? 'Semester '.$examTeachingPeriod }}"
                                            data-exam-date="{{ \Illuminate\Support\Carbon::parse($schedule->exam_date)->format('Y-m-d') }}"
                                            data-start-time="{{ substr($schedule->start_time, 0, 5) }}"
                                            data-end-time="{{ substr($schedule->end_time, 0, 5) }}"
                                            data-venue="{{ $schedule->venue }}"
                                            data-exam-type="{{ $schedule->exam_type }}"
                                            data-invigilator-id="{{ $schedule->invigilator_id ?? '' }}"
                                            data-status="{{ $schedule->status }}"
                                        >Edit</button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9">No exam sessions scheduled for Semester {{ $examTeachingPeriod }} units yet. <a href="{{ route('departments.academics.programs.curriculum', $timetableExamParams) }}" class="tich-link">Generate the exam timetable</a> to auto-fill this list.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        @can('academics.write')
            <div class="tich-modal" id="exam-schedule-edit-modal" hidden aria-hidden="true">
                <div class="tich-modal__backdrop" data-close-modal="exam-schedule-edit-modal"></div>
                <div class="tich-modal__dialog">
                    <header class="tich-modal__header">
                        <h2 class="tich-h3" style="margin:0;">Edit exam session</h2>
                        <button type="button" class="tich-modal__close" data-close-modal="exam-schedule-edit-modal" aria-label="Close">&times;</button>
                    </header>
                    <form method="POST" id="exam-schedule-edit-form" action="#" class="tich-modal__body">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                        <input type="hidden" name="teaching_period" value="{{ $examTeachingPeriod }}">
                        <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                        <dl class="tich-dl tich-mb-4">
                            <dt>Unit</dt><dd id="exam-schedule-edit-unit"></dd>
                            <dt>Semester</dt><dd id="exam-schedule-edit-semester"></dd>
                        </dl>
                        <div class="tich-grid tich-grid--2" style="gap:1rem;">
                            <div class="tich-form-group">
                                <label class="tich-label">Date</label>
                                <input type="date" name="exam_date" id="exam-schedule-edit-date" class="tich-input" required>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Type</label>
                                <select name="exam_type" id="exam-schedule-edit-type" class="tich-input" required>
                                    <option value="main">Main</option>
                                    <option value="supplementary">Supplementary</option>
                                    <option value="special">Special</option>
                                    <option value="clinical">Clinical</option>
                                </select>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Start time</label>
                                <input type="time" name="start_time" id="exam-schedule-edit-start" class="tich-input" required>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">End time</label>
                                <input type="time" name="end_time" id="exam-schedule-edit-end" class="tich-input" required>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Venue</label>
                                <input type="text" name="venue" id="exam-schedule-edit-venue" class="tich-input" required>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Invigilator</label>
                                <select name="invigilator_id" id="exam-schedule-edit-invigilator" class="tich-input">
                                    <option value="">-</option>
                                    @foreach ($examStaff ?? [] as $member)
                                        <option value="{{ $member->id }}">{{ $member->first_name }} {{ $member->surname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Status</label>
                                <select name="status" id="exam-schedule-edit-status" class="tich-input" required>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="in_progress">In progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        @error('schedule')<p class="tich-field-error">{{ $message }}</p>@enderror
                        <footer class="tich-modal__footer">
                            <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="exam-schedule-edit-modal">Cancel</button>
                            <button type="submit" class="tich-btn tich-btn-primary">Save session</button>
                        </footer>
                    </form>
                </div>
            </div>
        @endcan

    @elseif ($examTab === 'papers')
        @php
            $canModeratePapers = auth()->user()?->hasAnyRole(['Academic Registrar', 'HOD', 'Super Admin', 'Head of Academics']);
            $paperHub = \App\Support\AcademicsRouteParams::fromRequest(request());
        @endphp
        <article class="tich-card">
            <h2 class="tich-h3">Examination papers - Semester {{ $examTeachingPeriod }}</h2>
            <p class="tich-text tich-mt-2">Draft, moderated, and approved exam papers for units taught this semester.</p>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>Exam type</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Approved</th>
                            <th>Files / actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($examHub['papers'] as $paper)
                            <tr>
                                <td>{{ $paper->unit_code }} - {{ $paper->unit_name }}</td>
                                <td>{{ $paper->semester_label }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $paper->exam_type)) }}</td>
                                <td>{{ $paper->version }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $paper->status)) }}</td>
                                <td>{{ $paper->approved_at ? \Illuminate\Support\Carbon::parse($paper->approved_at)->format('d M Y') : '-' }}</td>
                                <td style="display:flex; flex-wrap:wrap; gap:0.35rem; align-items:center;">
                                    @if ($paper->draft_file_path)
                                        <a href="{{ route('departments.academics.examination-papers.download', array_merge($paperHub, ['examinationPaper' => $paper->id, 'kind' => 'draft'])) }}" class="tich-btn tich-btn-ghost">Draft</a>
                                    @endif
                                    @if ($paper->moderated_file_path)
                                        <a href="{{ route('departments.academics.examination-papers.download', array_merge($paperHub, ['examinationPaper' => $paper->id, 'kind' => 'moderated'])) }}" class="tich-btn tich-btn-ghost">Moderated</a>
                                    @endif
                                    @if ($paper->approved_file_path)
                                        <a href="{{ route('departments.academics.examination-papers.download', array_merge($paperHub, ['examinationPaper' => $paper->id, 'kind' => 'approved'])) }}" class="tich-btn tich-btn-ghost">Approved</a>
                                    @endif
                                    @if ($canModeratePapers && $paper->status === 'tabled')
                                        <form method="POST" action="{{ route('departments.academics.examination-papers.moderate', array_merge($paperHub, ['examinationPaper' => $paper->id])) }}" enctype="multipart/form-data" style="display:flex; gap:0.35rem; align-items:center;">
                                            @csrf
                                            <input type="file" name="moderated_file" class="tich-input" style="max-width:9rem;" accept=".pdf,.doc,.docx">
                                            <button type="submit" class="tich-btn tich-btn-secondary">Moderate</button>
                                        </form>
                                    @endif
                                    @if ($canModeratePapers && $paper->status === 'moderated')
                                        <form method="POST" action="{{ route('departments.academics.examination-papers.approve', array_merge($paperHub, ['examinationPaper' => $paper->id])) }}" enctype="multipart/form-data" style="display:flex; gap:0.35rem; align-items:center;">
                                            @csrf
                                            <input type="file" name="approved_file" class="tich-input" style="max-width:9rem;" accept=".pdf,.doc,.docx">
                                            <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No examination papers for Semester {{ $examTeachingPeriod }} units yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

    @elseif ($examTab === 'results')
        @php
            $eligibleRoster = $examHub['eligible_roster'] ?? ['eligible' => collect(), 'blocked' => collect(), 'pending' => collect()];
            $eligibleStudents = $eligibleRoster['eligible'] ?? collect();
            $blockedStudents = $eligibleRoster['blocked'] ?? collect();
            $pendingStudents = $eligibleRoster['pending'] ?? collect();
        @endphp

        <div class="tich-grid tich-grid--2 tich-mb-6" style="align-items:start; gap:1.5rem;">
            <article class="tich-card">
                <h2 class="tich-h3">Exam eligibility summary</h2>
                <dl class="tich-dl tich-mt-4">
                    <dt>Eligible unit entries</dt><dd>{{ $eligibility['eligible'] ?? 0 }}</dd>
                    <dt>Blocked unit entries</dt><dd>{{ $eligibility['blocked'] ?? 0 }}</dd>
                    <dt>Pending calculation</dt><dd>{{ $eligibility['pending'] ?? 0 }}</dd>
                    <dt>Students cleared for exams</dt><dd>{{ $eligibleStudents->count() }}</dd>
                </dl>
                <a href="{{ route('departments.academics.attendance-ledger.index', $deptHub) }}" class="tich-link tich-mt-4">Review attendance verification ledger →</a>
            </article>
            <article class="tich-card">
                <h2 class="tich-h3">Transcripts</h2>
                <p class="tich-text tich-mt-2">Generate official transcripts from cumulative grade records and exam results for each eligible student below.</p>
                <a href="{{ route('sis.students.index', ['program_id' => $program->id]) }}" class="tich-btn tich-btn-secondary tich-mt-4">Open SIS - all students</a>
            </article>
        </div>

        <article class="tich-card tich-mb-6">
            <h2 class="tich-h3">Exam-eligible students - Semester {{ $examTeachingPeriod }}</h2>
            <p class="tich-caption">
                {{ $eligibleRoster['semester_label'] ?? 'Teaching period '.$examTeachingPeriod }} ·
                Students with at least one unit cleared for exam sitting (attendance, fees, and verification checks).
            </p>

            @forelse ($eligibleStudents as $studentRow)
                <div class="tich-inset-panel tich-mt-4">
                    <div style="display:flex; flex-wrap:wrap; gap:1rem; justify-content:space-between; align-items:start;">
                        <div>
                            <h3 class="tich-h3" style="font-size:1.1rem;">{{ $studentRow->student_name ?: 'Student' }}</h3>
                            <p class="tich-caption">{{ $studentRow->registration_number }} · {{ $studentRow->cohort_intake }} · {{ ucfirst($studentRow->enrollment_status) }}</p>
                            <p class="tich-caption">
                                Campus: {{ $studentRow->campus_name ?? '-' }} ·
                                Fees: {{ ucfirst(str_replace('_', ' ', $studentRow->fee_clearance_status)) }} ·
                                {{ $studentRow->eligible_unit_count }}/{{ $studentRow->total_units }} units eligible
                            </p>
                        </div>
                        <a href="{{ route('sis.students.transcript', $studentRow->student_id) }}" target="_blank" class="tich-btn tich-btn-secondary">Generate transcript</a>
                    </div>

                    <div class="tich-table-wrap tich-mt-4">
                        <table class="tich-admin-table">
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th>Attendance</th>
                                    <th>Flag</th>
                                    <th>Fees</th>
                                    <th>Exam eligibility</th>
                                    <th>Cumulative (CA)</th>
                                    <th>Exam schedule</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($studentRow->units as $unitRow)
                                    <tr>
                                        <td>
                                            <strong>{{ $unitRow->unit_code }}</strong>
                                            <span class="tich-caption" style="display:block;">{{ $unitRow->unit_name }}</span>
                                        </td>
                                        <td>{{ $unitRow->attendance_percentage !== null ? number_format((float) $unitRow->attendance_percentage, 1).'%' : '-' }}</td>
                                        <td>
                                            @if ($unitRow->status_flag)
                                                @include('partials.attendance-flag', ['flag' => $unitRow->status_flag])
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $unitRow->fee_cleared ? 'Cleared' : 'Pending' }}</td>
                                        <td>
                                            @if ($unitRow->eligible_for_exams === true)
                                                <span class="tich-caption">Eligible</span>
                                            @elseif ($unitRow->eligible_for_exams === false)
                                                <span class="tich-caption">{{ $unitRow->block_reason ?? 'Blocked' }}</span>
                                            @else
                                                <span class="tich-caption">{{ $unitRow->block_reason ?? 'Pending' }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($unitRow->cumulative_score !== null)
                                                {{ number_format((float) $unitRow->cumulative_score, 1) }}% ({{ $unitRow->grade_letter ?? '-' }})
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($unitRow->exam_date)
                                                {{ \Illuminate\Support\Carbon::parse($unitRow->exam_date)->format('d M Y') }}
                                                {{ substr((string) $unitRow->start_time, 0, 5) }}-{{ substr((string) $unitRow->end_time, 0, 5) }}
                                                <span class="tich-caption" style="display:block;">{{ $unitRow->venue ?? '-' }}</span>
                                            @else
                                                Not scheduled
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="tich-text tich-mt-4">No students are currently cleared for exams in Semester {{ $examTeachingPeriod }}. Ensure attendance is verified and eligibility has been calculated.</p>
            @endforelse
        </article>

        @if ($blockedStudents->isNotEmpty())
            <article class="tich-card tich-mb-6">
                <h2 class="tich-h3">Blocked from exams</h2>
                <p class="tich-caption">Students with no eligible units for this semester.</p>
                <div class="tich-table-wrap tich-mt-4">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Registration</th>
                                <th>Fees</th>
                                <th>Units blocked</th>
                                <th>Primary reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($blockedStudents as $studentRow)
                                @php
                                    $firstBlocked = collect($studentRow->units)->first(fn ($u) => $u->eligible_for_exams === false);
                                @endphp
                                <tr>
                                    <td>{{ $studentRow->student_name }}</td>
                                    <td>{{ $studentRow->registration_number }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $studentRow->fee_clearance_status)) }}</td>
                                    <td>{{ $studentRow->blocked_unit_count }}/{{ $studentRow->total_units }}</td>
                                    <td>{{ $firstBlocked?->block_reason ?? 'Not eligible' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        @endif

        @if ($pendingStudents->isNotEmpty())
            <article class="tich-card tich-mb-6">
                <h2 class="tich-h3">Pending eligibility review</h2>
                <p class="tich-caption">{{ $pendingStudents->count() }} student(s) awaiting attendance verification or eligibility calculation.</p>
            </article>
        @endif

        <article class="tich-card tich-mb-6">
            <h2 class="tich-h3">Cumulative grade records - Semester {{ $examTeachingPeriod }}</h2>
            <p class="tich-caption">Compiled from continuous assessment for units taught this semester.</p>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>Cumulative</th>
                            <th>Grade</th>
                            <th>GP</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($examHub['grade_rows'] as $row)
                            <tr>
                                <td>{{ $row->registration_number }}</td>
                                <td>{{ $row->unit_code }}</td>
                                <td>{{ $row->semester_label }}</td>
                                <td>{{ number_format((float) $row->final_score, 1) }}%</td>
                                <td>{{ $row->grade_letter }}</td>
                                <td>{{ number_format((float) $row->grade_points, 1) }}</td>
                                <td>
                                    <a href="{{ route('sis.students.transcript', $row->student_id) }}" target="_blank" class="tich-link">Transcript</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No cumulative grades for Semester {{ $examTeachingPeriod }} units yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Final exam results - Semester {{ $examTeachingPeriod }}</h2>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Unit</th>
                            <th>CAT</th>
                            <th>Practical</th>
                            <th>Exam</th>
                            <th>Final</th>
                            <th>Grade</th>
                            <th>Published</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($examHub['exam_results'] as $result)
                            <tr>
                                <td>{{ $result->registration_number }}</td>
                                <td>{{ $result->unit_code }}</td>
                                <td>{{ number_format((float) $result->cat_total, 1) }}</td>
                                <td>{{ number_format((float) $result->practical_total, 1) }}</td>
                                <td>{{ number_format((float) $result->final_exam_score, 1) }}</td>
                                <td><strong>{{ number_format((float) $result->final_total_score, 1) }}</strong></td>
                                <td>{{ $result->grade_letter ?? '-' }}</td>
                                <td>{{ $result->is_published ? 'Yes' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No final exam results for Semester {{ $examTeachingPeriod }} units yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    @endif

    @if (in_array($examTab ?? 'overview', ['schedule', 'grading'], true))
        @include('admin.partials.tich-modal-assets')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var scheduleForm = document.getElementById('exam-schedule-edit-form');
            var scheduleBase = @json($examScheduleUpdateBase);

            document.querySelectorAll('[data-open-modal="exam-schedule-edit-modal"]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!scheduleForm) return;
                    scheduleForm.action = scheduleBase.replace('__ID__', button.getAttribute('data-schedule-id'));
                    document.getElementById('exam-schedule-edit-unit').textContent = button.getAttribute('data-unit-label') || '';
                    document.getElementById('exam-schedule-edit-semester').textContent = button.getAttribute('data-semester-label') || '';
                    document.getElementById('exam-schedule-edit-date').value = button.getAttribute('data-exam-date') || '';
                    document.getElementById('exam-schedule-edit-start').value = button.getAttribute('data-start-time') || '';
                    document.getElementById('exam-schedule-edit-end').value = button.getAttribute('data-end-time') || '';
                    document.getElementById('exam-schedule-edit-venue').value = button.getAttribute('data-venue') || '';
                    document.getElementById('exam-schedule-edit-type').value = button.getAttribute('data-exam-type') || 'main';
                    document.getElementById('exam-schedule-edit-status').value = button.getAttribute('data-status') || 'scheduled';
                    document.getElementById('exam-schedule-edit-invigilator').value = button.getAttribute('data-invigilator-id') || '';
                    window.tichOpenModal('exam-schedule-edit-modal');
                });
            });

            var unitForm = document.getElementById('unit-assessment-edit-form');
            var unitBase = @json($unitAssessmentUpdateBase);

            document.querySelectorAll('[data-open-modal="unit-assessment-edit-modal"]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!unitForm) return;
                    unitForm.action = unitBase.replace('__ID__', button.getAttribute('data-unit-id'));
                    document.getElementById('unit-assessment-edit-label').textContent = button.getAttribute('data-unit-label') || '';
                    document.getElementById('unit-assessment-cat').value = button.getAttribute('data-cat-weight') || '';
                    document.getElementById('unit-assessment-practical').value = button.getAttribute('data-practical-weight') || '';
                    document.getElementById('unit-assessment-attendance').value = button.getAttribute('data-attendance-weight') || '';
                    document.getElementById('unit-assessment-exam').value = button.getAttribute('data-exam-weight') || '';
                    window.tichOpenModal('unit-assessment-edit-modal');
                });
            });
        });
        </script>
    @endif
@endif
