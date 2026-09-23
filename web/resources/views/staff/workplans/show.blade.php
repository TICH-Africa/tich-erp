@extends('layouts.staff')

@section('staff-content')
    <p class="tich-caption tich-mb-2"><a href="{{ route('staff.workplans.index') }}" class="tich-link">← Semester workplans</a></p>

    <x-page-toolbar :title="$workplan->workplan_number" :meta="$workplan->title.' · '.($workplan->semester?->displayLabel() ?? '')">
        <x-slot:actions>
            @if ($workplan->isEditableByHod())
                <a href="{{ route('staff.workplans.edit', $workplan) }}" class="tich-btn tich-btn-secondary">Edit</a>
            @endif
            @if ($workplan->isSubmittable() && $workplan->status !== 'pending')
                <form method="POST" action="{{ route('staff.workplans.submit', $workplan) }}">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">Submit for review</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-mt-4">
        <x-status-badge :status="$workplan->status" />
        @if ($workplan->submitted_at)
            <span class="tich-caption tich-ml-2">Submitted {{ $workplan->submitted_at->format('d M Y H:i') }}</span>
        @endif
    </div>

    <div class="tich-mt-6">
        @include('staff.workplans.partials.approval-status', ['summary' => $summary])
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Plan summary</h2>
            <dl class="tich-dl tich-mt-4">
                <dt>Department</dt><dd>{{ $workplan->department?->dept_name }}</dd>
                <dt>Semester</dt><dd>{{ $workplan->semester?->displayLabel() }}</dd>
                <dt>Prepared by</dt><dd>{{ $workplan->preparedByStaff?->fullName() }}</dd>
                <dt>Objectives</dt><dd>{!! nl2br(e($workplan->objectives ?: '—')) !!}</dd>
                <dt>Resources</dt><dd>{!! nl2br(e($workplan->resources ?: '—')) !!}</dd>
                <dt>KPIs</dt><dd>{!! nl2br(e($workplan->kpis ?: '—')) !!}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Activities</h2>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Timeline</th>
                            <th>KPI</th>
                            <th>Resources</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($workplan->activities as $activity)
                            <tr>
                                <td>{{ $activity->activity }}</td>
                                <td>
                                    {{ $activity->timeline_start?->format('d M Y') ?? '—' }}
                                    –
                                    {{ $activity->timeline_end?->format('d M Y') ?? '—' }}
                                </td>
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
    </div>
@endsection
