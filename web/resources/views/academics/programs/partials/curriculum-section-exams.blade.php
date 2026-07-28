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
    @endphp

    <div class="tich-section__intro tich-mb-6" style="text-align:left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Exams &amp; grading - {{ $selectedIntake->intakeLabel() }}</h1>
        <p class="tich-text">Manage semester exams for the units taught in each teaching period. Select a semester to view its units, schedules, papers, and results.</p>
    </div>

    <form method="GET" action="{{ route('departments.academics.programs.curriculum', array_merge($curriculumParams, ['section' => 'exams', 'exam_tab' => $examTab ?? 'overview'])) }}" class="tich-card tich-mb-6" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end;">
        <div class="tich-form-group" style="margin:0; min-width:12rem;">
            <label class="tich-label">Semester</label>
            <select name="teaching_period" class="tich-input" onchange="this.form.submit()">
                @foreach (range(1, $totalTeachingPeriods) as $periodNumber)
                    @php
                        $periodUnitCount = $mappings->where('semester', $periodNumber)->count();
                    @endphp
                    <option value="{{ $periodNumber }}" @selected($examTeachingPeriod === $periodNumber)>
                        Semester {{ $periodNumber }}@if($periodUnitCount > 0) ({{ $periodUnitCount }} {{ str('unit')->plural($periodUnitCount) }})@endif
                    </option>
                @endforeach
            </select>
        </div>
        @if ($examPeriod?->scheduleLabel())
            <div class="tich-caption" style="margin:0;">
                <strong>Semester {{ $examTeachingPeriod }}</strong> · {{ $examPeriod->scheduleLabel() }}
                @if ($examPeriod->exam_start_date || $examPeriod->effectiveExamEnd())
                    · Exams: {{ $examPeriod->exam_start_date?->format('d M Y') ?? '-' }} - {{ $examPeriod->effectiveExamEnd()?->format('d M Y') ?? '-' }}
                @endif
            </div>
        @endif
    </form>

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
                <a href="{{ route('staff.dashboard', ['section' => 'grading']) }}" class="tich-btn tich-btn-primary">Open staff grading terminal</a>
                <a href="{{ route('departments.academics.performance.index', $deptHub) }}" class="tich-btn tich-btn-secondary">HOD performance terminal</a>
            </div>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Unit assessment weighting - Semester {{ $examTeachingPeriod }}</h2>
            <p class="tich-caption">Only units taught in this semester. CAT, practical, attendance, and exam weights drive cumulative score calculation.</p>
            <div style="overflow-x:auto;" class="tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>CAT %</th>
                            <th>Practical %</th>
                            <th>Attendance %</th>
                            <th>Exam %</th>
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
                            </tr>
                        @empty
                            <tr><td colspan="6">No units mapped to Semester {{ $examTeachingPeriod }} yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

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
            <div style="overflow-x:auto;" class="tich-mt-4">
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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($examHub['schedules'] as $schedule)
                            <tr>
                                <td>{{ $schedule->unit_code }}</td>
                                <td>{{ $schedule->semester_label }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($schedule->exam_date)->format('d M Y') }}</td>
                                <td>{{ substr($schedule->start_time, 0, 5) }}-{{ substr($schedule->end_time, 0, 5) }}</td>
                                <td>{{ $schedule->venue }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $schedule->exam_type)) }}</td>
                                <td>{{ trim(($schedule->invigilator_first ?? '').' '.($schedule->invigilator_surname ?? '')) ?: '-' }}</td>
                                <td>{{ ucfirst($schedule->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No exam sessions scheduled for Semester {{ $examTeachingPeriod }} units yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

    @elseif ($examTab === 'papers')
        <article class="tich-card">
            <h2 class="tich-h3">Examination papers - Semester {{ $examTeachingPeriod }}</h2>
            <p class="tich-text tich-mt-2">Draft, moderated, and approved exam papers for units taught this semester.</p>
            <div style="overflow-x:auto;" class="tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>Exam type</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Approved</th>
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
                            </tr>
                        @empty
                            <tr><td colspan="6">No examination papers for Semester {{ $examTeachingPeriod }} units yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

    @elseif ($examTab === 'results')
        <div class="tich-grid tich-grid--2 tich-mb-6" style="align-items:start; gap:1.5rem;">
            <article class="tich-card">
                <h2 class="tich-h3">Exam eligibility</h2>
                <dl class="tich-dl tich-mt-4">
                    <dt>Eligible for exams</dt><dd>{{ $eligibility['eligible'] ?? 0 }}</dd>
                    <dt>Blocked</dt><dd>{{ $eligibility['blocked'] ?? 0 }}</dd>
                    <dt>Pending calculation</dt><dd>{{ $eligibility['pending'] ?? 0 }}</dd>
                </dl>
                <a href="{{ route('departments.academics.attendance-ledger.index', $deptHub) }}" class="tich-link tich-mt-4">Review attendance verification ledger →</a>
            </article>
            <article class="tich-card">
                <h2 class="tich-h3">Transcripts</h2>
                <p class="tich-text tich-mt-2">Generate official transcripts from cumulative grade records and exam results.</p>
                <a href="{{ route('sis.students.index', ['program_id' => $program->id]) }}" class="tich-btn tich-btn-secondary tich-mt-4">Open SIS - generate per student</a>
            </article>
        </div>

        <article class="tich-card tich-mb-6">
            <h2 class="tich-h3">Cumulative grade records - Semester {{ $examTeachingPeriod }}</h2>
            <p class="tich-caption">Compiled from continuous assessment for units taught this semester.</p>
            <div style="overflow-x:auto;" class="tich-mt-4">
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
            <div style="overflow-x:auto;" class="tich-mt-4">
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
@endif
