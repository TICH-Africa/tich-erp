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
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <article class="tich-card">
            <h3 class="tich-h3">Recent Staff</h3>
            <div class="tich-mt-4">
                @forelse ($recentStaff as $member)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--tich-neutral-border);">
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
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--tich-neutral-border);">
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
            <p class="tich-text tich-mt-2">Manage job openings and recruitment.</p>
        </a>
    </div>
@endsection
