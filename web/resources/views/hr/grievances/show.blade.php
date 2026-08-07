@extends('layouts.hr')

@section('title', 'Grievance #' . $grievance->id)

@section('hr-content')
    <div class="tich-mb-6">
        <a href="{{ route('hr.employee-relations.grievances.index') }}" class="tich-btn tich-btn-ghost">← Back to grievances</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Grievance</p>
            <h1 class="tich-leave-hero__title">{{ $grievance->reference_number ?? '#'.$grievance->id }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge tich-badge--{{ match($grievance->status) {
                    'open' => 'warning',
                    'under_review' => 'info',
                    'resolved' => 'success',
                    'closed' => 'secondary',
                    default => 'secondary',
                } }}">
                    {{ $grievance->statusLabel() }}
                </span>
                <span class="tich-caption">{{ $grievance->categoryLabel() }}</span>
                @if ($grievance->subject)
                    <span class="tich-caption">{{ $grievance->subject }}</span>
                @endif
            </div>
        </div>
        <div class="tich-leave-hero__actions">
            <a href="{{ route('hr.employee-relations.grievances.edit', $grievance) }}" class="tich-btn tich-btn-secondary">Edit</a>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mb-8" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Employee</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Name</span>
                    <span class="tich-kv-grid__value">{{ $grievance->staff->fullName() }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Employee no.</span>
                    <span class="tich-kv-grid__value">{{ $grievance->staff->employee_number }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Department</span>
                    <span class="tich-kv-grid__value">{{ $grievance->staff->department?->dept_name ?? '—' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Job title</span>
                    <span class="tich-kv-grid__value">{{ $grievance->staff->job_title ?? '—' }}</span>
                </div>
            </div>
            <a href="{{ route('hr.staff.show', $grievance->staff) }}" class="tich-btn tich-btn-ghost tich-mt-4">View staff profile</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Grievance details</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Incident date</span>
                    <span class="tich-kv-grid__value">{{ $grievance->incident_date?->format('d M Y') ?? '—' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Assigned to</span>
                    <span class="tich-kv-grid__value">{{ $grievance->assignedTo?->fullName() ?? '—' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Resolved</span>
                    <span class="tich-kv-grid__value">{{ $grievance->resolved_at?->format('d M Y') ?? '—' }}</span>
                </div>
            </div>
        </article>
    </div>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Description</h2>
        <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $grievance->description }}</p>
    </article>

    @if ($grievance->resolution_notes)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Resolution notes</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $grievance->resolution_notes }}</p>
        </article>
    @endif

    @if ($grievance->hr_comments)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">HR comments</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $grievance->hr_comments }}</p>
        </article>
    @endif
@endsection
