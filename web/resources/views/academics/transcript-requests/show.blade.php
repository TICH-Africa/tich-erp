@extends('layouts.academics')

@section('academics-content')
    @php $hub = \App\Support\AcademicsRouteParams::for(['learning_department' => request()->integer('learning_department') ?: null]); @endphp

    <div class="tich-mb-6">
        <a href="{{ route('departments.academics.transcript-requests.index', $hub) }}" class="tich-btn tich-btn-ghost">← Back</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Transcript request</p>
            <h1 class="tich-leave-hero__title">{{ $transcriptRequest->student?->fullName() ?? 'Student' }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge">{{ ucfirst($transcriptRequest->status) }}</span>
                <span class="tich-caption">{{ $transcriptRequest->student?->registration_number }}</span>
            </div>
        </div>
    </section>

    <div class="tich-grid tich-grid--2" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Details</h2>
            <dl class="tich-mt-4" style="display:grid; grid-template-columns:9rem 1fr; gap:0.5rem 1rem;">
                <dt class="tich-caption">Delivery</dt>
                <dd>{{ ucfirst($transcriptRequest->delivery_method) }}</dd>
                <dt class="tich-caption">Student notes</dt>
                <dd>{{ $transcriptRequest->student_notes ?: '-' }}</dd>
                <dt class="tich-caption">Submitted</dt>
                <dd>{{ $transcriptRequest->created_at?->format('d M Y H:i') }}</dd>
            </dl>
            @if ($transcriptRequest->student)
                <p class="tich-mt-4">
                    <a href="{{ route('portal.transcript.print') }}" class="tich-link tich-caption" style="pointer-events:none; opacity:0.5;">Portal print is student-scoped</a>
                    ·
                    <a href="{{ route('sis.students.show', $transcriptRequest->student) }}" class="tich-link">Open SIS record</a>
                </p>
            @endif
        </article>

        @if (in_array($transcriptRequest->status, ['pending', 'processing'], true))
            <article class="tich-card">
                <h2 class="tich-h3">Action</h2>
                <form method="POST" action="{{ route('departments.academics.transcript-requests.issue', array_merge($hub, ['transcriptRequest' => $transcriptRequest->id])) }}" class="tich-form-stack tich-mt-4">
                    @csrf
                    <div>
                        <label for="registrar_notes" class="tich-label">Registrar notes</label>
                        <textarea id="registrar_notes" name="registrar_notes" rows="3" class="tich-input">{{ old('registrar_notes') }}</textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">Mark issued</button>
                </form>
                <form method="POST" action="{{ route('departments.academics.transcript-requests.reject', array_merge($hub, ['transcriptRequest' => $transcriptRequest->id])) }}" class="tich-form-stack tich-mt-6">
                    @csrf
                    <div>
                        <label for="reject_notes" class="tich-label">Rejection notes</label>
                        <textarea id="reject_notes" name="registrar_notes" rows="3" class="tich-input" required>{{ old('registrar_notes') }}</textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">Reject</button>
                </form>
            </article>
        @else
            <article class="tich-card">
                <h2 class="tich-h3">Decision</h2>
                <p class="tich-mt-4">{{ ucfirst($transcriptRequest->status) }}</p>
                <p class="tich-caption">{{ $transcriptRequest->registrar_notes ?: '-' }}</p>
            </article>
        @endif
    </div>
@endsection
