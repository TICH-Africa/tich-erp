@extends('layouts.qa')

@section('title', $plan->plan_name.' · '.$department->dept_name)

@section('qa-content')
    <x-page-toolbar title="{{ $department->dept_name }} responses" meta="{{ $plan->plan_name }}">
        <x-slot:actions>
            <a href="{{ route('qa.assessments.show', $plan) }}" class="tich-btn tich-btn-ghost">Back to sheet</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-8">
        <article class="tich-card">
            <p class="tich-caption">Department</p>
            <p class="tich-text tich-mt-2">{{ $department->dept_name }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Items submitted</p>
            <p class="tich-h2 tich-mt-2">{{ $compliance?->items_submitted ?? $submissions->whereIn('submission_status', ['submitted', 'verified', 'approved'])->count() }} / {{ $compliance?->total_items ?? $plan->checklists->count() }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Weighted score</p>
            <p class="tich-h2 tich-mt-2">{{ $compliance ? number_format((float) $compliance->weighted_score, 1).'%' : '—' }}</p>
            @if ($compliance)
                <p class="tich-caption tich-mt-2">{{ str_replace('_', ' ', $compliance->pass_fail_status) }}</p>
            @endif
        </article>
    </div>

    @forelse ($plan->checklists as $item)
        @php $submission = $submissions->get($item->id); @endphp
        <article class="tich-card tich-mt-4">
            <div class="tich-flex tich-flex--between" style="flex-wrap:wrap;gap:0.75rem;align-items:flex-start;">
                <div>
                    <h2 class="tich-h3">{{ $item->display_order ?: $loop->iteration }}. {{ $item->checklist_item_text }}</h2>
                    <p class="tich-caption tich-mt-1">
                        @if ($item->item_category){{ $item->item_category }} · @endif
                        Max {{ (int) $item->max_score }} · Weight {{ $item->weight }}
                    </p>
                </div>
                <x-status-badge :status="$submission?->submission_status ?? 'not_started'" />
            </div>

            @if ($submission)
                <p class="tich-text tich-mt-4">{{ $submission->submission_text ?: '—' }}</p>
                <p class="tich-caption tich-mt-2">Score: {{ $submission->score !== null ? (int) $submission->score : '—' }}</p>
                @if ($submission->submittedByStaff)
                    <p class="tich-caption">Submitted by {{ $submission->submittedByStaff->fullName() }}{{ $submission->submitted_at ? ' · '.$submission->submitted_at->format('d M Y H:i') : '' }}</p>
                @endif
                @if ($submission->evidence->isNotEmpty())
                    <div class="tich-mt-3">
                        <p class="tich-caption">Attachments</p>
                        <ul style="margin:0.35rem 0 0;padding-left:1.1rem;">
                            @foreach ($submission->evidence as $file)
                                <li>
                                    <a href="{{ route('qa.evidence.viewer', $file) }}" class="tich-link" target="_blank" rel="noopener">{{ $file->description ?: 'Attachment' }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @else
                <p class="tich-text tich-mt-4">No response yet for this criterion.</p>
            @endif
        </article>
    @empty
        <div class="tich-alert tich-alert--info tich-mt-8">This sheet has no evaluation criteria.</div>
    @endforelse
@endsection
