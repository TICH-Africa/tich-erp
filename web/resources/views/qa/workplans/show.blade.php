@extends('layouts.qa')

@section('title', $workplan->workplan_number)

@section('qa-content')
    <p class="tich-caption tich-mb-2"><a href="{{ route('qa.workplans.index') }}" class="tich-link">← Semester workplans</a></p>

    <x-page-toolbar
        :title="$workplan->workplan_number"
        :meta="($workplan->department?->dept_name ?? '').' · '.($workplan->title ?? '')"
    />

    <div class="tich-mt-4">
        <x-status-badge :status="$workplan->status" />
    </div>

    <div class="tich-mt-6">
        @include('staff.workplans.partials.approval-status', ['summary' => $summary])
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Plan summary</h2>
            <dl class="tich-dl tich-mt-4">
                <dt>Semester</dt><dd>{{ $workplan->semester?->displayLabel() }}</dd>
                <dt>Prepared by</dt><dd>{{ $workplan->preparedByStaff?->fullName() }}</dd>
                <dt>Submitted</dt><dd>{{ $workplan->submitted_at?->format('d M Y H:i') ?? '—' }}</dd>
                <dt>Objectives</dt><dd>{!! nl2br(e($workplan->objectives ?: '—')) !!}</dd>
                <dt>Resources</dt><dd>{!! nl2br(e($workplan->resources ?: '—')) !!}</dd>
                <dt>KPIs</dt><dd>{!! nl2br(e($workplan->kpis ?: '—')) !!}</dd>
            </dl>

            <h3 class="tich-h3 tich-mt-6">Activities</h3>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr><th>Activity</th><th>Timeline</th><th>KPI</th><th>Resources</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($workplan->activities as $activity)
                            <tr>
                                <td>{{ $activity->activity }}</td>
                                <td>{{ $activity->timeline_start?->format('d M Y') ?? '—' }} – {{ $activity->timeline_end?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $activity->kpi ?: '—' }}</td>
                                <td>{{ $activity->resources ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="tich-text">No activities recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        @if ($canAct)
            @include('staff.workplans.partials.review-actions', [
                'approveRoute' => route('qa.workplans.approve', $workplan),
                'rejectRoute' => route('qa.workplans.reject', $workplan),
                'changesRoute' => route('qa.workplans.request-changes', $workplan),
            ])
        @else
            <article class="tich-card">
                <h2 class="tich-h3">Review actions</h2>
                <p class="tich-text tich-mt-4">This workplan is not awaiting QA action.</p>
            </article>
        @endif
    </div>
@endsection
