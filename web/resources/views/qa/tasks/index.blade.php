@extends('layouts.qa')

@section('title', 'My department QA tasks')

@section('qa-content')
    <x-page-toolbar title="My department QA tasks" meta="Outstanding assessment sheets assigned to your department(s)" />

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Assessment</th>
                    <th>Your department(s)</th>
                    <th>Due</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    @php
                        $mine = $departments->whereIn('id', $plan->targetDepartmentIds());
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $plan->plan_name }}</strong>
                            <p class="tich-caption">{{ str_replace('_', ' ', $plan->status) }}</p>
                        </td>
                        <td>
                            @foreach ($mine as $department)
                                <div class="tich-mt-1">
                                    <a href="{{ route('qa.tasks.show', [$plan, $department]) }}" class="tich-link">{{ $department->dept_name }}</a>
                                </div>
                            @endforeach
                        </td>
                        <td>{{ $plan->due_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>
                            @if ($mine->isNotEmpty())
                                <a href="{{ route('qa.tasks.show', [$plan, $mine->first()]) }}" class="tich-btn tich-btn-primary">Fill</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', ['colspan' => 4, 'title' => 'No outstanding QA tasks', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
