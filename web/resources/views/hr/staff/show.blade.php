@extends('layouts.hr')

@section('title', $staff->fullName() . ' - Staff Profile')

@section('hr-content')
    <div class="tich-mb-8">
        <a href="{{ route('hr.staff.index') }}" class="tich-btn tich-btn-ghost">&larr; Back to staff</a>
    </div>

    <div class="tich-grid tich-grid--3 tich-mb-8">
        <article class="tich-card">
            <h3 class="tich-h3">Profile</h3>
            <div class="tich-mt-4">
                <p><strong>Employee No.:</strong> {{ $staff->employee_number }}</p>
                <p><strong>Name:</strong> {{ $staff->fullName() }}</p>
                <p><strong>Primary email:</strong> {{ $staff->primary_email }}</p>
                <p><strong>Organisation email:</strong> {{ $staff->organisation_email }}</p>
                <p><strong>Phone:</strong> {{ $staff->phone_number }}</p>
                <p><strong>Gender:</strong> {{ $staff->gender }}</p>
                <p><strong>DOB:</strong> {{ $staff->date_of_birth?->format('Y-m-d') }}</p>
                <p><strong>Nationality:</strong> {{ $staff->nationality }}</p>
                <p><strong>Home County:</strong> {{ $staff->home_county ?? '—' }}</p>
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Employment</h3>
            <div class="tich-mt-4">
                <p><strong>Department:</strong> {{ $staff->department->dept_name ?? '—' }}</p>
                <p><strong>Campus:</strong> {{ $staff->campus->campus_name ?? '—' }}</p>
                <p><strong>Job Title:</strong> {{ $staff->job_title }}</p>
                <p><strong>Grade:</strong> {{ $staff->job_grade ?? '—' }}</p>
                <p><strong>Category:</strong> {{ ucfirst($staff->employment_category) }}</p>
                <p><strong>Start Date:</strong> {{ $staff->employment_start_date?->format('Y-m-d') }}</p>
                <p><strong>Contract End:</strong> {{ $staff->contract_end_date?->format('Y-m-d') ?? '—' }}</p>
                <p><strong>Salary Scale:</strong> {{ $staff->salary_scale ?? '—' }}</p>
                <p><strong>Status:</strong> {{ ucfirst($staff->employment_status) }}</p>
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Statutory</h3>
            <div class="tich-mt-4">
                <p><strong>KRA PIN:</strong> {{ $staff->kra_pin ?? '—' }}</p>
                <p><strong>NSSF:</strong> {{ $staff->nssf_number ?? '—' }}</p>
                <p><strong>SHA:</strong> {{ $staff->sha_number ?? '—' }}</p>
                <p><strong>HELB:</strong> {{ $staff->helb_number ?? '—' }}</p>
                <p><strong>Bank:</strong> {{ $staff->bankAccount?->bank_name ?? '—' }}</p>
                <p><strong>Pension:</strong> {{ $staff->pensionScheme?->scheme_name ?? '—' }}</p>
            </div>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <article class="tich-card">
            <h3 class="tich-h3">Next of Kin</h3>
            <div class="tich-mt-4">
                @forelse ($staff->nextOfKin as $kin)
                    <div style="padding: 0.5rem 0; border-bottom: 1px solid var(--tich-neutral-border);">
                        <strong>{{ $kin->full_name }}</strong>
                        <span class="tich-badge tich-badge--info tich-ml-2">{{ $kin->relationship }}</span>
                        <p class="tich-caption tich-mt-1">{{ $kin->phone_number }} · {{ $kin->email ?? 'no email' }}</p>
                    </div>
                @empty
                    <p class="tich-text tich-text--secondary">No next of kin records.</p>
                @endforelse
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Active Allowances</h3>
            <div class="tich-mt-4">
                @forelse ($staff->activeAllowances as $allowance)
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--tich-neutral-border);">
                        <div>
                            <strong>{{ $allowance->allowance_name }}</strong>
                            <p class="tich-caption">{{ ucfirst($allowance->allowance_type) }} · Eff: {{ $allowance->effective_date?->format('Y-m-d') }}</p>
                        </div>
                        <strong>KES {{ number_format($allowance->amount, 2) }}</strong>
                    </div>
                @empty
                    <p class="tich-text tich-text--secondary">No active allowances.</p>
                @endforelse
            </div>
        </article>
    </div>

    <div class="tich-grid tich-grid--2">
        <article class="tich-card">
            <h3 class="tich-h3">Contracts</h3>
            <div class="tich-mt-4">
                @forelse ($staff->contracts as $contract)
                    <a href="{{ route('hr.contracts.show', $contract) }}" style="display: block; padding: 0.5rem 0; border-bottom: 1px solid var(--tich-neutral-border); text-decoration: none; color: inherit;">
                        <strong>{{ $contract->contract_number }}</strong>
                        <span class="tich-badge tich-badge--info tich-ml-2">{{ ucfirst($contract->contract_type) }}</span>
                        <p class="tich-caption tich-mt-1">{{ $contract->start_date?->format('Y-m-d') }} → {{ $contract->end_date?->format('Y-m-d') ?? 'Ongoing' }}</p>
                    </a>
                @empty
                    <p class="tich-text tich-text--secondary">No contracts.</p>
                @endforelse
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Documents</h3>
            <div class="tich-mt-4">
                @forelse ($staff->documents as $doc)
                    <div style="padding: 0.5rem 0; border-bottom: 1px solid var(--tich-neutral-border);">
                        <strong>{{ $doc->document_name }}</strong>
                        <span class="tich-badge tich-badge--{{ $doc->is_verified ? 'success' : 'warning' }} tich-ml-2">
                            {{ $doc->is_verified ? 'Verified' : 'Pending' }}
                        </span>
                        <p class="tich-caption tich-mt-1">{{ ucfirst($doc->document_type) }} · Exp: {{ $doc->expiry_date?->format('Y-m-d') ?? '—' }}</p>
                    </div>
                @empty
                    <p class="tich-text tich-text--secondary">No documents uploaded.</p>
                @endforelse
            </div>
        </article>
    </div>
@endsection
