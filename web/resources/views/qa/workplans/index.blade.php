@extends('layouts.qa')

@section('title', 'Semester workplans')

@section('qa-content')
    <x-page-toolbar title="Semester workplans" meta="HOD academic workplans awaiting QA review">
        <x-slot:filters>
            <form method="GET" class="tich-page-toolbar__filters-form">
                <select name="status" class="tich-input tich-input--compact" onchange="this.form.submit()">
                    <option value="">Awaiting QA</option>
                    <option value="pending" @selected($selectedStatus === 'pending')>Pending</option>
                    <option value="approved" @selected($selectedStatus === 'approved')>Approved</option>
                    <option value="rejected" @selected($selectedStatus === 'rejected')>Rejected</option>
                    <option value="changes_requested" @selected($selectedStatus === 'changes_requested')>Changes requested</option>
                    <option value="all" @selected($selectedStatus === 'all')>All submitted</option>
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Department</th>
                        <th>Title</th>
                        <th>Semester</th>
                        <th>HOD</th>
                        <th>Status</th>
                        <th>Registrar</th>
                        <th>QA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($workplans as $plan)
                        <tr>
                            <td>{{ $plan->workplan_number }}</td>
                            <td>{{ $plan->department?->dept_name }}</td>
                            <td>{{ $plan->title }}</td>
                            <td>{{ $plan->semester?->displayLabel() }}</td>
                            <td>{{ $plan->preparedByStaff?->fullName() }}</td>
                            <td><x-status-badge :status="$plan->status" /></td>
                            <td>
                                @if ($plan->registrar_status === 'approved')
                                    <span class="tich-badge tich-badge--success">✓</span>
                                @else
                                    <span class="tich-badge tich-badge--info">pending</span>
                                @endif
                            </td>
                            <td>
                                @if ($plan->qa_status === 'approved')
                                    <span class="tich-badge tich-badge--success">✓</span>
                                @elseif ($plan->qa_status === 'pending')
                                    <span class="tich-badge tich-badge--info">pending</span>
                                @else
                                    <x-status-badge :status="$plan->qa_status" />
                                @endif
                            </td>
                            <td><a href="{{ route('qa.workplans.show', $plan) }}" class="tich-link">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="tich-text">No workplans in this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
