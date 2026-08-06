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
    </div>

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

@section('scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script id="hr-dashboard-chart-data" type="application/json">@json($chartData)</script>
    <script src="{{ asset('js/tich-hr-dashboard.js') }}" defer></script>
@endsection
