<x-page-toolbar title="Department leave" :meta="$staff->department?->dept_name . ' · Management'" />

<div class="tich-mt-6">
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
</div>
