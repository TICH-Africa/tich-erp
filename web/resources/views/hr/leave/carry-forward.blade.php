@extends('layouts.hr')

@section('title', 'Leave Carry-Forward Requests')

@section('hr-content')
    <x-page-toolbar title="Leave Carry-Forward Requests" meta="Review employee requests to carry forward unused annual leave days">
        <x-slot:actions>
            <form method="GET" style="display:inline-flex;gap:0.5rem;">
                <select name="status" class="tich-input tich-input--compact" onchange="this.form.submit()">
                    <option value="pending" @selected($filter === 'pending')>Pending</option>
                    <option value="approved" @selected($filter === 'approved')>Approved</option>
                    <option value="rejected" @selected($filter === 'rejected')>Rejected</option>
                    <option value="all" @selected($filter === 'all')>All</option>
                </select>
            </form>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave type</th>
                        <th>Period</th>
                        <th>Days requested</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $cfr)
                        <tr>
                            <td>
                                <strong>{{ $cfr->staff->fullName() }}</strong>
                                <p class="tich-caption">{{ $cfr->staff->employee_number }}</p>
                            </td>
                            <td class="tich-caption">{{ $cfr->leaveType->leave_name }}</td>
                            <td>{{ $cfr->from_year }} &rarr; {{ $cfr->to_year }}</td>
                            <td><strong>{{ number_format($cfr->days_requested, 1) }}</strong></td>
                            <td class="tich-caption" style="max-width:14rem;">{{ Str::limit($cfr->reason, 80) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($cfr->status) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'warning',
                                } }}">{{ $cfr->statusLabel() }}</span>
                            </td>
                            <td class="tich-caption">{{ $cfr->created_at?->format('d M Y') }}</td>
                            <td>
                                @if ($cfr->status === 'pending')
                                    <div style="display:flex;gap:0.25rem;flex-wrap:wrap;">
                                        <form method="POST" action="{{ route('hr.leave.carry-forward.approve', $cfr) }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="days_approved" value="{{ min($cfr->days_requested, 10) }}">
                                            <button type="submit" class="tich-btn tich-btn-ghost" style="color:var(--tich-green);" title="Approve full amount">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('hr.leave.carry-forward.reject', $cfr) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="tich-btn tich-btn-ghost" style="color:var(--tich-error);" title="Reject request">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="tich-caption">
                                        {{ $cfr->days_approved !== null ? number_format($cfr->days_approved, 1) . ' approved' : '' }}
                                        {{ $cfr->reviewer ? '· ' . $cfr->reviewer->fullName() : '' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 8, 'title' => 'No carry-forward requests', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($requests->hasPages())
            <div class="tich-pagination-wrap">{{ $requests->withQueryString()->links() }}</div>
        @endif
    </div>
@endsection
