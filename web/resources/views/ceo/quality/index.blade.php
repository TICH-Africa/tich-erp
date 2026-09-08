@extends('layouts.ceo')

@section('title', 'Quality reports')

@section('ceo-content')
    <x-page-toolbar title="Quality Level Reports" meta="Compiled QA compliance scores and corrective action flags" />

    <div class="tich-grid tich-grid--2 tich-mt-8">
        <article class="tich-card">
            <h2 class="tich-h3">Below-threshold scores</h2>
            <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
                @forelse ($failing as $score)
                    <li class="tich-text tich-mt-2">
                        <strong>{{ $score->department?->dept_name }}</strong>
                        - {{ number_format((float) $score->weighted_score, 1) }}%
                        <p class="tich-caption">{{ $score->plan?->plan_name }}</p>
                    </li>
                @empty
                    <li class="tich-text">No failing compliance scores right now.</li>
                @endforelse
            </ul>
        </article>
        <article class="tich-card">
            <h2 class="tich-h3">Open corrective actions</h2>
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
        </article>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Assessment reports</h2>
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Assessment</th>
                    <th>Status</th>
                    <th>Compiled</th>
                    <th>Departments</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td>{{ $plan->plan_name }}</td>
                        <td><span class="tich-badge">{{ str_replace('_', ' ', $plan->status) }}</span></td>
                        <td>{{ $plan->compiled_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ $plan->complianceScores->count() }}</td>
                        <td><a href="{{ route('ceo.quality.show', $plan) }}" class="tich-btn tich-btn-secondary">Open</a></td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No quality reports yet', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
        @if ($plans->hasPages())
            <div class="tich-mt-4">{{ $plans->links() }}</div>
        @endif
    </div>
@endsection
