@extends('layouts.qa')

@section('title', 'QA Command Center')

@section('qa-content')
    <x-page-toolbar title="QA Command Center" meta="Quality plans, assessment sheets, capacity building, and compliance oversight" />

    <div class="tich-grid tich-grid--4 tich-mt-8">
        <article class="tich-card"><p class="tich-caption">Draft sheets</p><p class="tich-h2 tich-mt-2">{{ $stats['draft'] }}</p></article>
        <article class="tich-card"><p class="tich-caption">In the field</p><p class="tich-h2 tich-mt-2">{{ $stats['active'] }}</p></article>
        <article class="tich-card"><p class="tich-caption">Compiled reports</p><p class="tich-h2 tich-mt-2">{{ $stats['compiled'] }}</p></article>
        <article class="tich-card tich-card--highlight"><p class="tich-caption">Open corrective actions</p><p class="tich-h2 tich-mt-2">{{ $stats['corrective'] }}</p></article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8">
        <article class="tich-card">
            <div class="tich-flex" style="justify-content:space-between;align-items:center;gap:1rem;">
                <h2 class="tich-h3">Assessment sheets</h2>
                <a href="{{ route('qa.assessments.create') }}" class="tich-btn tich-btn-primary">Build sheet</a>
            </div>
            <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
                @forelse ($openPlans as $plan)
                    <li class="tich-text tich-mt-2">
                        <a href="{{ route('qa.assessments.show', $plan) }}" class="tich-link">{{ $plan->plan_name }}</a>
                        <span class="tich-caption">· {{ str_replace('_', ' ', $plan->status) }}</span>
                    </li>
                @empty
                    <li class="tich-text">No open assessment sheets yet.</li>
                @endforelse
            </ul>
            <a href="{{ route('qa.assessments.index') }}" class="tich-btn tich-btn-secondary tich-mt-4">View all</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Corrective actions</h2>
            <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
                @forelse ($openActions as $action)
                    <li class="tich-text tich-mt-2">
                        <strong>{{ $action->department?->dept_name }}</strong>
                        <span class="tich-caption">due {{ $action->resolution_deadline?->format('d M Y') }}</span>
                        <p class="tich-caption">{{ \Illuminate\Support\Str::limit($action->flagged_reason, 120) }}</p>
                    </li>
                @empty
                    <li class="tich-text">No open corrective actions.</li>
                @endforelse
            </ul>
            <a href="{{ route('qa.corrective-actions.index') }}" class="tich-btn tich-btn-secondary tich-mt-4">Manage actions</a>
        </article>
    </div>

    @if ($pendingTasks > 0)
        <div class="tich-alert tich-alert--info tich-mt-8">
            You have department QA tasks waiting.
            <a href="{{ route('qa.tasks.index') }}" class="tich-link">Open my department tasks</a>
        </div>
    @endif
@endsection
