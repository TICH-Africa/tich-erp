@php
    $academics = $academics ?? [];
    $compact = $compact ?? false;
@endphp

<div @class(['tich-student-academic-record', 'tich-student-academic-record--compact' => $compact])>
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
                                <td>{{ number_format((float) $row->final_exam_score, 1) }}</td>
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
        <p class="tich-text tich-mt-4">No academic records yet for this student. Units, marks, and results will appear once semester registration and assessment data is captured.</p>
    @endif
</div>
