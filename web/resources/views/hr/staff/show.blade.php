@extends('layouts.hr')

@section('title', $staff->fullName() . ' - Staff Profile')

@section('hr-content')
    <x-page-toolbar :title="$staff->fullName()" :meta="$staff->employee_number" />

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
                <p><strong>Home County:</strong> {{ $staff->home_county ?? '-' }}</p>
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Employment</h3>
            <div class="tich-mt-4">
                <p><strong>Department:</strong> {{ $staff->department->dept_name ?? '-' }}</p>
                <p><strong>Campus:</strong> {{ $staff->campus->campus_name ?? '-' }}</p>
                <p><strong>Job Title:</strong> {{ $staff->job_title }}</p>
                <p><strong>Grade:</strong> {{ $staff->job_grade ?? '-' }}</p>
                <p><strong>Category:</strong> {{ config('tich-payroll.employment_categories.'.$staff->employment_category, ucfirst(str_replace('_', ' ', $staff->employment_category))) }}</p>
                <p><strong>Payroll scheme:</strong> {{ $staff->payrollSchemeLabel() }}</p>
                <p><strong>Start Date:</strong> {{ $staff->employment_start_date?->format('Y-m-d') }}</p>
                <p><strong>Contract End:</strong> {{ $staff->contract_end_date?->format('Y-m-d') ?? '-' }}</p>
                <p><strong>Salary Scale:</strong> {{ $staff->salary_scale ?? '-' }}</p>
                <p><strong>Status:</strong> {{ ucfirst($staff->employment_status) }}</p>
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Statutory</h3>
            <div class="tich-mt-4">
                <p><strong>KRA PIN:</strong> {{ $staff->kra_pin ?? '-' }}</p>
                <p><strong>NSSF:</strong> {{ $staff->nssf_number ?? '-' }}</p>
                <p><strong>SHA:</strong> {{ $staff->sha_number ?? '-' }}</p>
                <p><strong>HELB:</strong> {{ $staff->helb_number ?? '-' }}</p>
                <p><strong>Bank:</strong> {{ $staff->bankAccount?->bank_name ?? '-' }}</p>
                <p><strong>Pension:</strong> {{ $staff->pensionScheme?->scheme_name ?? '-' }}</p>
            </div>
        </article>
    </div>

    @if ($staff->latestOnboarding)
        <div class="tich-card tich-mb-8">
            <h3 class="tich-h3">Onboarding</h3>
            <div class="tich-mt-4">
                <p><strong>Onboarding No.:</strong> {{ $staff->latestOnboarding->onboarding_number }}</p>
                <p><strong>Current Step:</strong> {{ ucfirst(str_replace('_', ' ', $staff->latestOnboarding->current_step)) }}</p>
                <p><strong>Status:</strong>
                    <span class="tich-badge tich-badge--{{ $staff->latestOnboarding->status === 'completed' ? 'success' : ($staff->latestOnboarding->status === 'rejected' ? 'danger' : ($staff->latestOnboarding->status === 'approved' ? 'success' : 'warning')) }}">
                        {{ ucfirst($staff->latestOnboarding->status) }}
                    </span>
                </p>
                @if ($staff->latestOnboarding->status === 'pending_hr_review')
                    <a href="{{ route('hr.onboarding.review', $staff->latestOnboarding) }}" class="tich-btn tich-btn-primary tich-mt-4">Review Biodata</a>
                @endif
            </div>
        </div>
    @endif

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
                    <a href="{{ route('hr.contracts.show', $contract) }}" class="tich-list-item">
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
            <div class="tich-flex tich-flex--between tich-mb-4">
                <h3 class="tich-h3 mb-0">Documents</h3>
                <a href="{{ route('hr.staff.documents.create', $staff) }}" class="tich-btn tich-btn-primary tich-btn--sm">
                    + Upload
                </a>
            </div>

            @forelse ($staff->documents as $doc)
                <div class="tich-doc-card">
                    <div class="tich-doc-card__body">
                        <div class="tich-doc-card__row">
                            <div class="tich-doc-card__icon">
                                <svg class="tich-doc-card__svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-2 0V8m-2 4h2m0 4.01h.01M9 16h6" />
                                </svg>
                            </div>
                            <div class="tich-doc-card__content">
                                <strong class="tich-doc-card__title">{{ $doc->document_name }}</strong>
                                <p class="tich-doc-card__meta">
                                    {{ ucfirst($doc->document_type) }}
                                    @if($doc->expiry_date)
                                        · Exp: {{ $doc->expiry_date->format('Y-m-d') }}
                                    @endif
                                </p>
                            </div>
                            <span class="tich-badge tich-badge--{{ $doc->status === 'approved' ? 'success' : ($doc->status === 'rejected' ? 'danger' : 'warning') }} tich-badge--sm">
                                {{ ucfirst($doc->status ?? 'pending') }}
                            </span>
                        </div>
                        <div class="tich-doc-card__actions">
                            <a href="{{ route('hr.staff.documents.download', [$staff, $doc]) }}" class="tich-btn tich-btn-ghost tich-btn--sm">
                                Download
                            </a>
                            <form method="POST" action="{{ route('hr.staff.documents.destroy', [$staff, $doc]) }}" onsubmit="return confirm('Delete this document?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-btn tich-btn--sm tich-btn--danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="tich-text tich-text--secondary">No documents uploaded.</p>
            @endforelse
        </article>
    </div>
    @include('partials.staff-profile-update-prompt-form', [
        'action' => route('hr.staff.profile-update-prompt.store', $staff),
        'staff' => $staff,
    ])
@endsection
