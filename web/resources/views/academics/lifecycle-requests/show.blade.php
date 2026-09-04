@extends('layouts.academics')

@section('academics-content')
    @php $hub = \App\Support\AcademicsRouteParams::for(['learning_department' => request()->integer('learning_department') ?: null]); @endphp

    <div class="tich-mb-6">
        <a href="{{ route('departments.academics.lifecycle-requests.index', $hub) }}" class="tich-btn tich-btn-ghost">← Back</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Deferment</p>
            <h1 class="tich-leave-hero__title">{{ $lifecycleRequest->student?->fullName() ?? 'Student' }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge">{{ $lifecycleRequest->statusLabel() }}</span>
                <span class="tich-caption">{{ $lifecycleRequest->student?->registration_number }}</span>
            </div>
        </div>
    </section>

    <div class="tich-grid tich-grid--2" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Request</h2>
            <dl class="tich-mt-4" style="display:grid; grid-template-columns:9rem 1fr; gap:0.5rem 1rem;">
                <dt class="tich-caption">Period</dt>
                <dd>{{ $lifecycleRequest->deferment_months ? $lifecycleRequest->deferment_months.' month(s)' : '-' }}</dd>
                <dt class="tich-caption">Reason</dt>
                <dd style="white-space:pre-wrap;">{{ $lifecycleRequest->reason ?: '-' }}</dd>
                <dt class="tich-caption">Attachments</dt>
                <dd>
                    @forelse (($lifecycleRequest->attachments ?? []) as $i => $file)
                        <div>
                            <a class="tich-link" href="{{ route('departments.academics.lifecycle-requests.attachment', array_merge($hub, ['lifecycleRequest' => $lifecycleRequest->id, 'index' => $i])) }}">
                                {{ $file['original_name'] ?? ('File '.($i + 1)) }}
                            </a>
                        </div>
                    @empty
                        -
                    @endforelse
                </dd>
                <dt class="tich-caption">Registrar</dt>
                <dd>
                    {{ \App\Models\StudentLifecycleRequest::REVIEW_STATUSES[$lifecycleRequest->registrar_status ?? 'pending'] ?? ucfirst($lifecycleRequest->registrar_status ?? 'pending') }}
                    @if ($lifecycleRequest->registrar_notes)
                        <p class="tich-caption" style="white-space:pre-wrap;">{{ $lifecycleRequest->registrar_notes }}</p>
                    @endif
                </dd>
                <dt class="tich-caption">Dean</dt>
                <dd>
                    {{ \App\Models\StudentLifecycleRequest::REVIEW_STATUSES[$lifecycleRequest->dean_status ?? 'pending'] ?? ucfirst($lifecycleRequest->dean_status ?? 'pending') }}
                    @if ($lifecycleRequest->dean_notes)
                        <p class="tich-caption" style="white-space:pre-wrap;">{{ $lifecycleRequest->dean_notes }}</p>
                    @endif
                </dd>
            </dl>
        </article>

        <div style="display:grid; gap:1rem;">
            @if ($canActAsRegistrar && in_array($lifecycleRequest->registrar_status, ['pending', 'on_hold'], true) && $lifecycleRequest->isOpenForReview())
                <article class="tich-card">
                    <h2 class="tich-h3">Academic Registrar review</h2>
                    @foreach ([
                        ['approve', 'Approve', 'tich-btn-primary', false],
                        ['hold', 'Put on hold', 'tich-btn-secondary', true],
                        ['reject', 'Reject', 'tich-btn-secondary', true],
                    ] as [$action, $label, $btn, $notesRequired])
                        <form method="POST" action="{{ route('departments.academics.lifecycle-requests.'.$action, array_merge($hub, ['lifecycleRequest' => $lifecycleRequest->id])) }}" class="tich-form-stack tich-mt-4">
                            @csrf
                            <input type="hidden" name="review_role" value="registrar">
                            <div>
                                <label class="tich-label">Notes{{ $notesRequired ? '' : ' (optional)' }}</label>
                                <textarea name="reviewer_notes" rows="2" class="tich-input" @required($notesRequired)></textarea>
                            </div>
                            <button type="submit" class="tich-btn {{ $btn }}">{{ $label }}</button>
                        </form>
                    @endforeach
                </article>
            @endif

            @if ($canActAsDean && in_array($lifecycleRequest->dean_status, ['pending', 'on_hold'], true) && $lifecycleRequest->isOpenForReview())
                <article class="tich-card">
                    <h2 class="tich-h3">Dean of Students review</h2>
                    @foreach ([
                        ['approve', 'Approve', 'tich-btn-primary', false],
                        ['hold', 'Put on hold', 'tich-btn-secondary', true],
                        ['reject', 'Reject', 'tich-btn-secondary', true],
                    ] as [$action, $label, $btn, $notesRequired])
                        <form method="POST" action="{{ route('departments.academics.lifecycle-requests.'.$action, array_merge($hub, ['lifecycleRequest' => $lifecycleRequest->id])) }}" class="tich-form-stack tich-mt-4">
                            @csrf
                            <input type="hidden" name="review_role" value="dean">
                            <div>
                                <label class="tich-label">Notes{{ $notesRequired ? '' : ' (optional)' }}</label>
                                <textarea name="reviewer_notes" rows="2" class="tich-input" @required($notesRequired)></textarea>
                            </div>
                            <button type="submit" class="tich-btn {{ $btn }}">{{ $label }}</button>
                        </form>
                    @endforeach
                </article>
            @endif

            @if (! $lifecycleRequest->isOpenForReview())
                <article class="tich-card">
                    <h2 class="tich-h3">Final decision</h2>
                    <p class="tich-mt-4">{{ $lifecycleRequest->statusLabel() }}</p>
                    <p class="tich-caption" style="white-space:pre-wrap;">{{ $lifecycleRequest->reviewer_notes ?: '-' }}</p>
                </article>
            @endif
        </div>
    </div>
@endsection
