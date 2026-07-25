@extends('layouts.academics')

@section('academics-content')
    @php
        $allocation = $plan->allocation;
        $unit = $allocation?->unit;
    @endphp

    <header class="tich-dept-header">
        <p class="tich-caption"><a href="{{ route('departments.academics.lesson-plans.index', $hubParams) }}" class="tich-link">← Lesson plan approval</a></p>
        <h1 class="tich-h1 tich-dept-header__title">{{ $plan->plan_number }}</h1>
        <p class="tich-text">{{ $unit?->unit_code }} · {{ $unit?->unit_name }} · {{ $plan->preparedByStaff?->fullName() }}</p>
    </header>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Lesson plan content</h2>

            @if ($canReview)
                <form method="POST" action="{{ route('departments.academics.lesson-plans.update', array_merge($hubParams, ['plan' => $plan->id])) }}" class="tich-mt-4">
                    @csrf
                    @method('PUT')
                    @include('academics.lesson-plans.partials.form-fields', ['plan' => $plan])
                    <button type="submit" class="tich-btn tich-btn-secondary tich-mt-4">Save HOD edits</button>
                </form>
            @else
                <dl class="tich-dl tich-mt-4">
                    <dt>Planned date</dt><dd>{{ $plan->planned_date?->format('d M Y') }}</dd>
                    <dt>Week</dt><dd>{{ $plan->week_number }}</dd>
                    <dt>Contact hours</dt><dd>{{ $plan->contact_hours }}</dd>
                    <dt>Lesson objectives</dt><dd style="white-space:pre-wrap;">{{ $plan->lesson_objectives }}</dd>
                    <dt>Topics covered</dt><dd style="white-space:pre-wrap;">{{ $plan->topics_covered ?: '—' }}</dd>
                    <dt>Core competencies</dt><dd style="white-space:pre-wrap;">{{ $plan->competencies_targeted ?: '—' }}</dd>
                    <dt>Teaching methods</dt><dd>{{ $plan->teaching_methods ?: '—' }}</dd>
                    <dt>Resources required</dt><dd>{{ $plan->resources_required ?: '—' }}</dd>
                    <dt>Status</dt><dd>{{ ucfirst($plan->status) }}</dd>
                    @if ($plan->hod_comments)
                        <dt>HOD comments</dt><dd style="white-space:pre-wrap;">{{ $plan->hod_comments }}</dd>
                    @endif
                </dl>
            @endif
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Approval actions</h2>
            <p class="tich-caption tich-mt-2">Status: <strong>{{ ucfirst($plan->status) }}</strong></p>

            @if ($canReview)
                <form method="POST" action="{{ route('departments.academics.lesson-plans.approve', array_merge($hubParams, ['plan' => $plan->id])) }}" class="tich-mt-4">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Approval comments (optional)</label>
                        <textarea name="hod_comments" class="tich-input" rows="3" placeholder="Optional note to the tutor">{{ old('hod_comments', $plan->hod_comments) }}</textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">Approve &amp; clear timetable</button>
                </form>

                <form method="POST" action="{{ route('departments.academics.lesson-plans.request-modification', array_merge($hubParams, ['plan' => $plan->id])) }}" class="tich-mt-6" style="border-top:1px solid var(--tich-neutral-border); padding-top:1.25rem;">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Request revision</label>
                        <textarea name="hod_comments" class="tich-input" rows="3" required placeholder="Explain what the tutor should change"></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">Request modification</button>
                </form>

                <form method="POST" action="{{ route('departments.academics.lesson-plans.reject', array_merge($hubParams, ['plan' => $plan->id])) }}" class="tich-mt-4">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Reject plan</label>
                        <textarea name="hod_comments" class="tich-input" rows="3" required placeholder="Reason for rejection"></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary" style="color:var(--tich-danger, #b91c1c);">Reject</button>
                </form>
            @else
                <p class="tich-text tich-mt-4">This plan is read-only. Only the department HOD can approve or reject submitted plans.</p>
            @endif

            @if ($plan->approvals->isNotEmpty())
                <h3 class="tich-h3 tich-mt-6">Decision history</h3>
                <ul class="tich-mt-2" style="list-style:none; padding:0;">
                    @foreach ($plan->approvals as $entry)
                        <li class="tich-text tich-mt-2" style="border-left:3px solid var(--tich-neutral-border); padding-left:0.75rem;">
                            <strong>{{ ucfirst(str_replace('_', ' ', $entry->decision)) }}</strong>
                            · {{ $entry->approver?->fullName() }}
                            · {{ $entry->decided_at?->format('d M Y H:i') }}
                            @if ($entry->comments)
                                <br><span class="tich-caption">{{ $entry->comments }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </div>
@endsection
