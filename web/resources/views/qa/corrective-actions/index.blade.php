@extends('layouts.qa')

@section('title', 'Corrective actions')

@section('qa-content')
    <x-page-toolbar title="Corrective actions" meta="Quality exceptions flagged when department scores fall below threshold" />

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Assessment</th>
                    <th>Score</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($actions as $action)
                    <tr>
                        <td>{{ $action->department?->dept_name }}</td>
                        <td>
                            <a href="{{ route('qa.assessments.show', $action->qa_plan_id) }}" class="tich-link">{{ $action->plan?->plan_name }}</a>
                            <p class="tich-caption">{{ \Illuminate\Support\Str::limit($action->flagged_reason, 100) }}</p>
                        </td>
                        <td>{{ $action->compliance_score_at_flag !== null ? number_format((float) $action->compliance_score_at_flag, 1).'%' : '-' }}</td>
                        <td>{{ $action->resolution_deadline?->format('d M Y') }}</td>
                        <td><x-status-badge :status="$action->status" /></td>
                        <td>
                            @if (in_array($action->status, ['open', 'in_progress', 'overdue'], true))
                                <form method="POST" action="{{ route('qa.corrective-actions.resolve', $action) }}" class="tich-form-stack">
                                    @csrf
                                    <textarea name="resolution_notes" class="tich-input" rows="2" required placeholder="Resolution notes"></textarea>
                                    <button type="submit" class="tich-btn tich-btn-secondary tich-mt-2">Mark resolved</button>
                                </form>
                            @else
                                <span class="tich-caption">{{ $action->resolution_notes }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No corrective actions', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
        @if ($actions->hasPages())
            <div class="tich-mt-4">{{ $actions->links() }}</div>
        @endif
    </div>
@endsection
