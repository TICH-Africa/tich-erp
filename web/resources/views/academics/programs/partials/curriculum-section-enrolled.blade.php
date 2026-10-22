@if (! $selectedIntake)
    <article class="tich-card">
        <p class="tich-text">Select an <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">intake</a> to view enrolled students for that cohort.</p>
    </article>
@else
    @php
        $matchedStudents = $enrolledStudents['matched'] ?? collect();
        $otherStudents = $enrolledStudents['other'] ?? collect();
        $filterParams = array_filter(array_merge($curriculumParams, [
            'enrollment_status' => $enrollmentStatusFilter,
        ]));
        $desk = $studentRegistration ?? null;
        $deskStudents = $desk['students'] ?? collect();
        $deskUnits = $desk['periodUnits'] ?? collect();
        $deskPeriod = $desk['selectedPeriod'] ?? null;
        $registeredByStudent = $desk['registeredUnitIds'] ?? collect();
        // Opening a student's record is a read-up: the intake desk and the other cohorts
        // belong to the programme view, not to that one student.
        $recordOpen = (bool) ($expandedStudentRecord ?? null);
    @endphp

    <article class="tich-card">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h3">Enrolled students - {{ $selectedIntake->intakeLabel() }}</h2>
            <p class="tich-text">
                Students enrolled on {{ $program->program_name }} for this intake.
                View registered units, CAT marks, exam results, and grades.
            </p>
        </div>

        <form method="GET" action="{{ route('departments.academics.programs.curriculum', $curriculumParams) }}" class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
            <input type="hidden" name="section" value="enrolled">
            <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
            @if ($learningDepartment)
                <input type="hidden" name="learning_department" value="{{ $learningDepartment->id }}">
            @endif
            @if ($deskPeriod ?? null)
                <input type="hidden" name="registration_period" value="{{ $desk['selectedPeriodKey'] }}">
            @endif

            <div class="tich-form-group" style="margin:0; min-width:12rem;">
                <label for="enrollment_status" class="tich-label">Enrollment status</label>
                <select id="enrollment_status" name="enrollment_status" class="tich-input">
                    <option value="">All</option>
                    @foreach (['active', 'pending', 'deferred', 'suspended', 'withdrawn', 'graduated', 'alumni'] as $status)
                        <option value="{{ $status }}" @selected($enrollmentStatusFilter === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tich-form-group" style="margin:0; min-width:10rem;">
                <label for="admission_year" class="tich-label">Admitted year</label>
                <select id="admission_year" name="admission_year" class="tich-input">
                    <option value="">All years</option>
                    @foreach ($desk['admissionYears'] ?? [] as $year)
                        <option value="{{ $year }}" @selected((int) ($desk['yearFilter'] ?? 0) === $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tich-form-group" style="margin:0; min-width:11rem;">
                <label for="study_year" class="tich-label">Year of study</label>
                <select id="study_year" name="study_year" class="tich-input">
                    <option value="">All years</option>
                    @foreach (($desk['studyYears'] ?? []) as $year)
                        <option value="{{ $year }}" @selected((int) ($desk['studyYearFilter'] ?? 0) === (int) $year)>Year {{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tich-form-group" style="margin:0; min-width:11rem;">
                <label for="entry_pathway" class="tich-label">Admission pathway</label>
                <select id="entry_pathway" name="entry_pathway" class="tich-input">
                    <option value="">All pathways</option>
                    @foreach ($desk['pathways'] ?? [] as $pathway)
                        <option value="{{ $pathway }}" @selected(($desk['pathwayFilter'] ?? null) === $pathway)>{{ ucfirst(str_replace('_', ' ', $pathway)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tich-form-group" style="margin:0; min-width:12rem;">
                <label for="student_search" class="tich-label">Reg number or name</label>
                <input id="student_search" type="search" name="student_search" class="tich-input" value="{{ $desk['search'] ?? '' }}" placeholder="e.g. REG-2026">
            </div>

            <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
            <a href="{{ route('departments.academics.programs.curriculum', $curriculumParams) }}" class="tich-btn tich-btn-secondary">Reset</a>
            <a href="{{ route('sis.students.index', ['program_id' => $program->id]) }}" class="tich-link" style="margin-left:auto;">Open in SIS</a>
        </form>
    </article>

    @can('academics.write')
        @unless ($recordOpen)
        <article class="tich-card tich-mt-6" id="unit-registration">
            <div class="tich-dept-panel__head">
                <h2 class="tich-h3">Register units for this intake</h2>
                <p class="tich-text">
                    Pick the teaching period, tick the units the students are due to do, then tick the students.
                    Each student keeps one registration per semester, so re-running this only adds what is missing.
                    The list holds the students sitting the year of that period, read from their admission details,
                    and a cohort moves to the next semester by itself when that semester's dates start - anyone
                    owed a failed unit is held on their current semester instead.
                </p>
            </div>

            @if ($deskPeriod && $desk['semesterId'])
                <form method="GET" action="{{ route('departments.academics.programs.curriculum', $curriculumParams) }}" class="tich-flex-wrap tich-mt-4" style="gap:0.75rem; align-items:flex-end;">
                    @foreach (array_filter([
                        'section' => 'enrolled',
                        'intake' => $selectedIntake->id,
                        'learning_department' => $learningDepartment?->id,
                        'enrollment_status' => $enrollmentStatusFilter,
                        'admission_year' => $desk['yearFilter'],
                        'study_year' => $desk['studyYearFilter'],
                        'entry_pathway' => $desk['pathwayFilter'],
                        'student_search' => $desk['search'],
                    ]) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <div class="tich-form-group" style="margin:0; min-width:14rem;">
                        <label for="registration_period" class="tich-label">Teaching period</label>
                        <select id="registration_period" name="registration_period" class="tich-select" onchange="this.form.submit()">
                            @foreach ($desk['periodOptions'] as $key => $option)
                                <option value="{{ $key }}" @selected($key === $desk['selectedPeriodKey'])>
                                    {{ $option['label'] }}{{ (($desk['unitsByPeriod'][$key] ?? 0) > 0) ? ' ('.$desk['unitsByPeriod'][$key].' units)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="tich-btn tich-btn-secondary">Show units</button>

                    <p class="tich-text" style="margin:0 0 0.2rem auto; font-size:0.8125rem;">
                        {{ $deskStudents->count() }} {{ str('student')->plural($deskStudents->count()) }} match the filters
                        · {{ collect($desk['unitsByPeriod'] ?? [])->filter()->count() }} of {{ $desk['periodOptions']->count() }}
                        teaching periods carry units for this intake
                    </p>
                </form>

                @if ($deskUnits->isEmpty())
                    @php
                        $populatedPeriods = collect($desk['unitsByPeriod'] ?? [])
                            ->filter(fn (int $count) => $count > 0)
                            ->map(fn (int $count, string $key) => [
                                'key' => $key,
                                'label' => $desk['periodOptions'][$key]['label'] ?? $key,
                                'count' => $count,
                            ]);
                    @endphp

                    <div class="tich-inset-panel tich-mt-4">
                        <p class="tich-text" style="margin:0;">
                            {{ $deskPeriod['label'] }} has no units mapped for {{ $selectedIntake->intakeLabel() }}.
                            @if ($populatedPeriods->isNotEmpty())
                                These periods do have units, so pick one of them above:
                                @foreach ($populatedPeriods as $periodLink)
                                    <a class="tich-link" href="{{ route('departments.academics.programs.curriculum', array_filter(array_merge($curriculumParams, [
                                        'section' => 'enrolled',
                                        'intake' => $selectedIntake->id,
                                        'registration_period' => $periodLink['key'],
                                        'enrollment_status' => $enrollmentStatusFilter,
                                        'study_year' => $desk['studyYearFilter'],
                                    ]))) }}">{{ $periodLink['label'] }} ({{ $periodLink['count'] }})</a>@if(! $loop->last) · @endif
                                @endforeach
                            @else
                                <a class="tich-link" href="{{ route('departments.academics.programs.curriculum', array_filter(array_merge($curriculumParams, ['section' => 'semesters']))) }}">
                                    Map units in the Semesters tab</a> first, then come back to register students.
                            @endif
                            The "own year" button below still works: it reads the units mapped to each student's own year.
                        </p>
                    </div>
                @endif

                @if ($deskStudents->isEmpty())
                    <p class="tich-caption tich-mt-4">No students match these filters for this intake. Widen the filters above, or admit the students first.</p>
                @else
                    <form method="POST" action="{{ route('departments.academics.programs.student-registrations.store', array_merge($hub, ['program' => $program->id])) }}" class="tich-mt-4">
                        @csrf
                        <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                        <input type="hidden" name="registration_period" value="{{ $desk['selectedPeriodKey'] }}">
                        @if ($learningDepartment)
                            <input type="hidden" name="learning_department" value="{{ $learningDepartment->id }}">
                        @endif

                        <div class="tich-grid" style="display:grid; gap:1.25rem; grid-template-columns:repeat(auto-fit, minmax(20rem, 1fr));">
                            <div>
                                <h3 class="tich-h4">Units in {{ $deskPeriod['label'] }}</h3>
                                @if ($deskUnits->isEmpty())
                                    <p class="tich-caption">Nothing is mapped to this teaching period yet, so tick the students and use the "own year" button.</p>
                                @else
                                    <p class="tich-caption">Lecturers shown are the ones Academics has approved for that unit this semester.</p>
                                @endif
                                @if ($deskUnits->isNotEmpty())
                                <div style="max-height:24rem; overflow:auto; border:1px solid var(--tich-border, #e2e8f0); border-radius:0.5rem;">
                                    <ul class="tich-catalog-alloc-list" style="list-style:none; margin:0; padding:0;">
                                        @foreach ($deskUnits as $mapping)
                                            @php
                                                $unit = $mapping->unit;
                                                $lecturer = $desk['lecturersByUnit'][(int) $mapping->unit_id] ?? null;
                                                $creditHours = (float) ($mapping->credit_hours ?: $unit?->credit_hours);
                                                $alreadyOn = $registeredByStudent->filter(fn ($ids) => collect($ids)->contains((int) $mapping->unit_id))->count();
                                            @endphp
                                            <li class="tich-catalog-alloc-card">
                                                <label class="tich-label" style="display:flex; gap:0.5rem; align-items:flex-start;">
                                                    <input type="checkbox" name="unit_ids[]" value="{{ $mapping->unit_id }}" class="tich-checkbox" data-reg-unit checked>
                                                    <span>
                                                        <strong>{{ $unit?->unit_code }}</strong> · {{ $unit?->unit_name }}
                                                        <span class="tich-caption" style="display:block;">
                                                            {{ $creditHours > 0 ? $creditHours.' credit hours' : 'credit hours not set' }}
                                                            · {{ $lecturer ? 'Lecturer: '.$lecturer : 'No lecturer allocated yet' }}
                                                            @if ($alreadyOn > 0)
                                                                · {{ $alreadyOn }} already registered
                                                            @endif
                                                        </span>
                                                    </span>
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>

                            <div>
                                <h3 class="tich-h4">Students</h3>
                                <p class="tich-caption">
                                    <button type="button" class="tich-link" data-reg-toggle="students">Select all</button>
                                    · {{ $deskStudents->count() }} listed, intake {{ $selectedIntake->intakeLabel() }} first
                                </p>
                                <div style="max-height:24rem; overflow:auto; border:1px solid var(--tich-border, #e2e8f0); border-radius:0.5rem;">
                                    <table class="tich-admin-table">
                                        <tbody>
                                            @foreach ($deskStudents->groupBy(fn ($student) => (int) ($desk['yearsByStudent'][$student->id]['year'] ?? 0))->sortKeys(SORT_NUMERIC) as $deskYear => $yearStudents)
                                                <tr style="background:var(--tich-surface-hover, #f1f5f9);">
                                                    <td colspan="3">
                                                        <strong>{{ $deskYear > 0 ? 'Year '.$deskYear : 'Year of study not derived' }}</strong>
                                                        <span class="tich-caption"> · tick the students to register them on {{ $deskPeriod['label'] }}</span>
                                                    </td>
                                                </tr>
                                                @foreach ($yearStudents as $student)
                                                    @php
                                                        $unitIds = $registeredByStudent->get($student->id, collect());
                                                        $inIntake = \App\Support\IntakeIdentity::studentMatchesIntake($student, $selectedIntake);
                                                        $yearInfo = ($desk['yearsByStudent'] ?? collect())->get($student->id);
                                                        $hold = ($desk['holds'] ?? [])[$student->id] ?? null;
                                                    @endphp
                                                    <tr>
                                                        <td style="width:2.25rem;">
                                                            <input type="checkbox" name="students[]" value="{{ $student->id }}" class="tich-checkbox" data-reg-student>
                                                        </td>
                                                        <td>
                                                            <strong>{{ $student->displayName() }}</strong>
                                                            @if ($yearInfo)
                                                                <span class="tich-badge">{{ $yearInfo['period_label'] }}{{ $yearInfo['overridden'] ? ' (pinned)' : '' }}</span>
                                                            @endif
                                                            @if ($hold && $hold['held'])
                                                                <span class="tich-badge">held on {{ $program->periodLabel($hold['sitting']) }} - owes {{ implode(', ', $hold['owed']) }}</span>
                                                            @endif
                                                            <span class="tich-caption" style="display:block;">
                                                                {{ $student->registration_number }}
                                                                · {{ $student->campus?->campus_name ?? 'campus not set' }}
                                                                · {{ $student->cohort_intake ?: 'no cohort' }}
                                                                · {{ str_replace('_', ' ', $student->entry_pathway ?: 'regular') }}
                                                                · admitted {{ $student->date_of_admission ? \Illuminate\Support\Carbon::parse($student->date_of_admission)->format('M Y') : 'not set' }}
                                                                @if ($yearInfo)
                                                                    · {{ $yearInfo['basis'] }}
                                                                @endif
                                                                @if (! $inIntake)
                                                                    · <span class="tich-badge">other intake</span>
                                                                @endif
                                                            </span>
                                                        </td>
                                                        <td class="tich-text-right" style="white-space:nowrap;">
                                                            @if ($deskUnits->isNotEmpty())
                                                                {{ $unitIds->count() }}/{{ $deskUnits->count() }} registered
                                                            @else
                                                                no units in this period
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="tich-flex-wrap tich-mt-4" style="gap:0.75rem;">
                            @if ($deskUnits->isNotEmpty())
                                <button type="submit" class="tich-btn tich-btn-primary">Register selected students</button>
                            @endif
                            <button type="submit" class="tich-btn {{ $deskUnits->isEmpty() ? 'tich-btn-primary' : 'tich-btn-secondary' }}"
                                formaction="{{ route('departments.academics.programs.student-registrations.by-year', array_merge($hub, ['program' => $program->id])) }}"
                                formnovalidate>
                                Register each student on their own year
                            </button>
                            <p class="tich-text" style="margin:0; font-size:0.8125rem;">
                                Students see these units on their portal straight away, with the lecturer Academics has approved.
                                The "own year" button ignores the period above and places each student on the units mapped to the
                                year their admission date gives them, so a mixed cohort registers in one go.
                            </p>
                        </div>
                    </form>

                    <script>
                        (function () {
                            const toggle = document.querySelector('[data-reg-toggle="students"]');

                            if (! toggle) {
                                return;
                            }

                            toggle.addEventListener('click', function () {
                                const boxes = document.querySelectorAll('[data-reg-student]');
                                const checkAll = Array.from(boxes).some((box) => ! box.checked);

                                boxes.forEach((box) => { box.checked = checkAll; });
                                toggle.textContent = checkAll ? 'Clear selection' : 'Select all';
                            });
                        })();
                    </script>
                @endif
            @else
                <p class="tich-caption tich-mt-4">
                    This intake has no calendar semester for the selected teaching period yet. Set the period dates in the
                    <a class="tich-link" href="{{ route('departments.academics.programs.curriculum', array_filter(array_merge($curriculumParams, ['section' => 'semesters']))) }}">Semesters tab</a>.
                </p>
            @endif
        </article>
        @endunless
    @endcan

    <article class="tich-card tich-mt-6">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h3">Student records</h2>
            <p class="tich-text">
                Registered units, CAT marks, exam results and grades per student, grouped by the year of study they are
                sitting and then by the campus they are enrolled at.
                Everyone listed is on {{ $program->program_code }} under {{ $program->department?->dept_name }}.
            </p>
        </div>

        @unless ($recordOpen)
            @if ($otherStudents->isNotEmpty())
                <div class="tich-card tich-mt-4" style="border-left: 3px solid #ca8a04; padding: 1rem 1.25rem;">
                    <p class="tich-text" style="margin:0;">
                        <strong>{{ $otherStudents->count() }}</strong>
                        {{ str('student')->plural($otherStudents->count()) }}
                        on this programme {{ $otherStudents->count() === 1 ? 'is' : 'are' }} assigned to a different intake than {{ $selectedIntake->intakeLabel() }}.
                    </p>
                </div>
            @endif
        @endunless

        @php
            $rosterDetailRoute = fn ($student) => route('departments.academics.programs.curriculum', array_merge($filterParams, ['student' => $student->id]));
            $rosterSisRoute = fn ($student) => route('sis.students.show', $student).'#academic-record';
        @endphp

        @include('sis.partials.student-roster-table', [
            'students' => $matchedStudents,
            'summaries' => $enrolledSummaries,
            'yearsByStudent' => $enrolledYears ?? collect(),
            'groupByCampus' => true,
            'emptyMessage' => 'No enrolled students for this intake yet. Students appear here after applications are approved and enrollment is created.',
            'detailRoute' => $rosterDetailRoute,
            'sisRoute' => $rosterSisRoute,
        ])

        @if ($otherStudents->isNotEmpty() && ! $recordOpen)
            <div class="tich-mt-8">
                <h3 class="tich-h3">Other intakes on this programme</h3>
                @include('sis.partials.student-roster-table', [
                    'students' => $otherStudents,
                    'summaries' => $enrolledSummaries,
                    'yearsByStudent' => $enrolledYears ?? collect(),
                    'groupByCampus' => true,
                    'emptyMessage' => '',
                    'detailRoute' => $rosterDetailRoute,
                    'sisRoute' => $rosterSisRoute,
                ])
            </div>
        @endif
    </article>

    @if ($expandedStudentRecord)
        <article class="tich-card tich-mt-6" id="student-detail">
            <div class="tich-dept-panel__head">
                <h2 class="tich-h3">
                    {{ $expandedStudentRecord['student']->displayName() }}
                </h2>
                <p class="tich-text">
                    {{ $expandedStudentRecord['student']->registration_number }}
                    · <a href="{{ route('sis.students.show', $expandedStudentRecord['student']) }}" class="tich-link">Full 360° record</a>
                    · <a href="{{ route('departments.academics.programs.curriculum', $filterParams) }}" class="tich-link">Close</a>
                </p>
                <p class="tich-caption" style="margin:0.35rem 0 0;">
                    This record shows the student's own profile and performance only - not the intake desk or the other cohorts.
                    Unit corrections below act on this student alone. Close the record to use the intake registration desk.
                </p>
            </div>

            @include('sis.partials.student-academic-record', [
                'student' => $expandedStudentRecord['student'],
                'academics' => $expandedStudentRecord['academics'],
                'performance' => $expandedStudentRecord['performance'],
                'compact' => false,
            ])

            @can('academics.write')
                @if ($studentCorrections && $selectedIntake)
                    @include('academics.programs.partials.student-unit-corrections')
                @endif
            @endcan
        </article>
    @endif
@endif
