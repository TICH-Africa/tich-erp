@extends('layouts.academics')

@section('academics-content')
    @php $hub = \App\Support\AcademicsRouteParams::for(['learning_department' => request()->integer('learning_department') ?: null]); @endphp

    <div class="tich-mb-6">
        <a href="{{ route('departments.academics.lifecycle-requests.index', $hub) }}" class="tich-btn tich-btn-ghost">← Back</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">{{ $lifecycleRequest->typeLabel() }}</p>
            <h1 class="tich-leave-hero__title">{{ $lifecycleRequest->student?->fullName() ?? 'Student' }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge">{{ ucfirst($lifecycleRequest->status) }}</span>
                <span class="tich-caption">{{ $lifecycleRequest->student?->registration_number }}</span>
            </div>
        </div>
    </section>

    <div class="tich-grid tich-grid--2" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Request</h2>
            <dl class="tich-mt-4" style="display:grid; grid-template-columns:9rem 1fr; gap:0.5rem 1rem;">
                <dt class="tich-caption">Effective date</dt>
                <dd>{{ optional($lifecycleRequest->effective_date)->format('d M Y') ?: '-' }}</dd>
                <dt class="tich-caption">Reason</dt>
                <dd style="white-space:pre-wrap;">{{ $lifecycleRequest->reason ?: '-' }}</dd>
            </dl>
        </article>

        @if ($lifecycleRequest->status === 'pending')
            <article class="tich-card">
                <h2 class="tich-h3">Review</h2>
                <form method="POST" action="{{ route('departments.academics.lifecycle-requests.approve', array_merge($hub, ['lifecycleRequest' => $lifecycleRequest->id])) }}" class="tich-form-stack tich-mt-4">
                    @csrf
                    <div>
                        <label for="reviewer_notes" class="tich-label">Notes</label>
                        <textarea id="reviewer_notes" name="reviewer_notes" rows="3" class="tich-input"></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
                </form>
                <form method="POST" action="{{ route('departments.academics.lifecycle-requests.reject', array_merge($hub, ['lifecycleRequest' => $lifecycleRequest->id])) }}" class="tich-form-stack tich-mt-6">
                    @csrf
                    <div>
                        <label for="reject_notes" class="tich-label">Rejection notes</label>
                        <textarea id="reject_notes" name="reviewer_notes" rows="3" class="tich-input" required></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">Reject</button>
                </form>
            </article>
        @else
            <article class="tich-card">
                <h2 class="tich-h3">Decision</h2>
                <p class="tich-mt-4">{{ ucfirst($lifecycleRequest->status) }}</p>
                <p class="tich-caption">{{ $lifecycleRequest->reviewer_notes ?: '-' }}</p>
            </article>
        @endif
    </div>
@endsection
