@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = \App\Support\AcademicsRouteParams::for([
            'learning_department' => request()->integer('learning_department') ?: null,
        ]);
    @endphp

    <div class="tich-mb-6">
        <a href="{{ route('departments.academics.profile-changes.index', $hub) }}" class="tich-btn tich-btn-ghost">← Back to profile approvals</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">{{ ucfirst(str_replace('_', ' ', $profileChange->request_type)) }}</p>
            <h1 class="tich-leave-hero__title">{{ $profileChange->student?->fullName() ?? 'Student' }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge">{{ ucfirst($profileChange->status) }}</span>
                <span class="tich-caption">{{ $profileChange->student?->registration_number }} · submitted {{ $profileChange->created_at?->format('d M Y H:i') }}</span>
            </div>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mb-8" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Proposed changes</h2>
            <dl class="tich-mt-4" style="display:grid; grid-template-columns:8rem 1fr; gap:0.5rem 1rem;">
                @foreach (($profileChange->proposed_changes ?? []) as $field => $value)
                    <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $field)) }}</dt>
                    <dd>
                        <span class="tich-caption">was {{ $profileChange->current_snapshot[$field] ?? '-' }}</span><br>
                        <strong>{{ is_array($value) ? json_encode($value) : ($value ?: '-') }}</strong>
                    </dd>
                @endforeach
            </dl>
            @if ($profileChange->attachment_path)
                <p class="tich-caption tich-mt-4">Attachment: {{ $profileChange->attachment_path }}</p>
            @endif
            @if ($profileChange->student_notes)
                <p class="tich-text tich-mt-4"><strong>Student notes:</strong> {{ $profileChange->student_notes }}</p>
            @endif
        </article>

        @if ($profileChange->status === 'pending')
            <article class="tich-card">
                <h2 class="tich-h3">Review</h2>
                <form method="POST" action="{{ route('departments.academics.profile-changes.approve', array_merge($hub, ['profileChange' => $profileChange->id])) }}" class="tich-form-stack tich-mt-4">
                    @csrf
                    <div>
                        <label for="reviewer_notes" class="tich-label">Notes</label>
                        <textarea id="reviewer_notes" name="reviewer_notes" rows="3" class="tich-input">{{ old('reviewer_notes') }}</textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
                </form>
                <form method="POST" action="{{ route('departments.academics.profile-changes.reject', array_merge($hub, ['profileChange' => $profileChange->id])) }}" class="tich-form-stack tich-mt-6">
                    @csrf
                    <div>
                        <label for="rejection_reason" class="tich-label">Rejection reason</label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" class="tich-input" required>{{ old('rejection_reason') }}</textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">Reject</button>
                </form>
            </article>
        @else
            <article class="tich-card">
                <h2 class="tich-h3">Decision</h2>
                <p class="tich-mt-4">{{ ucfirst($profileChange->status) }} by {{ $profileChange->reviewer?->displayName() ?? '-' }}</p>
                <p class="tich-caption">{{ $profileChange->reviewed_at?->format('d M Y H:i') }}</p>
                @if ($profileChange->rejection_reason)
                    <p class="tich-text tich-mt-2">{{ $profileChange->rejection_reason }}</p>
                @endif
            </article>
        @endif
    </div>
@endsection
