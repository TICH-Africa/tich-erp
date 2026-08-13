@extends('layouts.hr')

@section('title', 'Disciplinary case ' . $case->case_number)

@section('hr-content')
    <div class="tich-mb-6">
        <a href="{{ route('hr.employee-relations.disciplinary.index') }}" class="tich-btn tich-btn-ghost">← Back to cases</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Disciplinary case</p>
            <h1 class="tich-leave-hero__title">{{ $case->case_number }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge tich-badge--{{ match($case->status) {
                    'open' => 'warning',
                    'under_investigation' => 'info',
                    'hearing_scheduled' => 'info',
                    'decided' => 'success',
                    'appealed' => 'warning',
                    'closed' => 'secondary',
                    default => 'secondary',
                } }}">
                    {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                </span>
                <span class="tich-caption">Incident: {{ $case->incident_date->format('d M Y') }}</span>
            </div>
        </div>
        <div class="tich-leave-hero__actions">
            <a href="{{ route('hr.employee-relations.disciplinary.edit', $case) }}" class="tich-btn tich-btn-secondary">Edit case</a>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mb-8" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Employee</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Name</span>
                    <span class="tich-kv-grid__value">{{ $case->staff->fullName() }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Employee no.</span>
                    <span class="tich-kv-grid__value">{{ $case->staff->employee_number }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Department</span>
                    <span class="tich-kv-grid__value">{{ $case->staff->department?->dept_name ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Job title</span>
                    <span class="tich-kv-grid__value">{{ $case->staff->job_title ?? '-' }}</span>
                </div>
            </div>
            <a href="{{ route('hr.staff.show', $case->staff) }}" class="tich-btn tich-btn-ghost tich-mt-4">View staff profile</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Case details</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Incident date</span>
                    <span class="tich-kv-grid__value">{{ $case->incident_date->format('d M Y') }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Hearing date</span>
                    <span class="tich-kv-grid__value">{{ $case->hearing_date?->format('d M Y') ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Assigned to</span>
                    <span class="tich-kv-grid__value">{{ $case->assignedTo?->fullName() ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Action type</span>
                    <span class="tich-kv-grid__value">{{ ucfirst($case->action_type ?? '-') }}</span>
                </div>
                @if ($case->action_start_date)
                    <div>
                        <span class="tich-kv-grid__label">Action period</span>
                        <span class="tich-kv-grid__value">
                            {{ $case->action_start_date->format('d M Y') }}
                            {{ $case->action_end_date ? ' → ' . $case->action_end_date->format('d M Y') : '' }}
                        </span>
                    </div>
                @endif
            </div>
        </article>
    </div>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Incident description</h2>
        <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->incident_description }}</p>
    </article>

    @if ($case->investigation_notes)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Investigation notes</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->investigation_notes }}</p>
        </article>
    @endif

    @if ($case->witness_information)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Witness information</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->witness_information }}</p>
        </article>
    @endif

    @if ($case->committee_members)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Committee members</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->committee_members }}</p>
        </article>
    @endif

    @if ($case->decision)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Decision</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->decision }}</p>
        </article>
    @endif

    @if ($case->hr_comments)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">HR comments</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->hr_comments }}</p>
        </article>
    @endif
@endsection
