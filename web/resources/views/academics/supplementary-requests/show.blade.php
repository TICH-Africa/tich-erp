@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = \App\Support\AcademicsRouteParams::for(['learning_department' => request()->integer('learning_department') ?: null]);
        $canReview = in_array($supplementaryRequest->application_status, ['pending_review', 'pending_fee', 'on_hold'], true);
    @endphp

    <div class="tich-mb-6">
        <a href="{{ route('departments.academics.supplementary-requests.index', $hub) }}" class="tich-btn tich-btn-ghost">← Back</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Supplementary exam</p>
            <h1 class="tich-leave-hero__title">{{ $supplementaryRequest->student?->fullName() ?? 'Student' }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge">{{ $supplementaryRequest->statusLabel() }}</span>
                <span class="tich-caption">{{ $supplementaryRequest->student?->registration_number }}</span>
            </div>
        </div>
    </section>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Request</h2>
        <dl class="tich-mt-4" style="display:grid; grid-template-columns:9rem 1fr; gap:0.5rem 1rem;">
            <dt class="tich-caption">Unit</dt>
            <dd>{{ $supplementaryRequest->unit?->unit_code }} - {{ $supplementaryRequest->unit?->unit_name }}</dd>
            <dt class="tich-caption">Semester</dt>
            <dd>{{ $supplementaryRequest->semester?->semester_label ?? '-' }}</dd>
            <dt class="tich-caption">Type</dt>
            <dd>{{ $supplementaryRequest->typeLabel() }}</dd>
            <dt class="tich-caption">Notes</dt>
            <dd style="white-space:pre-wrap;">{{ $supplementaryRequest->student_notes ?: '-' }}</dd>
        </dl>
    </article>

    @include('academics.partials.supporting-docs-viewer', [
        'documents' => $supplementaryRequest->supporting_docs ?? [],
        'viewRoute' => 'departments.academics.supplementary-requests.attachments.view',
        'downloadRoute' => 'departments.academics.supplementary-requests.attachments.download',
        'routeParams' => array_merge($hub, ['supplementaryExamRequest' => $supplementaryRequest->id]),
        'title' => 'Supporting documents',
        'subtitle' => 'Review uploaded evidence for this supplementary exam request.',
    ])

    @if ($canReview)
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Review</h2>
            <div class="tich-mt-4">
                <label for="exam-review-notes" class="tich-label">Notes</label>
                <textarea id="exam-review-notes" rows="3" class="tich-input" placeholder="Optional for approve; required for hold or reject"></textarea>
            </div>
            <div class="tich-flex-wrap tich-mt-6" style="gap: 0.75rem; align-items: center;">
                <form method="POST" action="{{ route('departments.academics.supplementary-requests.approve', array_merge($hub, ['supplementaryExamRequest' => $supplementaryRequest->id])) }}" class="exam-review-action" data-require-notes="0">
                    @csrf
                    <input type="hidden" name="reviewed_notes" value="">
                    <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
                </form>
                <form method="POST" action="{{ route('departments.academics.supplementary-requests.hold', array_merge($hub, ['supplementaryExamRequest' => $supplementaryRequest->id])) }}" class="exam-review-action" data-require-notes="1">
                    @csrf
                    <input type="hidden" name="reviewed_notes" value="">
                    <button type="submit" class="tich-btn tich-btn-secondary">Put on hold</button>
                </form>
                <form method="POST" action="{{ route('departments.academics.supplementary-requests.reject', array_merge($hub, ['supplementaryExamRequest' => $supplementaryRequest->id])) }}" class="exam-review-action" data-require-notes="1">
                    @csrf
                    <input type="hidden" name="reviewed_notes" value="">
                    <button type="submit" class="tich-btn tich-btn-secondary">Reject</button>
                </form>
            </div>
        </article>
        <script>
            (function () {
                var notes = document.getElementById('exam-review-notes');
                document.querySelectorAll('.exam-review-action').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        var value = notes ? notes.value.trim() : '';
                        var input = form.querySelector('[name="reviewed_notes"]');
                        if (input) input.value = value;
                        if (form.dataset.requireNotes === '1' && !value) {
                            event.preventDefault();
                            if (notes) notes.focus();
                            alert('Notes are required for this action.');
                        }
                    });
                });
            })();
        </script>
    @else
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Decision</h2>
            <p class="tich-mt-4">{{ $supplementaryRequest->statusLabel() }}</p>
            <p class="tich-caption">{{ $supplementaryRequest->reviewed_notes ?: '-' }}</p>
        </article>
    @endif
@endsection
