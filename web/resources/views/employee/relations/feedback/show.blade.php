@extends('layouts.employee')

@section('title', 'Feedback #' . $feedback->id)

@section('employee-content')
    <div class="tich-mb-6">
        <a href="{{ route('employee.relations.feedback.index') }}" class="tich-btn tich-btn-ghost">← Back to my feedback</a>
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
    </section>

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
