@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
    @endphp

    @include('academics.partials.learning-department-context')

    <header class="tich-dept-header">
        <h1 class="tich-h1 tich-dept-header__title">Lesson plan audit repository</h1>
        <p class="tich-text">Read-only institutional record of approved, modified, and rejected lesson plans for curriculum coverage tracking.</p>
    </header>

    <form method="GET" class="tich-card tich-mt-6" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end;">
        @if (! empty($learningDepartments) && empty($learningDepartment))
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Learning department</label>
                <select name="learning_department" class="tich-input">
                    <option value="">All in scope</option>
                    @foreach ($learningDepartments as $dept)
                        <option value="{{ $dept->id }}" @selected(request('learning_department') == $dept->id)>{{ $dept->dept_name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label">Semester</label>
            <select name="semester" class="tich-input">
                <option value="">All semesters</option>
                @foreach ($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected($selectedSemesterId == $semester->id)>{{ $semester->semester_label }} ({{ $semester->academicYear?->year_label }})</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label">Status</label>
            <select name="status" class="tich-input">
                <option value="">All recorded</option>
                <option value="submitted" @selected($selectedStatus === 'submitted')>Submitted</option>
                <option value="approved" @selected($selectedStatus === 'approved')>Approved</option>
                <option value="modified" @selected($selectedStatus === 'modified')>Modified</option>
                <option value="rejected" @selected($selectedStatus === 'rejected')>Rejected</option>
            </select>
        </div>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
        <a href="{{ route('departments.academics.lesson-plans.index', $hub) }}" class="tich-link" style="margin-left:auto;">HOD approval inbox →</a>
    </form>

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Plan ID</th>
                    <th>Unit</th>
                    <th>Tutor</th>
                    <th>Semester</th>
                    <th>Planned date</th>
                    <th>Competencies</th>
                    <th>Contact hrs</th>
                    <th>Status</th>
                    <th>HOD action</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td>{{ $plan->plan_number }}</td>
                        <td>{{ $plan->unit_code }}</td>
                        <td>{{ trim($plan->tutor_first_name.' '.$plan->tutor_surname) }}</td>
                        <td>{{ $plan->semester_label ?? '-' }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($plan->planned_date)->format('d M Y') }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($plan->competencies_targeted ?? '-', 40) }}</td>
                        <td>{{ $plan->contact_hours }}</td>
                        <td>{{ ucfirst($plan->status) }}</td>
                        <td>{{ $plan->hod_action_at ? \Illuminate\Support\Carbon::parse($plan->hod_action_at)->format('d M Y') : '-' }}</td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('departments.academics.lesson-plans.show', array_merge($hub, ['plan' => $plan->id])) }}" class="tich-link">View</a>
                            @if ($canReview && $plan->status === 'submitted')
                                <form method="POST" action="{{ route('departments.academics.lesson-plans.approve', array_merge($hub, ['plan' => $plan->id])) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-link">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('departments.academics.lesson-plans.reject', array_merge($hub, ['plan' => $plan->id])) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-link" style="color:var(--tich-danger, #b91c1c);">Reject</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="tich-text">No lesson plans in the audit repository yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
