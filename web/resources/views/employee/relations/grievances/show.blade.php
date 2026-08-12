@extends('layouts.employee')

@section('title', 'Grievance #' . $grievance->id)

@section('employee-content')
    <div class="tich-mb-6">
        <a href="{{ route('employee.relations.grievances.index') }}" class="tich-btn tich-btn-ghost">← Back to my grievances</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Grievance</p>
            <h1 class="tich-leave-hero__title">#{{ $grievance->id }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge tich-badge--{{ match($grievance->status) {
                    'open' => 'warning',
                    'under_review' => 'info',
                    'resolved' => 'success',
                    'closed' => 'secondary',
                    default => 'secondary',
                } }}">
                    {{ ucfirst(str_replace('_', ' ', $grievance->status)) }}
                </span>
                <span class="tich-caption">Type: {{ $grievance->grievance_type ?? '-' }}</span>
            </div>
        </div>
    </section>

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
            <h2 class="tich-h3">HR response</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $grievance->hr_comments }}</p>
        </article>
    @endif
@endsection
