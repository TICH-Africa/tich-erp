<header class="tich-dept-header">
    <p class="tich-caption">Staff portal</p>
    <h1 class="tich-h1 tich-dept-header__title">Overview</h1>
    <p class="tich-text tich-dept-header__meta">{{ $staff->department?->dept_name }}</p>
</header>

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
        <p class="tich-caption">At-risk attendance</p>
        <p class="tich-stat__value">{{ $portalData['attendance_alerts']->count() }}</p>
    </article>
</div>

@if ($portalData['attendance_alerts']->isNotEmpty())
    <article class="tich-card tich-mt-6" style="border-left:3px solid #dc2626; padding:1rem 1.25rem;">
        <h2 class="tich-h3">Attendance alerts (&lt; 90%)</h2>
        <p class="tich-text">Students at risk of losing exam eligibility.</p>
        <table class="tich-admin-table tich-mt-4">
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
    </article>
@endif
