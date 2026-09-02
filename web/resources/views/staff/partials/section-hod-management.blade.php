<x-page-toolbar title="HOD management" :meta="$staff->department?->dept_name" />

<div class="tich-hod-overview tich-mt-6">
    <p class="tich-text tich-mt-2">Department management overview for <strong>{{ $staff->fullName() }}</strong>.</p>
    <p class="tich-text tich-mt-4">Use the sidebar to navigate to individual management modules:</p>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap: 1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Lesson plans</h2>
            <p class="tich-text tich-mt-2">Submitted and modified lesson plans from tutors in your department.</p>
            @if ($hodManagement['lesson_plans']->isEmpty())
                <p class="tich-text tich-mt-4">No pending lesson plans.</p>
            @else
                <div class="tich-table-wrap tich-mt-4">
                    <table class="tich-admin-table">
                        <thead><tr><th>Tutor</th><th>Unit</th><th>Date</th><th>Hrs</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($hodManagement['lesson_plans'] as $plan)
                                <tr>
                                    <td>{{ $plan->tutor_name }}</td>
                                    <td>{{ $plan->unit_code }} - {{ $plan->unit_name }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($plan->planned_date)->format('d M Y') }}</td>
                                    <td>{{ $plan->contact_hours }}</td>
                                    <td>{{ ucfirst($plan->status) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.lesson-plans.index', ['learning_department' => $staff->department_id]) }}" class="tich-link">Lesson plan approval</a> in Academics to review and approve/reject.</p>
            @endif
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Unit allocations</h2>
            <p class="tich-text tich-mt-2">Lecturers assigned to units in your department.</p>
            @if ($hodManagement['allocations']->isEmpty())
                <p class="tich-text tich-mt-4">No unit allocations found.</p>
            @else
                <div class="tich-table-wrap tich-mt-4">
                    <table class="tich-admin-table">
                        <thead><tr><th>Unit</th><th>Lecturer</th><th>Semester</th><th>Campus</th></tr></thead>
                        <tbody>
                            @foreach ($hodManagement['allocations'] as $allocation)
                                <tr>
                                    <td>{{ $allocation->unit?->unit_code }} - {{ $allocation->unit?->unit_name }}</td>
                                    <td>{{ $allocation->staff?->fullName() }}</td>
                                    <td>{{ $allocation->semester?->semester_label ?? '-' }}</td>
                                    <td>{{ $allocation->campus?->campus_name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.programs.index', ['learning_department' => $staff->department_id]) }}" class="tich-link">Programme curriculum</a> in Academics to manage allocations.</p>
            @endif
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap: 1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Attendance review</h2>
            <p class="tich-text tich-mt-2">Lecturer-submitted attendance sheets pending or completed verification.</p>
            @if ($hodManagement['attendance']->isEmpty())
                <p class="tich-text tich-mt-4">No submitted attendance sessions.</p>
            @else
                <div class="tich-table-wrap tich-mt-4">
                    <table class="tich-admin-table">
                        <thead><tr><th>Tutor</th><th>Unit</th><th>Date</th><th>Status</th><th>Signed sheet</th><th>HOD</th><th>Registrar</th></tr></thead>
                        <tbody>
                            @foreach ($hodManagement['attendance'] as $session)
                                <tr>
                                    <td>{{ $session->tutor_name }}</td>
                                    <td>{{ $session->unit_code }} - {{ $session->unit_name }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($session->session_date)->format('d M Y') }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $session->verification_status)) }}</td>
                                    <td>
                                        @if ($session->signed_sheet_image_path)
                                            <a href="{{ asset('storage/'.$session->signed_sheet_image_path) }}" target="_blank" class="tich-link">View photo</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $session->hod_verified_at ? \Illuminate\Support\Carbon::parse($session->hod_verified_at)->format('d M Y') : '-' }}</td>
                                    <td>{{ $session->registrar_verified_at ? \Illuminate\Support\Carbon::parse($session->registrar_verified_at)->format('d M Y') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.attendance-ledger.index', ['learning_department' => $staff->department_id]) }}" class="tich-link">Attendance ledger</a> in Academics to verify.</p>
            @endif
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Performance</h2>
            <p class="tich-text tich-mt-2">Department performance snapshot from existing academics reports.</p>
            @if (empty($hodManagement['performance']))
                <p class="tich-text tich-mt-4">No performance data available.</p>
            @else
                <div class="tich-mt-4">
                    <p class="tich-text">Class average: <strong>{{ $hodManagement['performance']['summary']['avg_score'] ?? 0 }}%</strong></p>
                    <p class="tich-text">Registered students: <strong>{{ $hodManagement['performance']['summary']['registered_students'] ?? 0 }}</strong></p>
                    <p class="tich-text">Failing rate: <strong>{{ $hodManagement['performance']['summary']['failing_rate'] ?? 0 }}%</strong></p>
                </div>
                <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.performance.index', ['learning_department' => $staff->department_id]) }}" class="tich-link">Performance terminal</a> in Academics for full analytics.</p>
            @endif
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap: 1.5rem;">
        <article class="tich-card" style="grid-column: 1 / -1;">
            <h2 class="tich-h3">Department leave</h2>
            <p class="tich-text tich-mt-2">Lecturers and tutors in your department who have requested leave, with HR approval status and expected return dates.</p>
            @if ($hodManagement['leave']->isEmpty())
                <p class="tich-text tich-mt-4">No active leave requests in your department.</p>
            @else
                <div class="tich-table-wrap tich-mt-4">
                    <table class="tich-admin-table">
                        <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Job title</th>
                            <th>Leave type</th>
                            <th>Start date</th>
                            <th>End date</th>
                            <th>Return date</th>
                            <th>HOD status</th>
                            <th>HR status</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach ($hodManagement['leave'] as $leave)
                                @php
                                    $overallStatus = $leave->overall_status;
                                    $statusClass = match ($overallStatus) {
                                        'approved' => 'success',
                                        'rejected', 'cancelled' => 'danger',
                                        'pending_hr' => 'warning',
                                        'pending_hod' => 'info',
                                        default => 'muted',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $leave->tutor_name }}</strong>
                                        <p class="tich-caption">{{ $leave->employee_number }}</p>
                                    </td>
                                    <td>{{ $leave->job_title ?? '—' }}</td>
                                    <td>{{ $leave->leave_type_name ?? '—' }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($leave->start_date)->format('d M Y') }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($leave->end_date)->format('d M Y') }}</td>
                                    <td>
                                        @if ($leave->return_date)
                                            {{ \Illuminate\Support\Carbon::parse($leave->return_date)->format('d M Y') }}
                                            @if (\Illuminate\Support\Carbon::parse($leave->return_date)->isPast() && !$leave->is_completed)
                                                <span class="tich-badge tich-badge--danger tich-ml-2">Overdue</span>
                                            @elseif (\Illuminate\Support\Carbon::parse($leave->return_date)->isToday())
                                                <span class="tich-badge tich-badge--info tich-ml-2">Today</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <span class="tich-badge tich-badge--{{ $statusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $leave->overall_status)) }}
                                        </span>
                                    </td>
                                    <td class="tich-caption">
                                        @if ($leave->is_completed)
                                            <span class="tich-badge tich-badge--success">Completed</span>
                                        @else
                                            Active
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </div>
</div>
