@php
    $academics = $portalData['academics'];
    $currentSemesterNumber = $academics['current_period']?->semester;
    $allSemesters = collect($academics['curriculum_by_semester'])->sortKeys();
    $otherSemesters = $currentSemesterNumber
        ? $allSemesters->except($currentSemesterNumber)
        : $allSemesters;

    $portalPeriodStatus = function ($period) {
        if (! $period?->start_date || ! $period?->end_date) {
            return null;
        }

        $today = now()->startOfDay();

        if ($today->lt($period->start_date)) {
            return 'Upcoming';
        }

        if ($today->gt($period->end_date)) {
            return 'Completed';
        }

        return 'In progress';
    };
@endphp

<header class="tich-dept-header">
    <p class="tich-caption">Learning</p>
    <h1 class="tich-h1 tich-dept-header__title">Academics</h1>
    <p class="tich-text tich-dept-header__meta">
        {{ $biodata['academic']['program'] }}
        @if ($academics['current_period'])
            · Semester {{ $academics['current_period']->semester }}
        @elseif ($academics['current_semester'])
            · Current: {{ $academics['current_semester']->semester_label }}
        @endif
        @if ($academics['curriculum'])
            · Curriculum: {{ $academics['curriculum']->intakeLabel() }}
        @endif
    </p>
</header>

@if ($academics['curriculum'] && ! $academics['curriculum_is_published'])
    <div class="tich-notice tich-notice--info tich-mt-4">
        <p class="tich-text" style="margin:0;">
            Your programme curriculum for {{ $academics['curriculum']->intakeLabel() }} is still being finalised by the academic office.
            The units and dates shown below are provisional until the intake is published.
        </p>
    </div>
@endif

@if ($academics['current_period'])
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">
                Semester {{ $academics['current_period']->semester }}
                @if ($academics['current_period_status'] === 'in_progress')
                    <span class="tich-caption">· In progress</span>
                @elseif ($academics['current_period_status'] === 'upcoming')
                    <span class="tich-caption">· Upcoming</span>
                @elseif ($academics['current_period_status'] === 'completed')
                    <span class="tich-caption">· Completed</span>
                @endif
            </h2>
            <p class="tich-text">
                @if ($academics['current_period']->scheduleLabel())
                    {{ $academics['current_period']->scheduleLabel() }}
                @else
                    Semester dates have not been published yet.
                @endif
            </p>
        </div>

        @if ($academics['current_period_units']->isNotEmpty())
            <div class="tich-card tich-mt-4" style="overflow-x:auto;">
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
                        @foreach ($academics['current_period_units'] as $mapping)
                            <tr>
                                <td>{{ $mapping->unit?->unit_code }} — {{ $mapping->unit?->unit_name }}</td>
                                <td>{{ $mapping->contact_hours ?? 0 }}</td>
                                <td>{{ $mapping->total_learning_hours ?? 0 }}</td>
                                <td>{{ $mapping->is_compulsory ? 'Yes' : 'No' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <article class="tich-card tich-mt-4">
                <p class="tich-text">No units are mapped to this semester yet.</p>
            </article>
        @endif
    </section>
@endif

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

@if ($otherSemesters->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Other semesters</h2>
            <p class="tich-text">
                @if ($academics['curriculum'])
                    Full programme plan for {{ $academics['curriculum']->intakeLabel() }}.
                @else
                    All semesters in your programme.
                @endif
            </p>
        </div>

        <ul class="tich-semester-list tich-mt-4">
            @foreach ($otherSemesters as $semesterNumber => $units)
                @php($period = $academics['periods_by_semester']->get($semesterNumber))
                @php($status = $portalPeriodStatus($period))
                <li class="tich-semester-list__item">
                    <span class="tich-semester-list__label">Semester {{ $semesterNumber }}</span>
                    <span class="tich-semester-list__meta">
                        @if ($period?->scheduleLabel())
                            {{ $period->scheduleLabel() }}
                        @else
                            Dates not published
                        @endif
                        · {{ $units->count() }} {{ str('unit')->plural($units->count()) }}
                        @if ($status)
                            · {{ $status }}
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>

        <div class="tich-form-group tich-semester-picker tich-mt-6">
            <label for="semester-units-select" class="tich-label">View units for semester</label>
            <select id="semester-units-select" class="tich-input">
                <option value="">Choose a semester…</option>
                @foreach ($otherSemesters as $semesterNumber => $units)
                    @php($period = $academics['periods_by_semester']->get($semesterNumber))
                    <option value="semester-{{ $semesterNumber }}">
                        Semester {{ $semesterNumber }}
                        @if ($period?->scheduleLabel())
                            ({{ $period->scheduleLabel() }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        @foreach ($otherSemesters as $semesterNumber => $units)
            <div id="semester-{{ $semesterNumber }}" class="tich-semester-units-panel" hidden>
                <div class="tich-card" style="overflow-x:auto;">
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
                </div>
            </div>
        @endforeach
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var select = document.getElementById('semester-units-select');
        if (!select) {
            return;
        }

        var panels = document.querySelectorAll('.tich-semester-units-panel');

        select.addEventListener('change', function () {
            panels.forEach(function (panel) {
                panel.hidden = panel.id !== select.value;
            });
        });
    });
    </script>
@elseif ($academics['registered_units']->isEmpty() && $academics['grades']->isEmpty() && ! $academics['curriculum'])
    <article class="tich-card tich-dept-empty tich-mt-8">
        <h2 class="tich-h3">No academic records yet</h2>
        <p class="tich-text tich-mt-2">
            @if ($student->program_id)
                Unit registration, grades, and attendance will appear here once your programme curriculum is published and semester registration is open.
            @else
                Your programme has not been assigned yet. Contact the admissions office if this persists.
            @endif
        </p>
    </article>
@endif
