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
            <div class="tich-form-group" style="margin:0; min-width:12rem;">
                <label for="enrollment_status" class="tich-label">Enrollment status</label>
                <select id="enrollment_status" name="enrollment_status" class="tich-input">
                    <option value="">All</option>
                    @foreach (['active', 'pending', 'deferred', 'suspended', 'withdrawn', 'graduated', 'alumni'] as $status)
                        <option value="{{ $status }}" @selected($enrollmentStatusFilter === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
            <a href="{{ route('departments.academics.programs.curriculum', $curriculumParams) }}" class="tich-btn tich-btn-secondary">Reset</a>
            <a href="{{ route('sis.students.index', ['program_id' => $program->id]) }}" class="tich-link" style="margin-left:auto;">Open in SIS</a>
        </form>

        @if ($otherStudents->isNotEmpty())
            <div class="tich-card tich-mt-4" style="border-left: 3px solid #ca8a04; padding: 1rem 1.25rem;">
                <p class="tich-text" style="margin:0;">
                    <strong>{{ $otherStudents->count() }}</strong>
                    {{ str('student')->plural($otherStudents->count()) }}
                    on this programme {{ $otherStudents->count() === 1 ? 'is' : 'are' }} assigned to a different intake than {{ $selectedIntake->intakeLabel() }}.
                </p>
            </div>
        @endif

        @include('sis.partials.student-roster-table', [
            'students' => $matchedStudents,
            'summaries' => $enrolledSummaries,
            'emptyMessage' => 'No enrolled students for this intake yet. Students appear here after applications are approved and enrollment is created.',
            'detailRoute' => fn ($student) => route('departments.academics.programs.curriculum', array_merge($filterParams, ['student' => $student->id])),
            'sisRoute' => fn ($student) => route('sis.students.show', $student).'#academic-record',
        ])

        @if ($otherStudents->isNotEmpty())
            <div class="tich-mt-8">
                <h3 class="tich-h3">Other intakes on this programme</h3>
                @include('sis.partials.student-roster-table', [
                    'students' => $otherStudents,
                    'summaries' => $enrolledSummaries,
                    'emptyMessage' => '',
                    'detailRoute' => fn ($student) => route('departments.academics.programs.curriculum', array_merge($filterParams, ['student' => $student->id])),
                    'sisRoute' => fn ($student) => route('sis.students.show', $student).'#academic-record',
                ])
            </div>
        @endif
    </article>

    @if ($expandedStudentRecord)
        <article class="tich-card tich-mt-6" id="student-detail">
            <div class="tich-dept-panel__head">
                <h2 class="tich-h3">
                    {{ $expandedStudentRecord['student']->applicant?->fullName() ?? $expandedStudentRecord['student']->registration_number }}
                </h2>
                <p class="tich-text">
                    {{ $expandedStudentRecord['student']->registration_number }}
                    · <a href="{{ route('sis.students.show', $expandedStudentRecord['student']) }}" class="tich-link">Full 360° record</a>
                    · <a href="{{ route('departments.academics.programs.curriculum', $filterParams) }}" class="tich-link">Close</a>
                </p>
            </div>

            @include('sis.partials.student-academic-record', [
                'academics' => $expandedStudentRecord['academics'],
                'compact' => false,
            ])
        </article>
    @endif
@endif
