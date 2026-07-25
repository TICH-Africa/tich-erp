@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
    @endphp

    @include('academics.partials.learning-department-context')

    <header class="tich-dept-header">
        <h1 class="tich-h1 tich-dept-header__title">Lesson plan approval</h1>
        <p class="tich-text">Review submitted lesson plans from tutors in your department. Approve, request changes, or reject before class sessions begin.</p>
    </header>

    <form method="GET" class="tich-card tich-mt-6" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end;">
        @if (! empty($learningDepartments) && empty($learningDepartment))
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Learning department</label>
                <select name="learning_department" class="tich-input">
                    @foreach ($learningDepartments as $dept)
                        <option value="{{ $dept->id }}" @selected(request('learning_department') == $dept->id)>{{ $dept->dept_name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label">Status</label>
            <select name="status" class="tich-input">
                <option value="">Awaiting review</option>
                <option value="submitted" @selected($selectedStatus === 'submitted')>Submitted</option>
            </select>
        </div>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
        <a href="{{ route('departments.academics.lesson-plans.audit', $hub) }}" class="tich-link" style="margin-left:auto;">Curriculum audit repository →</a>
    </form>

    <div class="tich-card tich-mt-8" style="overflow-x:auto; padding:0;">
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
