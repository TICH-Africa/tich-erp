@extends('layouts.qa')

@section('title', 'Lesson plans')

@section('qa-content')
    <x-page-toolbar title="Lesson plans" meta="Acknowledge and comment in parallel with HOD approval">
        <x-slot:actions>
            <a href="{{ route('qa.lesson-plans.index', ['filter' => 'pending']) }}" class="tich-btn {{ $filter === 'pending' ? 'tich-btn-primary' : 'tich-btn-secondary' }} tich-btn--sm">Pending</a>
            <a href="{{ route('qa.lesson-plans.index', ['filter' => 'acknowledged']) }}" class="tich-btn {{ $filter === 'acknowledged' ? 'tich-btn-primary' : 'tich-btn-secondary' }} tich-btn--sm">Acknowledged</a>
            <a href="{{ route('qa.lesson-plans.index', ['filter' => 'all']) }}" class="tich-btn {{ $filter === 'all' ? 'tich-btn-primary' : 'tich-btn-secondary' }} tich-btn--sm">All</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Tutor</th>
                        <th>Unit</th>
                        <th>Department</th>
                        <th>HOD status</th>
                        <th>QA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td>
                                <a href="{{ route('qa.lesson-plans.show', $plan->id) }}" class="tich-link">{{ $plan->plan_number }}</a>
                                <p class="tich-caption">{{ \Illuminate\Support\Carbon::parse($plan->planned_date)->format('d M Y') }}</p>
                            </td>
                            <td>{{ $plan->tutor_name }}</td>
                            <td>{{ $plan->unit_code }} — {{ $plan->unit_name }}</td>
                            <td>{{ $plan->department_name ?? '—' }}</td>
                            <td><x-status-badge :status="$plan->status" /></td>
                            <td>
                                @if ($plan->qa_acknowledged_at)
                                    <span class="tich-caption">Acknowledged {{ \Illuminate\Support\Carbon::parse($plan->qa_acknowledged_at)->format('d M Y') }}</span>
                                @else
                                    <span class="tich-caption">Pending</span>
                                @endif
                            </td>
                            <td class="tich-table-actions">
                                <a href="{{ route('qa.lesson-plans.show', $plan->id) }}" class="tich-btn tich-btn-secondary tich-btn--sm">Open</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => 7,
                            'title' => 'No lesson plans in this view',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
