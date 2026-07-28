@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
        $summary = $performance['summary'];
    @endphp

    @include('academics.partials.learning-department-context')

    <header class="tich-dept-header">
        <h1 class="tich-h1 tich-dept-header__title">Performance mapping terminal</h1>
        <p class="tich-text">Departmental analytics for HODs and academic leadership - class averages, failing trends, and practical completion across campuses and sub-county hubs.</p>
    </header>

    <form method="GET" class="tich-card tich-mt-6" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end;">
        @if (! empty($learningDepartments) && empty($learningDepartment))
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Learning department</label>
                <select name="learning_department" class="tich-input">
                    @foreach ($learningDepartments as $dept)
                        <option value="{{ $dept->id }}" @selected(request('learning_department') == $dept->id)>{{ $dept->dept_name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label">Semester</label>
            <select name="semester" class="tich-input">
                @foreach ($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected($selectedSemesterId == $semester->id || ($performance['semester_id'] ?? null) == $semester->id)>
                        {{ $semester->semester_label }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="tich-btn tich-btn-secondary">Apply</button>
        <a href="{{ route('sis.students.index') }}" class="tich-link" style="margin-left:auto;">Student records →</a>
    </form>

    <div class="tich-grid tich-grid--4 tich-dept-stats tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Class average</p>
            <p class="tich-stat__value">{{ $summary['avg_score'] }}%</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Registered students</p>
            <p class="tich-stat__value">{{ $summary['registered_students'] }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Practical completion</p>
            <p class="tich-stat__value">{{ $summary['practical_completion_rate'] }}%</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Failing trend</p>
            <p class="tich-stat__value">{{ $summary['failing_rate'] }}%</p>
        </article>
    </div>

    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2">Campus &amp; sub-county hub breakdown</h2>
        </div>
        <div class="tich-card tich-mt-4" style="overflow-x:auto; padding:0;">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Campus / hub</th>
                        <th>Type</th>
                        <th>Sub-county</th>
                        <th>Students</th>
                        <th>Avg score</th>
                        <th>Failing assessments</th>
                        <th>Practical entries</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($performance['campus_breakdown'] as $row)
                        <tr>
                            <td>{{ $row->campus_name }}</td>
                            <td>{{ str_replace('_', ' ', $row->campus_type) }}</td>
                            <td>{{ $row->sub_county ?? $row->county ?? '-' }}</td>
                            <td>{{ $row->student_count }}</td>
                            <td>{{ $row->avg_score }}%</td>
                            <td>{{ $row->failing_assessments }}</td>
                            <td>{{ $row->practical_entries }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No assessment data for this semester yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2">Unit class averages</h2>
        </div>
        <div class="tich-card tich-mt-4" style="overflow-x:auto; padding:0;">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Students</th>
                        <th>Class average</th>
                        <th>Failing</th>
                        <th>High performers (A/B)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($performance['unit_breakdown'] as $row)
                        <tr @class(['tich-competency-grid__fail' => $row->class_average < 40])>
                            <td>{{ $row->unit_code }} - {{ $row->unit_name }}</td>
                            <td>{{ $row->student_count }}</td>
                            <td>{{ $row->class_average }}%</td>
                            <td>{{ $row->failing_count }}</td>
                            <td>{{ $row->high_performers }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No cumulative grades compiled yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($performance['failing_students']->isNotEmpty())
        <section class="tich-dept-panel tich-mt-8">
            <div class="tich-dept-panel__head">
                <h2 class="tich-h2">Early intervention - at-risk students</h2>
                <p class="tich-text">Students below pass mark who may need support before final exams.</p>
            </div>
            <div class="tich-card tich-mt-4" style="overflow-x:auto; padding:0;">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Unit</th>
                            <th>Campus</th>
                            <th>Sub-county</th>
                            <th>Cumulative</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($performance['failing_students'] as $row)
                            <tr>
                                <td>{{ $row->registration_number }} · {{ trim($row->student_name) }}</td>
                                <td>{{ $row->unit_code }}</td>
                                <td>{{ $row->campus_name }}</td>
                                <td>{{ $row->sub_county ?? '-' }}</td>
                                <td>{{ number_format((float) $row->final_score, 1) }}%</td>
                                <td>{{ $row->grade_letter }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
