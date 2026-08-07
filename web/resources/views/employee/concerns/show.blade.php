@extends('layouts.employee')

@section('employee-content')
    <div class="tich-mb-6">
        <a href="{{ route('employee.concerns.index') }}" class="tich-btn tich-btn-ghost">← Back to concerns</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Employee concern</p>
            <h1 class="tich-leave-hero__title">{{ $concern->reference_number ?? '#'.$concern->id }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge tich-badge--{{ match($concern->status) {
                    'open' => 'warning',
                    'under_review' => 'info',
                    'resolved' => 'success',
                    'closed' => 'secondary',
                    default => 'secondary',
                } }}">
                    {{ $concern->statusLabel() }}
                </span>
                <span class="tich-caption">{{ $concern->categoryLabel() }}</span>
                @if ($concern->incident_date)
                    <span class="tich-caption">Incident: {{ $concern->incident_date->format('d M Y') }}</span>
                @endif
            </div>
        </div>
    </section>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">{{ $concern->subject ?? 'Concern details' }}</h2>
        <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $concern->description }}</p>
    </article>

    @if ($concern->resolution_notes)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Outcome you requested</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $concern->resolution_notes }}</p>
        </article>
    @endif

    @if ($concern->assignedTo)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Assigned HR contact</h2>
            <p class="tich-text tich-mt-4">{{ $concern->assignedTo->fullName() }}</p>
        </article>
    @endif

    @if ($concern->hr_comments)
        <article class="tich-card tich-mb-8" style="border-left:4px solid var(--tich-blue);">
            <h2 class="tich-h3">HR response</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $concern->hr_comments }}</p>
            @if ($concern->resolved_at)
                <p class="tich-caption tich-mt-4">Updated {{ $concern->resolved_at->format('d M Y') }}</p>
            @endif
        </article>
    @elseif (in_array($concern->status, ['open', 'under_review'], true))
        <article class="tich-card tich-mb-8">
            <p class="tich-text tich-text--secondary">HR is reviewing this concern. You will see updates here when they respond.</p>
        </article>
    @endif

    @if (in_array($concern->status, ['open', 'under_review'], true))
        <a href="{{ route('employee.concerns.create') }}" class="tich-btn tich-btn-ghost">Raise another concern</a>
    @endif
@endsection
