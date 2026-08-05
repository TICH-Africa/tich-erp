@extends('layouts.hr')

@section('title', 'Leave requests')

@section('hr-content')
    <x-page-toolbar
        title="Leave requests"
        :meta="$pendingCount > 0 ? $pendingCount . ' awaiting HR review' : null"
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.leave.index') }}" class="tich-page-toolbar__filters-form">
                @include('partials.search-field', [
                    'placeholder' => 'Name, employee no., leave no.',
                    'value' => request('search'),
                ])
                <select id="status" name="status" class="tich-input tich-input--compact">
                    <option value="">All statuses</option>
                    <option value="pending_hr" @selected(request('status') === 'pending_hr')>Awaiting HR</option>
                    <option value="returned" @selected(request('status') === 'returned')>Returned</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Leave no.</th>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $leaveRequest)
                        <tr>
                            <td>{{ $leaveRequest->leave_number }}</td>
                            <td>
                                <strong>{{ $leaveRequest->staff->fullName() }}</strong>
                                <p class="tich-caption">{{ $leaveRequest->staff->employee_number }}</p>
                            </td>
                            <td>{{ $leaveRequest->leaveType?->leave_name }}</td>
                            <td>{{ $leaveRequest->start_date->format('d M Y') }} – {{ $leaveRequest->end_date->format('d M Y') }}</td>
                            <td>{{ (int) $leaveRequest->days_requested }}</td>
                            <td>{{ $leaveRequest->statusLabel() }}</td>
                            <td>{{ $leaveRequest->created_at?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('hr.leave.show', $leaveRequest) }}" class="tich-btn tich-btn-ghost">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-text tich-text--secondary">No leave requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($requests->hasPages())
            <div class="tich-mt-4">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection
