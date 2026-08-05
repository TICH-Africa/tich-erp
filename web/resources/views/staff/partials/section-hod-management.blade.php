<x-page-toolbar title="HOD management" :meta="$staff->department?->dept_name" />

<div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap:1.5rem;">
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
            <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.lesson-plans.index', ['department' => $staff->department_id]) }}" class="tich-link">Lesson plan approval</a> in Academics to review and approve/reject.</p>
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
            <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.programs.index', ['department' => $staff->department_id]) }}" class="tich-link">Programme curriculum</a> in Academics to manage allocations.</p>
        @endif
    </article>
</div>

<div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap:1.5rem;">
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
            <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.attendance-ledger.index', ['department' => $staff->department_id]) }}" class="tich-link">Attendance ledger</a> in Academics to verify.</p>
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
            <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.performance.index', ['department' => $staff->department_id]) }}" class="tich-link">Performance terminal</a> in Academics for full analytics.</p>
        @endif
    </article>
</div>
