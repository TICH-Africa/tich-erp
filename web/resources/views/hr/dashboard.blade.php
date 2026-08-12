@extends('layouts.hr')

@section('title', 'HR Dashboard')

@section('hr-content')
    <x-page-toolbar title="HR Dashboard" meta="Staff lifecycle, onboarding, contracts, and recruitment" />

    <div class="tich-stat-row tich-mb-8">
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
        <div class="tich-stat">
            <p class="tich-stat__label">Profile updates pending</p>
            <p class="tich-stat__value">{{ $pendingProfileChangeCount }}</p>
            @if ($pendingProfileChangeCount > 0)
                <p class="tich-caption"><a href="{{ route('hr.profile-changes.index', ['status' => 'pending']) }}">Review now</a></p>
            @endif
        </div>
    </div>

    @if ($pendingProfileChanges->isNotEmpty())
        <article class="tich-card tich-mb-8" id="profile-changes-inbox">
            <div class="tich-flex tich-flex--between tich-mb-4" style="flex-wrap:wrap; gap:0.75rem; align-items:flex-start;">
                <div>
                    <h2 class="tich-h3" style="margin:0;">Employee profile updates awaiting approval</h2>
                    <p class="tich-caption tich-mt-2">Review contact, photo, and qualification changes submitted from the employee portal.</p>
                </div>
                <a href="{{ route('hr.profile-changes.index', ['status' => 'pending']) }}" class="tich-btn tich-btn-ghost">View all</a>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Request type</th>
                            <th>Summary</th>
                            <th>Submitted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingProfileChanges as $changeRequest)
                            @php
                                $summary = $changeRequest->request_type === 'profile_update'
                                    ? collect($changeRequest->proposed_changes ?? [])->keys()->map(fn ($f) => ucwords(str_replace('_', ' ', $f)))->take(3)->join(', ')
                                    : ($changeRequest->proposed_changes['qualification_name'] ?? $changeRequest->proposed_changes['subject'] ?? $changeRequest->typeLabel());
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $changeRequest->staff->fullName() }}</strong>
                                    <p class="tich-caption">{{ $changeRequest->staff->employee_number }}</p>
                                </td>
                                <td>{{ $changeRequest->typeLabel() }}</td>
                                <td class="tich-caption">{{ $summary ?: '—' }}</td>
                                <td class="tich-caption">{{ $changeRequest->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('hr.profile-changes.show', $changeRequest) }}" class="tich-btn tich-btn-primary" style="font-size:0.8125rem; padding:0.35rem 0.75rem;">Review &amp; approve</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif

    <section class="tich-dashboard-charts tich-mb-8" aria-label="HR statistics charts">
        <article class="tich-card tich-chart-card">
            <h3 class="tich-h3">Staff by status</h3>
            <p class="tich-chart-card__meta">Employment status breakdown</p>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="hr-chart-staff-status" aria-label="Staff by status chart"></canvas>
            </div>
        </article>

        <article class="tich-card tich-chart-card">
            <h3 class="tich-h3">Staff by department</h3>
            <p class="tich-chart-card__meta">Top departments by headcount</p>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="hr-chart-staff-departments" aria-label="Staff by department chart"></canvas>
            </div>
        </article>

        <article class="tich-card tich-chart-card">
            <h3 class="tich-h3">Leave pipeline</h3>
            <p class="tich-chart-card__meta">Open and completed leave requests</p>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="hr-chart-leave-status" aria-label="Leave requests by status chart"></canvas>
            </div>
        </article>

        <article class="tich-card tich-chart-card">
            <h3 class="tich-h3">Recruitment pipeline</h3>
            <p class="tich-chart-card__meta">Job applications by stage</p>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="hr-chart-applications-status" aria-label="Job applications by status chart"></canvas>
            </div>
        </article>
    </section>

    @include('partials.staff-registration-invite-form', [
        'action' => route('hr.registration-invites.store'),
    ])

    <div class="tich-grid tich-grid--3">
        <a href="{{ route('hr.staff.index') }}" class="tich-card tich-card--hover" style="text-decoration: none; color: inherit;">
            <h3 class="tich-h3">Staff Directory</h3>
            <p class="tich-text tich-mt-2">View and manage employee profiles.</p>
        </a>
        <a href="{{ route('hr.profile-changes.index') }}" class="tich-card tich-card--hover" style="text-decoration: none; color: inherit;">
            <h3 class="tich-h3">Profile changes</h3>
            <p class="tich-text tich-mt-2">Approve employee contact, photo, and qualification updates.</p>
            @if ($pendingProfileChangeCount > 0)
                <p class="tich-caption tich-mt-2" style="color:#b45309;">{{ $pendingProfileChangeCount }} pending</p>
            @endif
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

@section('scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script id="hr-dashboard-chart-data" type="application/json">@json($chartData)</script>
    <script src="{{ asset('js/tich-hr-dashboard.js') }}" defer></script>
@endsection
