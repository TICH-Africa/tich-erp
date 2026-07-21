@extends('layouts.approval')

@section('approval-content')
    <h1 class="tich-h1" style="font-size: 2rem;">Approval dashboard</h1>
    <p class="tich-text tich-mb-8">
        Verify and approve student onboarding applications.
        @if ($canAccessAll)
            You have institution-wide access across all departments.
        @else
            Showing applications for your assigned department(s) only.
        @endif
    </p>

    <div class="tich-grid tich-grid--4 tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Pending review</p>
            <p class="tich-stat__value">{{ $stats['pending'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Shortlisted</p>
            <p class="tich-stat__value">{{ $stats['shortlisted'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Admitted</p>
            <p class="tich-stat__value">{{ $stats['admitted'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Rejected</p>
            <p class="tich-stat__value">{{ $stats['rejected'] }}</p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2" style="align-items: start; gap: 2rem;">
        <article class="tich-card">
            <h2 class="tich-h3">By handling department</h2>
            <p class="tich-text tich-mb-4">Each application is routed to the academic department offering the selected programme.</p>

            @if ($departmentBreakdown->isEmpty())
                <p class="tich-caption">No applications in your scope yet.</p>
            @else
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Pending</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($departmentBreakdown as $row)
                            <tr>
                                <td>{{ $row->dept_name }}</td>
                                <td>{{ $row->pending_count }}</td>
                                <td>{{ $row->total }}</td>
                                <td>
                                    <a href="{{ route('admissions.applications.index', ['department' => $row->id, 'status' => 'pending']) }}" class="tich-link">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </article>

        <article class="tich-card">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                <h2 class="tich-h3">Recent applications</h2>
                <a href="{{ route('admissions.applications.index') }}" class="tich-link">View all</a>
            </div>

            @if ($recentApplications->isEmpty())
                <p class="tich-caption tich-mt-4">No applications submitted yet.</p>
            @else
                <table class="tich-admin-table tich-mt-4">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Programme</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentApplications as $application)
                            <tr>
                                <td>
                                    <a href="{{ route('admissions.applications.show', $application->id) }}" class="tich-link">
                                        {{ $application->fullName() }}
                                    </a>
                                    <br>
                                    <span class="tich-caption">{{ $application->application_number }}</span>
                                </td>
                                <td>{{ $application->program?->program_name ?? '—' }}</td>
                                <td>{{ $application->program?->department?->dept_name ?? $application->handlingDepartment?->dept_name ?? '—' }}</td>
                                <td>@include('admissions.partials.status-badge', ['applicant' => $application])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </article>
    </div>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Quick actions</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;" class="tich-mt-4">
            <a href="{{ route('admissions.applications.index', ['status' => 'pending']) }}" class="tich-btn tich-btn-primary">Review pending</a>
            <a href="{{ route('admissions.applications.index', ['status' => 'admitted']) }}" class="tich-btn tich-btn-secondary">View admitted</a>
            <a href="{{ route('admissions.applications.index', ['status' => 'rejected']) }}" class="tich-btn tich-btn-secondary">View rejected</a>
        </div>
    </div>
@endsection
