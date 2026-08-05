<x-page-toolbar title="Overview" :meta="$staff->department?->dept_name" />

<div class="tich-grid tich-grid--4 tich-dept-stats tich-mt-6">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Assigned units</p>
        <p class="tich-stat__value">{{ $portalData['allocation_count'] }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Timetable slots</p>
        <p class="tich-stat__value">{{ $portalData['timetable_sessions']->count() }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Lesson plans</p>
        <p class="tich-stat__value">{{ $portalData['lesson_plans']->count() }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Marks recorded</p>
        <p class="tich-stat__value">{{ $portalData['cat_scores']->count() }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">At-risk attendance</p>
        <p class="tich-stat__value">{{ $portalData['attendance_alerts']->count() }}</p>
    </article>
</div>

@if ($portalData['allocations']->isNotEmpty())
    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">Quick actions</h2>
        <p class="tich-text tich-mt-2">
            <a href="{{ route('staff.dashboard', ['section' => 'grading', 'allocation' => $portalData['allocations']->first()->id]) }}" class="tich-link">Enter CAT &amp; exam marks</a>
            for {{ $portalData['allocations']->first()->unit?->unit_code }}.
        </p>
    </article>
@endif

@if ($portalData['attendance_alerts']->isNotEmpty())
    <article class="tich-card tich-mt-6" style="border-left:3px solid #dc2626; padding:1rem 1.25rem;">
        <h2 class="tich-h3">Attendance alerts (&lt; 90%)</h2>
        <p class="tich-text">Students at risk of losing exam eligibility.</p>
        <div class="tich-table-wrap tich-mt-4">
        <table class="tich-admin-table">
            <thead><tr><th>Student</th><th>Unit</th><th>Percentage</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($portalData['attendance_alerts']->take(10) as $alert)
                    <tr>
                        <td>{{ trim($alert->student_name) ?: $alert->registration_number }}</td>
                        <td>{{ $alert->unit_code }}</td>
                        <td>{{ number_format((float) $alert->attendance_percentage, 1) }}%</td>
                        <td>@include('partials.attendance-flag', ['flag' => $alert->status_flag])</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </article>
@endif

@if (! empty($hodManagement))
    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">HOD management</h2>
        <p class="tich-text tich-mt-2">Quick links to lesson plan approvals, unit allocations, attendance review, and performance.</p>
        <div class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:1rem;">
            <a href="{{ route('staff.dashboard', ['section' => 'hod-management']) }}" class="tich-btn tich-btn-primary">Open HOD management</a>
            <a href="{{ route('departments.academics.lesson-plans.index', ['department' => $staff->department_id]) }}" class="tich-btn tich-btn-secondary">Lesson plan approval</a>
            <a href="{{ route('departments.academics.attendance-ledger.index', ['department' => $staff->department_id]) }}" class="tich-btn tich-btn-secondary">Attendance ledger</a>
            <a href="{{ route('departments.academics.performance.index', ['department' => $staff->department_id]) }}" class="tich-btn tich-btn-secondary">Performance terminal</a>
        </div>
    </article>
@endif
