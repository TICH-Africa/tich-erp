@extends('layouts.academics')

@section('academics-content')
    @php $hub = \App\Support\AcademicsRouteParams::for(['learning_department' => request()->integer('learning_department') ?: null]); @endphp

    <x-page-toolbar title="Document change" :meta="$submission->typeLabel()">
        <x-slot:actions>
            <a href="{{ route('departments.academics.document-submissions.index', $hub) }}" class="tich-btn tich-btn-secondary">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card tich-mt-8" style="padding:1.25rem;">
        <p class="tich-caption">{{ $submission->student?->registration_number }} · {{ $submission->student?->fullName() }}</p>
        <h1 class="tich-h3 tich-mt-2">{{ $submission->typeLabel() }}</h1>
        <p class="tich-mt-4"><x-status-badge :status="$submission->status" /></p>

        <dl class="tich-mt-6" style="display:grid; gap:0.75rem;">
            <div>
                <dt class="tich-caption">Proposed file</dt>
                <dd class="tich-text">{{ $submission->original_filename }}</dd>
            </div>
            @if ($submission->student_notes)
                <div>
                    <dt class="tich-caption">Student notes</dt>
                    <dd class="tich-text" style="white-space:pre-wrap;">{{ $submission->student_notes }}</dd>
                </div>
            @endif
            @if ($submission->applicationDocument)
                <div>
                    <dt class="tich-caption">Current file on record</dt>
                    <dd class="tich-text">{{ $submission->applicationDocument->original_filename }}</dd>
                </div>
            @endif
        </dl>

        <p class="tich-mt-6">
            <a href="{{ route('departments.academics.document-submissions.preview', array_merge($hub, ['submission' => $submission->id])) }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">Preview proposed file</a>
        </p>

        @if ($submission->status === 'pending')
            <form method="POST" action="{{ route('departments.academics.document-submissions.approve', array_merge($hub, ['submission' => $submission->id])) }}" class="tich-form-stack tich-mt-8">
                @csrf
                <label class="tich-label" for="approve_notes">Approval notes (optional)</label>
                <textarea id="approve_notes" name="reviewer_notes" rows="2" class="tich-input"></textarea>
                <button type="submit" class="tich-btn tich-btn-primary">Approve change</button>
            </form>

            <form method="POST" action="{{ route('departments.academics.document-submissions.reject', array_merge($hub, ['submission' => $submission->id])) }}" class="tich-form-stack tich-mt-6">
                @csrf
                <label class="tich-label" for="reject_notes">Rejection reason</label>
                <textarea id="reject_notes" name="reviewer_notes" rows="3" class="tich-input" required></textarea>
                <button type="submit" class="tich-btn tich-btn-secondary">Reject change</button>
            </form>
        @else
            <p class="tich-mt-6 tich-caption">Reviewed {{ $submission->reviewed_at?->format('d M Y H:i') ?? '-' }}</p>
            @if ($submission->reviewer_notes)
                <p class="tich-text" style="white-space:pre-wrap;">{{ $submission->reviewer_notes }}</p>
            @endif
        @endif
    </article>
@endsection
