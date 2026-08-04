@extends('layouts.hr')

@section('title', 'HR Dashboard')

@section('hr-content')
    <div class="tich-mb-8">
        <h1 class="tich-h1">HR Dashboard</h1>
        <p class="tich-text tich-mt-2">Staff lifecycle, onboarding, contracts, and recruitment overview.</p>
    </div>

    <div class="tich-grid tich-grid--4 tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Total Staff</p>
            <p class="tich-stat__value">{{ $staffCount }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Active</p>
            <p class="tich-stat__value">{{ $activeStaffCount }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Onboarding</p>
            <p class="tich-stat__value">{{ $onboardingCount }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Contract Alerts (30 days)</p>
            <p class="tich-stat__value">{{ $contractAlerts['contracts']->count() + $contractAlerts['licenses']->count() + $contractAlerts['certificates']->count() }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Job applications</p>
            <p class="tich-stat__value">{{ $applicationCount }}</p>
            @if ($newApplicationsCount > 0)
                <p class="tich-caption" style="color: var(--tich-green);">{{ $newApplicationsCount }} new</p>
            @endif
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Leave awaiting HR</p>
            <p class="tich-stat__value">{{ $pendingLeaveCount }}</p>
            @if ($pendingLeaveCount > 0)
                <p class="tich-caption"><a href="{{ route('hr.leave.index', ['status' => 'pending_hr']) }}">Review now</a></p>
            @endif
        </div>
    </div>

    @if ($recentLeaveRequests->isNotEmpty())
        <article class="tich-card tich-mb-8">
            <div class="tich-flex tich-flex--between tich-mb-4">
                <h3 class="tich-h3">Recent leave requests</h3>
                <a href="{{ route('hr.leave.index') }}" class="tich-btn tich-btn-ghost">View all</a>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentLeaveRequests as $leaveRequest)
                            <tr>
                                <td>{{ $leaveRequest->staff->fullName() }}</td>
                                <td>{{ $leaveRequest->leaveType?->leave_name }}</td>
                                <td>{{ $leaveRequest->start_date->format('d M') }} – {{ $leaveRequest->end_date->format('d M Y') }}</td>
                                <td>{{ $leaveRequest->statusLabel() }}</td>
                                <td><a href="{{ route('hr.leave.show', $leaveRequest) }}" class="tich-btn tich-btn-ghost">Review</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <article class="tich-card">
            <h3 class="tich-h3">Recent Staff</h3>
            <div class="tich-mt-4">
                @forelse ($recentStaff as $member)
                    <div class="tich-list-row">
                        <div>
                            <strong>{{ $member->fullName() }}</strong>
                            <p class="tich-caption">{{ $member->employee_number }} · {{ ucfirst($member->employment_status) }}</p>
                        </div>
                        <a href="{{ route('hr.staff.show', $member) }}" class="tich-btn tich-btn-ghost">View</a>
                    </div>
                @empty
                    <p class="tich-text tich-text--secondary">No staff yet.</p>
                @endforelse
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Recent Contracts</h3>
            <div class="tich-mt-4">
                @forelse ($recentContracts as $contract)
                    <div class="tich-list-row">
                        <div>
                            <strong>{{ $contract->contract_number }}</strong>
                            <p class="tich-caption">{{ $contract->staff->fullName() ?? 'Unknown' }} · {{ ucfirst($contract->contract_type) }}</p>
                        </div>
                        <a href="{{ route('hr.contracts.show', $contract) }}" class="tich-btn tich-btn-ghost">View</a>
                    </div>
                @empty
                    <p class="tich-text tich-text--secondary">No contracts yet.</p>
                @endforelse
            </div>
        </article>
    </div>

    <article class="tich-card tich-mb-8">
        <div class="tich-flex tich-flex--between tich-mb-4">
            <h3 class="tich-h3">Recent job applications</h3>
            <a href="{{ route('hr.recruitment.index') }}" class="tich-btn tich-btn-ghost">View all</a>
        </div>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Position</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentApplications as $application)
                        <tr>
                            <td>
                                <strong>{{ $application->full_name }}</strong>
                                <p class="tich-caption">{{ $application->email }}</p>
                            </td>
                            <td>{{ $application->vacancy->job_title ?? '-' }}</td>
                            <td class="tich-caption">{{ $application->created_at->format('M j, Y') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $application->status)) }}</td>
                            <td><a href="{{ route('hr.recruitment.show', $application) }}" class="tich-btn tich-btn-ghost">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="tich-table-empty">No applications yet. Published vacancies appear on the public Careers page.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    @if ($defaultApplication)
        @include('hr.partials.recruitment-document-viewer', [
            'application' => $defaultApplication,
            'applications' => $applicationsWithDocuments,
            'applicationsPayload' => $applicationsPayload,
            'selectedApplicationId' => $defaultApplication->id,
            'viewerId' => 'hr-dashboard-doc-viewer',
            'title' => 'Application documents',
            'subtitle' => 'Preview CVs and supporting files from recent applications. Open externally for a full-screen view with print and download.',
        ])
    @endif

    <div class="tich-grid tich-grid--3">
        <a href="{{ route('hr.staff.index') }}" class="tich-card tich-card--hover" style="text-decoration: none; color: inherit;">
            <h3 class="tich-h3">Staff Directory</h3>
            <p class="tich-text tich-mt-2">View and manage employee profiles.</p>
        </a>
        <a href="{{ route('hr.onboarding.index') }}" class="tich-card tich-card--hover" style="text-decoration: none; color: inherit;">
            <h3 class="tich-h3">Onboarding</h3>
            <p class="tich-text tich-mt-2">Track new hire onboarding progress.</p>
        </a>
        <a href="{{ route('hr.contracts.index') }}" class="tich-card tich-card--hover" style="text-decoration: none; color: inherit;">
            <h3 class="tich-h3">Contracts</h3>
            <p class="tich-text tich-mt-2">Manage employment contracts and renewals.</p>
        </a>
        <a href="{{ route('hr.vacancies.index') }}" class="tich-card tich-card--hover" style="text-decoration: none; color: inherit;">
            <h3 class="tich-h3">Vacancies</h3>
            <p class="tich-text tich-mt-2">Publish job openings to the Careers page.</p>
        </a>
        <a href="{{ route('hr.recruitment.index') }}" class="tich-card tich-card--hover" style="text-decoration: none; color: inherit;">
            <h3 class="tich-h3">Recruitment</h3>
            <p class="tich-text tich-mt-2">Review applications submitted from the public Careers page.</p>
        </a>
    </div>
@endsection
