@extends('layouts.hr')

@section('title', $staff->fullName() . ' - Staff Profile')

@section('hr-content')
    @include('partials.financial-privacy')

    <x-page-toolbar :title="$staff->fullName()" :meta="$staff->employee_number">
        <x-slot:actions>
            <a href="{{ route('hr.staff.profile-update-prompt.create', $staff) }}" class="tich-btn tich-btn-secondary">Request profile update</a>
            <a href="{{ route('hr.staff.edit', $staff) }}" class="tich-btn tich-btn-primary">Edit staff</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-hr-profile-header">
        <div class="tich-hr-profile-header__photo">
            @if ($staff->photoUrl())
                <img src="{{ $staff->photoUrl() }}" alt="{{ $staff->fullName() }}">
            @else
                <span class="tich-hr-profile-header__initials">{{ $staff->initials() }}</span>
            @endif
        </div>
        <div class="tich-hr-profile-header__main">
            <h2 class="tich-hr-profile-header__name">{{ $staff->fullName() }}</h2>
            <div class="tich-hr-profile-header__meta">
                <span>{{ $staff->job_title ?: 'No job title' }}</span>
                <span>{{ $staff->department->dept_name ?? 'Unassigned department' }}</span>
                <span>{{ $staff->campus->campus_name ?? 'No campus' }}</span>
                <span class="tich-badge tich-badge--{{ $staff->employment_status === 'active' ? 'success' : ($staff->employment_status === 'terminated' ? 'danger' : 'warning') }}">
                    {{ ucfirst(str_replace('_', ' ', $staff->employment_status)) }}
                </span>
            </div>
        </div>
    </div>

    <div class="tich-detail-grid tich-detail-grid--3 tich-mb-8">
        <section class="tich-detail-card">
            <h3 class="tich-detail-card__title">Profile</h3>
            <dl class="tich-dl">
                <div class="tich-dl__row"><dt class="tich-dl__label">Employee No.</dt><dd class="tich-dl__value">{{ $staff->employee_number }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Primary email</dt><dd class="tich-dl__value">{{ $staff->primary_email ?: '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Organisation email</dt><dd class="tich-dl__value">{{ $staff->organisation_email ?: '—' }}@if (! $staff->organisation_email)<span class="tich-caption"> · Assigned by ICT</span>@endif</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Phone</dt><dd class="tich-dl__value">{{ $staff->phone_number ?: '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Gender</dt><dd class="tich-dl__value">{{ $staff->gender ?: '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Date of birth</dt><dd class="tich-dl__value">{{ $staff->date_of_birth?->format('d M Y') ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Nationality</dt><dd class="tich-dl__value">{{ $staff->nationality ?: '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Home county</dt><dd class="tich-dl__value">{{ $staff->home_county ?? '—' }}</dd></div>
            </dl>
        </section>

        <section class="tich-detail-card">
            <h3 class="tich-detail-card__title">Employment</h3>
            <dl class="tich-dl">
                <div class="tich-dl__row"><dt class="tich-dl__label">Department</dt><dd class="tich-dl__value">{{ $staff->department->dept_name ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Campus</dt><dd class="tich-dl__value">{{ $staff->campus->campus_name ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Job title</dt><dd class="tich-dl__value">{{ $staff->job_title ?: '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Grade</dt><dd class="tich-dl__value">{{ $staff->job_grade ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Category</dt><dd class="tich-dl__value">{{ config('tich-payroll.employment_categories.'.$staff->employment_category, ucfirst(str_replace('_', ' ', $staff->employment_category))) }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Payroll scheme</dt><dd class="tich-dl__value">{{ $staff->payrollSchemeLabel() }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Start date</dt><dd class="tich-dl__value">{{ $staff->employment_start_date?->format('d M Y') ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Contract end</dt><dd class="tich-dl__value">{{ $staff->contract_end_date?->format('d M Y') ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Salary scale</dt><dd class="tich-dl__value">{{ $staff->salary_scale ?? '—' }}</dd></div>
            </dl>
        </section>

        <section class="tich-detail-card">
            <h3 class="tich-detail-card__title">Statutory</h3>
            <dl class="tich-dl">
                <div class="tich-dl__row"><dt class="tich-dl__label">KRA PIN</dt><dd class="tich-dl__value">{{ $staff->kra_pin ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">NSSF</dt><dd class="tich-dl__value">{{ $staff->nssf_number ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">SHA</dt><dd class="tich-dl__value">{{ $staff->sha_number ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">HELB</dt><dd class="tich-dl__value">{{ $staff->helb_number ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Bank</dt><dd class="tich-dl__value">{{ $staff->bankAccount?->bank_name ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Pension</dt><dd class="tich-dl__value">{{ $staff->pensionScheme?->scheme_name ?? '—' }}</dd></div>
            </dl>
        </section>
    </div>

    @if ($staff->latestOnboarding)
        <section class="tich-detail-card tich-mb-8">
            <h3 class="tich-detail-card__title">Onboarding</h3>
            <dl class="tich-dl tich-mt-2">
                <div class="tich-dl__row"><dt class="tich-dl__label">Onboarding No.</dt><dd class="tich-dl__value">{{ $staff->latestOnboarding->onboarding_number }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Current step</dt><dd class="tich-dl__value">{{ ucfirst(str_replace('_', ' ', $staff->latestOnboarding->current_step)) }}</dd></div>
                <div class="tich-dl__row">
                    <dt class="tich-dl__label">Status</dt>
                    <dd class="tich-dl__value">
                        <span class="tich-badge tich-badge--{{ $staff->latestOnboarding->status === 'completed' ? 'success' : ($staff->latestOnboarding->status === 'rejected' ? 'danger' : ($staff->latestOnboarding->status === 'approved' ? 'success' : 'warning')) }}">
                            {{ ucfirst($staff->latestOnboarding->status) }}
                        </span>
                    </dd>
                </div>
            </dl>
            @if ($staff->latestOnboarding->status === 'pending_hr_review')
                <a href="{{ route('hr.onboarding.review', $staff->latestOnboarding) }}" class="tich-btn tich-btn-primary tich-mt-4">Review biodata</a>
            @endif
        </section>
    @endif

    <div class="tich-detail-grid tich-detail-grid--2 tich-mb-8">
        <section class="tich-detail-card">
            <h3 class="tich-detail-card__title">Next of kin</h3>
            @forelse ($staff->nextOfKin as $kin)
                <div style="padding: 0.65rem 0; border-bottom: 1px solid var(--tich-neutral-border);">
                    <strong>{{ $kin->full_name }}</strong>
                    <span class="tich-badge tich-badge--info tich-ml-2">{{ $kin->relationship }}</span>
                    <p class="tich-caption tich-mt-1">{{ $kin->phone_number }} · {{ $kin->email ?? 'no email' }}</p>
                </div>
            @empty
                <p class="tich-text tich-text--secondary">No next of kin records.</p>
            @endforelse
        </section>

        <section class="tich-detail-card">
            <h3 class="tich-detail-card__title">Active allowances</h3>
            @forelse ($staff->activeAllowances as $allowance)
                <div style="display:flex; justify-content:space-between; gap:1rem; padding:0.65rem 0; border-bottom:1px solid var(--tich-neutral-border);">
                    <div>
                        <strong>{{ $allowance->allowance_name }}</strong>
                        <p class="tich-caption">{{ ucfirst($allowance->allowance_type) }} · Eff: {{ $allowance->effective_date?->format('d M Y') }}</p>
                    </div>
                    <x-financial-value :value="number_format($allowance->amount, 2)" />
                </div>
            @empty
                <p class="tich-text tich-text--secondary">No active allowances.</p>
            @endforelse
        </section>
    </div>

    <div class="tich-detail-grid tich-detail-grid--2">
        <section class="tich-detail-card">
            <h3 class="tich-detail-card__title">Contracts</h3>
            @forelse ($staff->contracts as $contract)
                <a href="{{ route('hr.contracts.show', $contract) }}" class="tich-list-item" style="display:block; padding:0.65rem 0; border-bottom:1px solid var(--tich-neutral-border); text-decoration:none;">
                    <strong>{{ $contract->contract_number }}</strong>
                    <span class="tich-badge tich-badge--info tich-ml-2">{{ ucfirst($contract->contract_type) }}</span>
                    <p class="tich-caption tich-mt-1">{{ $contract->start_date?->format('d M Y') }} → {{ $contract->end_date?->format('d M Y') ?? 'Ongoing' }}</p>
                </a>
            @empty
                <p class="tich-text tich-text--secondary">No contracts.</p>
            @endforelse
        </section>

        <section class="tich-detail-card">
            <div class="tich-flex tich-flex--between tich-mb-4">
                <h3 class="tich-detail-card__title mb-0">Documents</h3>
                <a href="{{ route('hr.staff.documents.create', $staff) }}" class="tich-btn tich-btn-primary tich-btn--sm">+ Upload</a>
            </div>

            @forelse ($staff->documents as $doc)
                <div class="tich-doc-card">
                    <div class="tich-doc-card__body">
                        <div class="tich-doc-card__row">
                            <div class="tich-doc-card__icon">
                                <svg class="tich-doc-card__svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-2 0V8m-2 4h2m0 4.01h.01M9 16h6" />
                                </svg>
                            </div>
                            <div class="tich-doc-card__content">
                                <strong class="tich-doc-card__title">{{ $doc->document_name }}</strong>
                                <p class="tich-doc-card__meta">
                                    {{ ucfirst($doc->document_type) }}
                                    @if($doc->expiry_date)
                                        · Exp: {{ $doc->expiry_date->format('d M Y') }}
                                    @endif
                                </p>
                            </div>
                            <span class="tich-badge tich-badge--{{ $doc->status === 'approved' ? 'success' : ($doc->status === 'rejected' ? 'danger' : 'warning') }} tich-badge--sm">
                                {{ ucfirst($doc->status ?? 'pending') }}
                            </span>
                        </div>
                        <div class="tich-doc-card__actions">
                            <a href="{{ route('hr.staff.documents.download', [$staff, $doc]) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Download</a>
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
        </section>
    </div>
@endsection
