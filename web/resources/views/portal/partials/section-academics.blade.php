@php($academics = $portalData['academics'])

<header class="tich-dept-header">
    <p class="tich-caption">Learning</p>
    <h1 class="tich-h1 tich-dept-header__title">Academics</h1>
    <p class="tich-text tich-dept-header__meta">
        {{ $biodata['academic']['program'] }}
        @if ($academics['current_semester'])
            · Current: {{ $academics['current_semester']->semester_label }}
        @endif
        @if ($academics['curriculum'])
            · Curriculum: {{ $academics['curriculum']->intakeLabel() }}
        @endif
    </p>
</header>

@if ($academics['registered_units']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">My registered units</h2>
        </div>
        <div class="tich-card tich-mt-4" style="overflow-x:auto;">
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
                            <td>{{ $row->unit_code }} — {{ $row->unit_name }}</td>
                            <td>{{ $row->semester_label ?? ('Semester '.$row->semester_number) }}</td>
                            <td>{{ $row->registration_date ? \Illuminate\Support\Carbon::parse($row->registration_date)->format('d M Y') : '—' }}</td>
                            <td>{{ ucfirst($row->registration_status ?? 'registered') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($academics['grades']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Grades</h2>
        </div>
        <div class="tich-card tich-mt-4" style="overflow-x:auto;">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Semester</th>
                        <th>Score</th>
                        <th>Grade</th>
                        <th>Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($academics['grades'] as $grade)
                        <tr>
                            <td>{{ $grade->unit_code }} — {{ $grade->unit_name }}</td>
                            <td>{{ $grade->semester_label }}</td>
                            <td>{{ number_format((float) $grade->final_score, 1) }}</td>
                            <td>{{ $grade->grade_letter ?? '—' }}</td>
                            <td>{{ $grade->recorded_at ? \Illuminate\Support\Carbon::parse($grade->recorded_at)->format('d M Y') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($academics['attendance']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Attendance</h2>
        </div>
        <div class="tich-card tich-mt-4" style="overflow-x:auto;">
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
                            <td>{{ $row->unit_code }} — {{ $row->unit_name }}</td>
                            <td>{{ $row->semester_label }}</td>
                            <td>{{ $row->total_present }}/{{ $row->total_sessions }}</td>
                            <td>{{ number_format((float) $row->attendance_percentage, 1) }}%</td>
                            <td>{{ ucfirst($row->status_flag ?? '—') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($academics['curriculum_by_semester']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Programme curriculum</h2>
            <p class="tich-text">
                @if ($academics['curriculum'])
                    Published plan for {{ $academics['curriculum']->intakeLabel() }}.
                @else
                    Units mapped to each semester of your programme.
                @endif
            </p>
        </div>

        @foreach ($academics['curriculum_by_semester'] as $semesterNumber => $units)
            <fieldset class="tich-mt-6" style="border:1px solid var(--tich-border); border-radius:0.5rem; padding:1rem;">
                <legend class="tich-h3" style="padding:0 0.5rem;">Semester {{ $semesterNumber }}</legend>
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Contact hrs</th>
                            <th>Learning hrs</th>
                            <th>Core</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($units as $mapping)
                            <tr>
                                <td>{{ $mapping->unit?->unit_code }} — {{ $mapping->unit?->unit_name }}</td>
                                <td>{{ $mapping->contact_hours ?? 0 }}</td>
                                <td>{{ $mapping->total_learning_hours ?? 0 }}</td>
                                <td>{{ $mapping->is_compulsory ? 'Yes' : 'No' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </fieldset>
        @endforeach
    </section>
@elseif ($academics['registered_units']->isEmpty() && $academics['grades']->isEmpty())
    <article class="tich-card tich-dept-empty tich-mt-8">
        <h2 class="tich-h3">No academic records yet</h2>
        <p class="tich-text tich-mt-2">Unit registration, grades, and attendance will appear here once the academic office publishes your semester registration.</p>
    </article>
@endif
