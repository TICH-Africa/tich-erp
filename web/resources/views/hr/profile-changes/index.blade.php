@extends('layouts.hr')

@section('title', 'Profile change requests')

@section('hr-content')
    <x-page-toolbar
        title="Profile change requests"
        :meta="$pendingCount > 0 ? $pendingCount . ' awaiting review' : 'Employee profile updates'"
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.profile-changes.index') }}" class="tich-page-toolbar__filters-form">
                <select name="status" class="tich-input tich-input--compact" onchange="this.form.submit()">
                    <option value="pending" @selected($status === 'pending')>Pending</option>
                    <option value="approved" @selected($status === 'approved')>Approved</option>
                    <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                    <option value="all" @selected($status === 'all')>All</option>
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Request type</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $changeRequest)
                        <tr>
                            <td>
                                <strong>{{ $changeRequest->staff->fullName() }}</strong>
                                <p class="tich-caption">{{ $changeRequest->staff->employee_number }} · {{ $changeRequest->staff->department?->dept_name }}</p>
                            </td>
                            <td>{{ $changeRequest->typeLabel() }}</td>
                            <td>{{ $changeRequest->created_at->format('d M Y H:i') }}</td>
                            <td>
                                @if ($changeRequest->status === 'pending')
                                    <span class="tich-badge tich-badge--warning">Pending</span>
                                @elseif ($changeRequest->status === 'approved')
                                    <span class="tich-badge tich-badge--success">Approved</span>
                                @else
                                    <span class="tich-badge tich-badge--danger">{{ ucfirst($changeRequest->status) }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('hr.profile-changes.show', $changeRequest) }}" class="tich-btn tich-btn-ghost">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tich-text tich-text--secondary">No profile change requests found.</td>
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
