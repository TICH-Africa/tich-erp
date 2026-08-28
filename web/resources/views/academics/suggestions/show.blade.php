@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = \App\Support\AcademicsRouteParams::for([
            'learning_department' => request()->integer('learning_department') ?: null,
        ]);
    @endphp

    <div class="tich-mb-6">
        <a href="{{ route('departments.academics.suggestions.index', $hub) }}" class="tich-btn tich-btn-ghost">← Back to suggestion box</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">{{ $suggestion->categoryLabel() }}</p>
            <h1 class="tich-leave-hero__title">{{ $suggestion->subject ?: ('Submission #'.$suggestion->id) }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge tich-badge--{{ $suggestion->statusBadge() }}">{{ $suggestion->statusLabel() }}</span>
                <span class="tich-caption">Submitted {{ $suggestion->created_at?->format('d M Y H:i') }}</span>
            </div>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mb-8" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Student</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Name</span>
                    <span class="tich-kv-grid__value">{{ $suggestion->student?->fullName() ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Registration</span>
                    <span class="tich-kv-grid__value">{{ $suggestion->student?->registration_number ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Programme</span>
                    <span class="tich-kv-grid__value">
                        {{ $suggestion->student?->program?->program_code ?? '-' }}
                        @if ($suggestion->student?->program?->program_name)
                            · {{ $suggestion->student->program->program_name }}
                        @endif
                    </span>
                </div>
            </div>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Review</h2>
            <form method="POST" action="{{ route('departments.academics.suggestions.update', array_merge($hub, ['suggestion' => $suggestion->id])) }}" class="tich-form-stack tich-mt-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="status" class="tich-label">Status</label>
                    <select id="status" name="status" class="tich-select" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $suggestion->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="response" class="tich-label">Response to student</label>
                    <textarea id="response" name="response" rows="5" class="tich-input" placeholder="Optional reply visible to the student">{{ old('response', $suggestion->response) }}</textarea>
                </div>
                @if ($suggestion->reviewer)
                    <p class="tich-caption">Last reviewed by {{ $suggestion->reviewer->displayName() ?? $suggestion->reviewer->name }} @if ($suggestion->resolved_at) · {{ $suggestion->resolved_at->format('d M Y') }} @endif</p>
                @endif
                <div>
                    <button type="submit" class="tich-btn tich-btn-primary">Save response</button>
                </div>
            </form>
        </article>
    </div>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Message</h2>
        <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $suggestion->body }}</p>
    </article>
@endsection
