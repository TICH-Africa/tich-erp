@extends('layouts.qa')

@section('title', 'Lesson plan '.$plan->plan_number)

@section('qa-content')
    @php
        $allocation = $plan->allocation;
        $unit = $allocation?->unit;
    @endphp

    <x-page-toolbar
        :title="$plan->plan_number"
        :meta="($unit?->unit_code ?? '').' · '.($unit?->unit_name ?? '').' · '.($plan->preparedByStaff?->fullName() ?? '')"
    >
        <x-slot:actions>
            <a href="{{ route('qa.lesson-plans.index') }}" class="tich-btn tich-btn-ghost">All lesson plans</a>
            @if ($plan->isFormBased())
                <a href="{{ route('lesson-plans.pdf', $plan->id) }}" class="tich-btn tich-btn-secondary">Download PDF</a>
            @elseif ($plan->uploaded_file_path)
                <a href="{{ route('lesson-plans.upload.download', $plan->id) }}" class="tich-btn tich-btn-secondary">Download upload</a>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start;gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Plan summary</h2>
            <dl class="tich-dl tich-mt-4">
                <dt>Department</dt><dd>{{ $unit?->department?->dept_name ?: '—' }}</dd>
                <dt>Semester</dt><dd>{{ $allocation?->semester?->semester_label ?? '—' }}</dd>
                <dt>Planned date</dt><dd>{{ $plan->planned_date?->format('d M Y') }}</dd>
                <dt>Week / hours</dt><dd>{{ $plan->week_number }} · {{ $plan->contact_hours }} hrs</dd>
                <dt>HOD status</dt><dd><x-status-badge :status="$plan->status" /></dd>
                @if ($plan->hod_comments)
                    <dt>HOD comments</dt><dd style="white-space:pre-wrap;">{{ $plan->hod_comments }}</dd>
                @endif
                <dt>Lesson objectives</dt><dd style="white-space:pre-wrap;">{{ $plan->lesson_objectives }}</dd>
                <dt>Topics</dt><dd style="white-space:pre-wrap;">{{ $plan->topics_covered ?: '—' }}</dd>
                <dt>Teaching methods</dt><dd>{{ $plan->teaching_methods ?: '—' }}</dd>
                <dt>Resources</dt><dd>{{ $plan->resources_required ?: '—' }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">QA acknowledgement</h2>
            <p class="tich-caption tich-mt-2">Runs in parallel with HOD approval — does not block or replace the HOD decision.</p>

            @if ($plan->isQaAcknowledged())
                <dl class="tich-dl tich-mt-4">
                    <dt>Acknowledged by</dt>
                    <dd>{{ $plan->qaAcknowledgedByStaff?->fullName() ?? '—' }}</dd>
                    <dt>Acknowledged at</dt>
                    <dd>{{ $plan->qa_acknowledged_at?->format('d M Y H:i') }}</dd>
                    <dt>Comments</dt>
                    <dd style="white-space:pre-wrap;">{{ $plan->qa_comments ?: '—' }}</dd>
                </dl>
            @elseif ($canAcknowledge)
                <form method="POST" action="{{ route('qa.lesson-plans.acknowledge', $plan) }}" class="tich-mt-4">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label" for="qa_comments">Comments</label>
                        <textarea id="qa_comments" name="qa_comments" class="tich-input" rows="5" required placeholder="QA comments / observations">{{ old('qa_comments') }}</textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Acknowledge &amp; comment</button>
                </form>
            @else
                <p class="tich-text tich-mt-4">This plan is not available for acknowledgement.</p>
            @endif

            @if ($plan->approvals->isNotEmpty())
                <h3 class="tich-h3 tich-mt-6">Decision history</h3>
                <ul class="tich-mt-2" style="padding-left:1.1rem;">
                    @foreach ($plan->approvals as $decision)
                        <li class="tich-text tich-mt-2">
                            <strong>{{ strtoupper($decision->approval_level) }}</strong>
                            · {{ str_replace('_', ' ', $decision->decision) }}
                            · {{ $decision->approver?->fullName() ?? '—' }}
                            · {{ $decision->decided_at?->format('d M Y H:i') }}
                            @if ($decision->comments)
                                <p class="tich-caption">{{ $decision->comments }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </div>
@endsection
