@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
    @endphp

    @include('academics.partials.learning-department-context')

    <x-page-toolbar title="Lesson plan approval" meta="Review submitted plans from tutors">
        <x-slot:actions>
            <a href="{{ route('departments.academics.lesson-plans.audit', $hub) }}" class="tich-btn tich-btn-ghost">Audit repository</a>
        </x-slot:actions>
        <x-slot:filters>
            <form method="GET" class="tich-page-toolbar__filters-form">
                @if (! empty($learningDepartments) && empty($learningDepartment))
                    <select name="learning_department" class="tich-input tich-input--compact" onchange="this.form.submit()">
                        @foreach ($learningDepartments as $dept)
                            <option value="{{ $dept->id }}" @selected(request('learning_department') == $dept->id)>{{ $dept->dept_name }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="status" class="tich-input tich-input--compact" onchange="this.form.submit()">
                    <option value="">Awaiting review</option>
                    <option value="submitted" @selected($selectedStatus === 'submitted')>Submitted</option>
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Plan ID</th>
                    <th>Unit</th>
                    <th>Tutor</th>
                    <th>Planned date</th>
                    <th>Week</th>
                    <th>Contact hrs</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td>{{ $plan->plan_number }}</td>
                        <td>{{ $plan->unit_code }}</td>
                        <td>{{ trim($plan->tutor_first_name.' '.$plan->tutor_surname) }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($plan->planned_date)->format('d M Y') }}</td>
                        <td>{{ $plan->week_number }}</td>
                        <td>{{ $plan->contact_hours }}</td>
                        <td>{{ ucfirst($plan->status) }}</td>
                        <td>
                            <a href="{{ route('departments.academics.lesson-plans.show', array_merge($hub, ['plan' => $plan->id])) }}" class="tich-link">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="tich-text">No lesson plans awaiting HOD review.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
