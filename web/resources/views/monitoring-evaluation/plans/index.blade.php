@extends('layouts.monitoring-evaluation')

@section('title', 'Technical plans')

@section('monitoring-evaluation-content')
    <x-page-toolbar title="Technical plans" meta="Concurrent intake from department budget submissions">
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.plans.index', ['status' => 'me_review']) }}" class="tich-btn tich-btn-secondary">In review</a>
            <a href="{{ route('monitoring_evaluation.plans.index', ['status' => 'baseline_locked']) }}" class="tich-btn tich-btn-ghost">Baselines</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <div class="tich-card tich-table-panel tich-mt-6">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Title</th>
                    <th>Outputs</th>
                    <th>Budget status</th>
                    <th>Plan status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $plan)
                    <tr>
                        <td>{{ $plan->department?->dept_name }}</td>
                        <td>{{ $plan->title }}</td>
                        <td>{{ $plan->outputs->count() }}</td>
                        <td>{{ $plan->budgetRequest?->status ?? '—' }}</td>
                        <td>{{ str_replace('_', ' ', $plan->status) }}</td>
                        <td><a href="{{ route('monitoring_evaluation.plans.show', $plan) }}" class="tich-link">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">No technical plans yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="tich-mt-4">{{ $items->links() }}</div>
    </div>
@endsection
