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
                    @php
                        $awaitingReview = $plan->status === 'in_progress' && (int) ($plan->awaiting_review_count ?? 0) > 0;
                    @endphp
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
                        <td>
                            <span class="tich-badge tich-badge--with-signal">
                                {{ str_replace('_', ' ', $plan->status) }}
                                @if ($awaitingReview)
                                    <span class="tich-signal-dot" title="Department responses awaiting review" aria-label="Department responses awaiting review"></span>
                                @endif
                            </span>
                        </td>
                        <td>
                            @if ($awaitingReview)
                                <a href="{{ route('qa.assessments.show', $plan) }}#department-responses" class="tich-btn tich-btn-success">Review</a>
                            @else
                                <a href="{{ route('qa.assessments.show', $plan) }}" class="tich-btn tich-btn-secondary">Open</a>
                            @endif
                        </td>
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
