@extends('layouts.hr')

@section('title', 'HR Dashboard')

@section('hr-content')
@php
    $contractAlertTotal = $contractAlerts['contracts']->count() + $contractAlerts['licenses']->count() + $contractAlerts['certificates']->count();
@endphp

<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">People operations</p>
            <h1 class="tich-mod-dash__title">HR command center</h1>
            <p class="tich-mod-dash__lede">Staff lifecycle, onboarding, contracts, leave, and recruitment — live overview.</p>
        </div>
        <div class="tich-mod-dash__hero-actions">
            <a href="{{ route('hr.staff.create') }}" class="tich-btn tich-btn-primary">Register staff</a>
            <a href="{{ route('hr.recruitment.index') }}" class="tich-btn tich-btn-secondary">Recruitment inbox</a>
        </div>
    </header>

    <section class="tich-mod-dash__metrics" aria-label="Key HR metrics">
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Total staff</p>
            <p class="tich-mod-dash__metric-value">{{ $staffCount }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--ok">
            <p class="tich-mod-dash__metric-label">Active</p>
            <p class="tich-mod-dash__metric-value">{{ $activeStaffCount }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--info">
            <p class="tich-mod-dash__metric-label">Onboarding</p>
            <p class="tich-mod-dash__metric-value">{{ $onboardingCount }}</p>
        </article>
        <article class="tich-mod-dash__metric {{ $contractAlertTotal > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Contract alerts</p>
            <p class="tich-mod-dash__metric-value">{{ $contractAlertTotal }}</p>
            <p class="tich-mod-dash__metric-hint">Next 30 days</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Applications</p>
            <p class="tich-mod-dash__metric-value">{{ $applicationCount }}</p>
            @if ($newApplicationsCount > 0)
                <p class="tich-mod-dash__metric-hint tich-mod-dash__metric-hint--pulse">{{ $newApplicationsCount }} new</p>
            @endif
        </article>
        <article class="tich-mod-dash__metric {{ $pendingLeaveCount > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Leave awaiting HR</p>
            <p class="tich-mod-dash__metric-value">{{ $pendingLeaveCount }}</p>
            @if ($pendingLeaveCount > 0)
                <a href="{{ route('hr.leave.index', ['status' => 'pending_hr']) }}" class="tich-mod-dash__metric-link">Review</a>
            @endif
        </article>
        <article class="tich-mod-dash__metric {{ $pendingProfileChangeCount > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Profile updates</p>
            <p class="tich-mod-dash__metric-value">{{ $pendingProfileChangeCount }}</p>
            @if ($pendingProfileChangeCount > 0)
                <a href="{{ route('hr.profile-changes.index', ['status' => 'pending']) }}" class="tich-mod-dash__metric-link">Review</a>
            @endif
        </article>
    </section>

    @if ($pendingProfileChanges->isNotEmpty())
        <article class="tich-mod-dash__panel tich-mod-dash__panel--attention" id="profile-changes-inbox">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Action queue</p>
                    <h2 class="tich-mod-dash__panel-title">Profile updates awaiting approval</h2>
                    <p class="tich-mod-dash__panel-meta">Contact, photo, and qualification changes from the employee portal.</p>
                </div>
                <a href="{{ route('hr.profile-changes.index', ['status' => 'pending']) }}" class="tich-btn tich-btn-ghost">View all</a>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table tich-mod-dash__table">
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
                                <td class="tich-caption">{{ $summary ?: '-' }}</td>
                                <td class="tich-caption">{{ $changeRequest->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('hr.profile-changes.show', $changeRequest) }}" class="tich-btn tich-btn-primary" style="font-size:0.8125rem; padding:0.35rem 0.75rem;">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif

    <section class="tich-mod-dash__charts" aria-label="HR statistics charts">
        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Staff by status</h3>
                <p class="tich-mod-dash__chart-meta">Employment status breakdown</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="hr-chart-staff-status" aria-label="Staff by status chart"></canvas>
            </div>
        </article>

        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Staff by department</h3>
                <p class="tich-mod-dash__chart-meta">Top departments by headcount</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="hr-chart-staff-departments" aria-label="Staff by department chart"></canvas>
            </div>
        </article>

        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Leave pipeline</h3>
                <p class="tich-mod-dash__chart-meta">Open and completed leave requests</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="hr-chart-leave-status" aria-label="Leave requests by status chart"></canvas>
            </div>
        </article>

        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Recruitment pipeline</h3>
                <p class="tich-mod-dash__chart-meta">Job applications by stage</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="hr-chart-applications-status" aria-label="Job applications by status chart"></canvas>
            </div>
        </article>
    </section>

    <div class="tich-mod-dash__invite">
        @include('partials.staff-registration-invite-form', [
            'action' => route('hr.registration-invites.store'),
        ])
    </div>

    <section class="tich-mod-dash__nav" aria-label="HR shortcuts">
        <p class="tich-mod-dash__section-label">Quick routes</p>
        <div class="tich-mod-dash__nav-grid">
            <a href="{{ route('hr.staff.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">01</span>
                <h3 class="tich-mod-dash__nav-title">Staff directory</h3>
                <p class="tich-mod-dash__nav-text">View and manage employee profiles.</p>
            </a>
            <a href="{{ route('hr.profile-changes.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">02</span>
                <h3 class="tich-mod-dash__nav-title">Profile changes</h3>
                <p class="tich-mod-dash__nav-text">Approve contact, photo, and qualification updates.</p>
                @if ($pendingProfileChangeCount > 0)
                    <span class="tich-mod-dash__nav-badge">{{ $pendingProfileChangeCount }} pending</span>
                @endif
            </a>
            <a href="{{ route('hr.onboarding.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">03</span>
                <h3 class="tich-mod-dash__nav-title">Onboarding</h3>
                <p class="tich-mod-dash__nav-text">Track new hire onboarding progress.</p>
            </a>
            <a href="{{ route('hr.contracts.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">04</span>
                <h3 class="tich-mod-dash__nav-title">Contracts</h3>
                <p class="tich-mod-dash__nav-text">Manage employment contracts and renewals.</p>
            </a>
            <a href="{{ route('hr.vacancies.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">05</span>
                <h3 class="tich-mod-dash__nav-title">Vacancies</h3>
                <p class="tich-mod-dash__nav-text">Publish job openings to the Careers page.</p>
            </a>
            <a href="{{ route('hr.recruitment.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">06</span>
                <h3 class="tich-mod-dash__nav-title">Recruitment</h3>
                <p class="tich-mod-dash__nav-text">Review applications from the public Careers page.</p>
            </a>
        </div>
    </section>
</div>
@endsection

@section('scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script id="hr-dashboard-chart-data" type="application/json">@json($chartData)</script>
    <script src="{{ asset('js/tich-hr-dashboard.js') }}" defer></script>
@endsection
