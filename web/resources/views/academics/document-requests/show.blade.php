@extends('layouts.academics')

@section('academics-content')
    @php $hub = \App\Support\AcademicsRouteParams::for(['learning_department' => request()->integer('learning_department') ?: null]); @endphp

    <div class="tich-mb-6">
        <a href="{{ route('departments.academics.document-requests.index', $hub) }}" class="tich-btn tich-btn-ghost">← Back</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">{{ $documentRequest->typeLabel() }}</p>
            <h1 class="tich-leave-hero__title">{{ $documentRequest->student?->fullName() ?? 'Student' }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge">{{ ucfirst($documentRequest->status) }}</span>
                <span class="tich-caption">{{ $documentRequest->student?->registration_number }}</span>
            </div>
        </div>
    </section>

    <div class="tich-grid tich-grid--2" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Request</h2>
            <p class="tich-text tich-mt-4" style="white-space:pre-wrap;">{{ $documentRequest->student_notes ?: 'No student notes.' }}</p>
        </article>

        @if ($documentRequest->status === 'pending')
            <article class="tich-card">
                <h2 class="tich-h3">Action</h2>
                <form method="POST" action="{{ route('departments.academics.document-requests.issue', array_merge($hub, ['documentRequest' => $documentRequest->id])) }}" class="tich-form-stack tich-mt-4">
                    @csrf
                    <div>
                        <label for="reviewer_notes" class="tich-label">Notes</label>
                        <textarea id="reviewer_notes" name="reviewer_notes" rows="3" class="tich-input"></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">Mark issued</button>
                </form>
                <form method="POST" action="{{ route('departments.academics.document-requests.reject', array_merge($hub, ['documentRequest' => $documentRequest->id])) }}" class="tich-form-stack tich-mt-6">
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
                <p class="tich-mt-4">{{ ucfirst($documentRequest->status) }}</p>
                <p class="tich-caption">{{ $documentRequest->reviewer_notes ?: '-' }}</p>
            </article>
        @endif
    </div>
@endsection
