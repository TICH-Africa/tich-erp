@extends('layouts.hr')

@section('title', 'Feedback #' . $feedback->id)

@section('hr-content')
    <div class="tich-mb-6">
        <a href="{{ route('hr.employee-relations.feedback.index') }}" class="tich-btn tich-btn-ghost">← Back to feedback</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Feedback</p>
            <h1 class="tich-leave-hero__title">#{{ $feedback->id }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge tich-badge--{{ match($feedback->status) {
                    'open' => 'warning',
                    'under_review' => 'info',
                    'resolved' => 'success',
                    'closed' => 'secondary',
                    default => 'secondary',
                } }}">
                    {{ ucfirst(str_replace('_', ' ', $feedback->status)) }}
                </span>
                <span class="tich-caption">Type: {{ $feedback->feedback_type ?? '-' }}</span>
            </div>
        </div>
        <div class="tich-leave-hero__actions">
            <a href="{{ route('hr.employee-relations.feedback.edit', $feedback) }}" class="tich-btn tich-btn-secondary">Edit</a>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mb-8" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Employee</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Name</span>
                    <span class="tich-kv-grid__value">{{ $feedback->staff->fullName() }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Employee no.</span>
                    <span class="tich-kv-grid__value">{{ $feedback->staff->employee_number }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Department</span>
                    <span class="tich-kv-grid__value">{{ $feedback->staff->department?->dept_name ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Job title</span>
                    <span class="tich-kv-grid__value">{{ $feedback->staff->job_title ?? '-' }}</span>
                </div>
            </div>
            <a href="{{ route('hr.staff.show', $feedback->staff) }}" class="tich-btn tich-btn-ghost tich-mt-4">View staff profile</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Feedback details</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Status</span>
                    <span class="tich-kv-grid__value">{{ ucfirst(str_replace('_', ' ', $feedback->status)) }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Resolved</span>
                    <span class="tich-kv-grid__value">{{ $feedback->resolved_at?->format('d M Y') ?? '-' }}</span>
                </div>
            </div>
        </article>
    </div>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Description</h2>
        <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $feedback->description }}</p>
    </article>

    @if ($feedback->response)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Response</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $feedback->response }}</p>
        </article>
    @endif

    @if ($feedback->hr_comments)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">HR comments</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $feedback->hr_comments }}</p>
        </article>
    @endif
@endsection
