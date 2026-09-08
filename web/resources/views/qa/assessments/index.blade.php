@extends('layouts.qa')

@section('title', 'Assessment sheets')

@section('qa-content')
    <x-page-toolbar title="Assessment sheets" meta="Build, dispatch, track, and compile departmental evaluation forms">
        <x-slot:actions>
            <a href="{{ route('qa.assessments.create') }}" class="tich-btn tich-btn-primary">Build new sheet</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Sheet</th>
                    <th>Period</th>
                    <th>Departments</th>
                    <th>Criteria</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td>
                            <a href="{{ route('qa.assessments.show', $plan) }}" class="tich-link">{{ $plan->plan_name }}</a>
                            @if ($plan->due_at)
                                <p class="tich-caption">Due {{ $plan->due_at->format('d M Y') }}</p>
                            @endif
                        </td>
                        <td>{{ $plan->period_start?->format('d M Y') }} – {{ $plan->period_end?->format('d M Y') }}</td>
                        <td>{{ count($plan->targetDepartmentIds()) }}</td>
                        <td>{{ $plan->checklists_count }}</td>
                        <td><span class="tich-badge">{{ str_replace('_', ' ', $plan->status) }}</span></td>
                        <td><a href="{{ route('qa.assessments.show', $plan) }}" class="tich-btn tich-btn-secondary">Open</a></td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No assessment sheets yet', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
        @if ($plans->hasPages())
            <div class="tich-mt-4">{{ $plans->links() }}</div>
        @endif
    </div>
@endsection
