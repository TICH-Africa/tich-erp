@php
    $academics = $academics ?? [];
    $compact = $compact ?? false;
    $performance = $performance ?? [];
    $recordStudent = $student ?? null;
@endphp

<div @class(['tich-student-academic-record', 'tich-student-academic-record--compact' => $compact])>
    @if ($recordStudent)
        @php
            $recordStudent->loadMissing(['applicant', 'program.department', 'campus', 'currentSemester']);
            $recordApplicant = $recordStudent->applicant;
            $profileRows = array_filter([
                'Programme' => $recordStudent->program?->program_name,
                'Campus' => $recordStudent->campus?->campus_name,
                'Study mode' => $recordStudent->studyModeLabel().' student',
                'Intake' => $recordStudent->cohort_intake,
                'Entry pathway' => $recordStudent->entry_pathway ? str_replace('_', ' ', $recordStudent->entry_pathway) : null,
                'Admitted' => $recordStudent->date_of_admission ? \Illuminate\Support\Carbon::parse($recordStudent->date_of_admission)->format('d M Y') : null,
                'Standing' => $recordStudent->enrollment_status ? ucfirst($recordStudent->enrollment_status) : null,
                'Currently on' => $recordStudent->currentSemester?->semester_label
                    ?: ($recordStudent->current_year ? 'Year '.$recordStudent->current_year : null),
                'Email' => $recordStudent->email ?: $recordApplicant?->email,
                'Phone' => $recordStudent->phone_number ?: $recordApplicant?->phone_number,
                'Date of birth' => $recordApplicant?->date_of_birth ? \Illuminate\Support\Carbon::parse($recordApplicant->date_of_birth)->format('d M Y') : null,
            ], fn ($value) => filled($value));
        @endphp

        <section class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:1.25rem; align-items:flex-start;">
            @if ($photoUrl = $recordStudent->photoUrl())
                <img src="{{ $photoUrl }}" alt="{{ $recordStudent->displayName() }}" style="width:88px; height:88px; object-fit:cover; border-radius:0.5rem; border:1px solid var(--tich-border, #e2e8f0);">
            @endif
            <div style="flex:1 1 20rem;">
                <h3 class="tich-h3" style="margin:0;">{{ $recordStudent->displayName() }}</h3>
                <p class="tich-caption" style="margin:0.25rem 0 0;">
                    {{ $recordStudent->registration_number }}
                    @if ($recordStudent->portal_activated_at || $recordStudent->user_id)
                        · portal active
                    @elseif ($recordStudent->hasActivePortalInvite())
                        · invite pending
                    @else
                        · no portal account
                    @endif
                </p>
                <dl style="display:grid; grid-template-columns:repeat(auto-fit, minmax(11rem, 1fr)); gap:0.4rem 1.25rem; margin:0.9rem 0 0;">
                    @foreach ($profileRows as $label => $value)
                        <div>
                            <dt class="tich-caption">{{ $label }}</dt>
                            <dd style="margin:0;">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    @if ($performance !== [])
        <section class="tich-mt-6">
            <div style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center; justify-content:space-between;">
                <div>
                    <h3 class="tich-h3" style="margin:0;">Semester by semester</h3>
                    <p class="tich-caption">Units taken since enrolment with the marks recorded for each one. Download a term transcript for any semester.</p>
                </div>
                @if ($recordStudent)
                    <div class="tich-flex-wrap" style="gap:0.5rem;">
                        <a class="tich-btn tich-btn-secondary tich-btn-sm" href="{{ route('sis.students.transcript', $recordStudent->id) }}" target="_blank">Open transcript</a>
                        <a class="tich-btn tich-btn-primary tich-btn-sm" href="{{ route('sis.students.transcript.pdf', $recordStudent->id) }}">Download full transcript (PDF)</a>
                    </div>
                @endif
            </div>

            @foreach ($performance as $semesterBlock)
                <article class="tich-inset-panel tich-mt-4">
                    <div style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:baseline; justify-content:space-between;">
                        <h4 class="tich-h4" style="margin:0;">
                            {{ $semesterBlock['label'] }}
                            @if ($semesterBlock['year_label'])
                                <span class="tich-caption">· {{ $semesterBlock['year_label'] }}</span>
                            @endif
                            @if ($semesterBlock['is_current'])
                                <span class="tich-badge tich-badge--pending">Current</span>
                            @endif
                        </h4>
                        <p class="tich-caption" style="margin:0;">
                            {{ $semesterBlock['graded_units'] }} graded
                            @if ($semesterBlock['in_progress_units'] > 0)
                                · {{ $semesterBlock['in_progress_units'] }} in progress
                            @endif
                            · {{ number_format((float) $semesterBlock['credits'], 2) }} credit hours
                            · GPA {{ $semesterBlock['gpa'] === null ? 'not yet computed' : number_format($semesterBlock['gpa'], 2) }}
                            @if ($recordStudent)
                                · <a class="tich-link" href="{{ route('sis.students.transcript.term.pdf', ['student' => $recordStudent->id, 'semester' => $semesterBlock['semester_id']]) }}">Term transcript</a>
                            @endif
                        </p>
                    </div>

                    <div class="tich-table-wrap tich-mt-3" style="max-height:24rem; overflow:auto;">
                        <table class="tich-admin-table">
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th>Lecturer</th>
                                    <th>Credits</th>
                                    <th>CAT</th>
                                    <th>Exam</th>
                                    <th>Total</th>
                                    <th>Grade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($semesterBlock['units'] as $row)
                                    <tr>
                                        <td>{{ $row['unit_code'] }} - {{ $row['unit_name'] }}</td>
                                        <td>{{ $row['lecturer'] ?: 'Not yet allocated' }}</td>
                                        <td>{{ number_format((float) $row['credit_hours'], 2) }}</td>
                                        <td>{{ $row['cat'] === null ? '-' : number_format((float) $row['cat'], 1) }}</td>
                                        <td>{{ $row['exam'] === null ? '-' : number_format((float) $row['exam'], 1) }}</td>
                                        <td>{{ $row['total'] === null ? '-' : number_format((float) $row['total'], 1) }}</td>
                                        <td>{{ $row['grade'] ?: '-' }}</td>
                                        <td>{{ $row['status'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @endforeach
        </section>
    @endif

    @if (! empty($academics['current_period']))
        <section class="tich-mt-4">
            <h3 class="tich-h3">Current semester</h3>
            <p class="tich-text">
                Semester {{ $academics['current_period']->semester }}
                @if ($academics['current_period']->scheduleLabel())
                    · {{ $academics['current_period']->scheduleLabel() }}
                @endif
            </p>
            @if ($academics['current_period_units']->isNotEmpty())
                <div class="tich-card tich-table-panel tich-mt-2">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Contact hrs</th>
                                <th>Core</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($academics['current_period_units'] as $mapping)
                                <tr>
                                    <td>{{ $mapping->unit?->unit_code }} - {{ $mapping->unit?->unit_name }}</td>
                                    <td>{{ $mapping->contact_hours ?? 0 }}</td>
                                    <td>{{ $mapping->is_compulsory ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    @if ($academics['registered_units']->isNotEmpty())
        <section class="tich-mt-6">
            <h3 class="tich-h3">Registered units</h3>
            <div class="tich-card tich-table-panel tich-mt-2">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>Registered</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($academics['registered_units'] as $row)
                            <tr>
                                <td>{{ $row->unit_code }} - {{ $row->unit_name }}</td>
                                <td>{{ $row->semester_label ?? ('Semester '.$row->semester_number) }}</td>
                                <td>{{ $row->registration_date ? \Illuminate\Support\Carbon::parse($row->registration_date)->format('d M Y') : '-' }}</td>
                                <td>{{ ucfirst($row->registration_status ?? 'registered') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($academics['cat_scores']->isNotEmpty())
        <section class="tich-mt-6">
            <h3 class="tich-h3">CAT and continuous assessment</h3>
            <div class="tich-card tich-table-panel tich-mt-2">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>Assessment</th>
                            <th>Type</th>
                            <th>Score</th>
                            <th>Weight</th>
                            <th>Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($academics['cat_scores'] as $row)
                            <tr>
                                <td>{{ $row->unit_code }} - {{ $row->unit_name }}</td>
                                <td>{{ $row->semester_label }}</td>
                                <td>{{ $row->assessment_name }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $row->assessment_type)) }}</td>
                                <td>{{ number_format((float) $row->score_obtained, 1) }}/{{ number_format((float) $row->max_score, 1) }}</td>
                                <td>{{ number_format((float) $row->weight_in_final, 1) }}%</td>
                                <td>{{ $row->recorded_at ? \Illuminate\Support\Carbon::parse($row->recorded_at)->format('d M Y') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($academics['exam_results']->isNotEmpty())
        <section class="tich-mt-6">
            <h3 class="tich-h3">Exam results</h3>
            <div class="tich-card tich-table-panel tich-mt-2">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>CAT</th>
                            <th>Practical</th>
                            <th>Exam</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Published</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($academics['exam_results'] as $row)
                            <tr>
                                <td>{{ $row->unit_code }} - {{ $row->unit_name }}</td>
                                <td>{{ $row->semester_label }}</td>
                                <td>{{ number_format((float) $row->cat_total, 1) }}</td>
                                <td>{{ number_format((float) $row->practical_total, 1) }}</td>
                                <td>{{ number_format((float) $row->final_exam_score, 1) }}@if ($row->is_supplementary)<sup>Supp</sup>@endif</td>
                                <td>{{ number_format((float) $row->final_total_score, 1) }}</td>
                                <td>{{ $row->grade_letter ?? '-' }}</td>
                                <td>
                                    @if ($row->is_published)
                                        Yes
                                    @else
                                        Draft
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($academics['grades']->isNotEmpty())
        <section class="tich-mt-6">
            <h3 class="tich-h3">Final grades</h3>
            <div class="tich-card tich-table-panel tich-mt-2">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>Score</th>
                            <th>Grade</th>
                            <th>Points</th>
                            <th>Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($academics['grades'] as $grade)
                            <tr>
                                <td>{{ $grade->unit_code }} - {{ $grade->unit_name }}</td>
                                <td>{{ $grade->semester_label }}</td>
                                <td>{{ number_format((float) $grade->final_score, 1) }}</td>
                                <td>{{ $grade->grade_letter ?? '-' }}</td>
                                <td>{{ $grade->grade_points ?? '-' }}</td>
                                <td>{{ $grade->recorded_at ? \Illuminate\Support\Carbon::parse($grade->recorded_at)->format('d M Y') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($academics['attendance']->isNotEmpty())
        <section class="tich-mt-6">
            <h3 class="tich-h3">Attendance</h3>
            <div class="tich-card tich-table-panel tich-mt-2">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Semester</th>
                            <th>Present</th>
                            <th>Percentage</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($academics['attendance'] as $row)
                            <tr>
                                <td>{{ $row->unit_code }} - {{ $row->unit_name }}</td>
                                <td>{{ $row->semester_label }}</td>
                                <td>{{ $row->total_present }}/{{ $row->total_sessions }}</td>
                                <td>{{ number_format((float) $row->attendance_percentage, 1) }}%</td>
                                <td>{{ ucfirst($row->status_flag ?? '-') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if (
        $academics['registered_units']->isEmpty()
        && $academics['cat_scores']->isEmpty()
        && $academics['exam_results']->isEmpty()
        && $academics['grades']->isEmpty()
        && $academics['attendance']->isEmpty()
    )
        @include('partials.states.empty', [
            'title' => 'No academic records yet',
            'description' => 'Units, marks, and results will appear once semester registration and assessment data is captured.',
            'icon' => 'inbox',
            'inline' => true,
        ])
    @endif
</div>
