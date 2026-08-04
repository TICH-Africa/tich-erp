@extends('layouts.hr')

@section('title', 'Offboarding - ' . $offboarding->staff->fullName())

@section('hr-content')
    <div class="tich-mb-8">
        <a href="{{ route('hr.offboarding.index') }}" class="tich-btn tich-btn-ghost">&larr; Back to offboarding</a>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <article class="tich-card">
            <h3 class="tich-h3">Offboarding Details</h3>
            <div class="tich-mt-4">
                <p><strong>Staff:</strong> {{ $offboarding->staff->fullName() }}</p>
                <p><strong>Employee No:</strong> {{ $offboarding->staff->employee_number }}</p>
                <p><strong>Exit Type:</strong> {{ ucfirst(str_replace('_', ' ', $offboarding->exit_type)) }}</p>
                <p><strong>Exit Date:</strong> {{ $offboarding->exit_date?->format('Y-m-d') }}</p>
                <p><strong>Notice Period:</strong> {{ $offboarding->notice_period_days ?? 0 }} days</p>
                <p><strong>Last Working Day:</strong> {{ $offboarding->last_working_day?->format('Y-m-d') ?? '—' }}</p>
                <p><strong>Status:</strong>
                    <span class="tich-badge tich-badge--{{ $offboarding->status === 'completed' ? 'success' : ($offboarding->status === 'rejected' ? 'danger' : 'warning') }}">
                        {{ ucfirst($offboarding->status) }}
                    </span>
                </p>
                <p><strong>Initiated By:</strong> {{ $offboarding->initiator?->fullName() ?? '—' }}</p>
                @if ($offboarding->approved_at)
                    <p><strong>Approved By:</strong> {{ $offboarding->approver?->fullName() ?? '—' }} on {{ $offboarding->approved_at?->format('Y-m-d H:i') }}</p>
                @endif
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Reason</h3>
            <div class="tich-mt-4">
                <p>{{ $offboarding->reason ?: '—' }}</p>
                @if ($offboarding->termination_reason)
                    <p class="tich-mt-2"><strong>Termination Reason:</strong> {{ $offboarding->termination_reason }}</p>
                @endif
            </div>
        </article>
    </div>

    <div class="tich-card tich-mb-8">
        <div class="tich-flex tich-flex--between tich-flex--start">
            <h3 class="tich-h3">Clearance Checklist</h3>
            @if ($offboarding->status === 'pending')
                <form method="POST" action="{{ route('hr.offboarding.approve', $offboarding) }}" class="tich-d-inline">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-success tich-mr-2">Approve</button>
                </form>
                <button type="button" onclick="document.getElementById('reject-form').style.display='block'" class="tich-btn tich-btn-danger">Reject</button>
                <div id="reject-form" style="display: none; margin-top: 1rem;">
                    <form method="POST" action="{{ route('hr.offboarding.reject', $offboarding) }}">
                        @csrf
                        <textarea name="notes" placeholder="Rejection reason..." class="tich-input" rows="2" required></textarea>
                        <button type="submit" class="tich-btn tich-btn-danger tich-mt-2">Confirm</button>
                    </form>
                </div>
            @endif

            @if ($offboarding->status === 'approved' && !$offboarding->clearanceItems->where('is_completed', false)->isEmpty())
                <form method="POST" action="{{ route('hr.offboarding.start-clearance', $offboarding) }}">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">Start Clearance</button>
                </form>
            @endif

            @if ($offboarding->status === 'in_progress' && $offboarding->clearanceItems->where('is_completed', false)->isEmpty())
                <form method="POST" action="{{ route('hr.offboarding.complete-clearance', $offboarding) }}" onsubmit="return confirm('Mark offboarding as completed? This will update staff status.')">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-success">Complete Clearance</button>
                </form>
            @endif
        </div>

        <div class="tich-mt-6">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Completed By</th>
                        <th>Remarks</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offboarding->clearanceItems as $item)
                        <tr>
                            <td class="tich-caption">{{ $item->department }}</td>
                            <td>{{ $item->item }}</td>
                            <td>
                                @if ($item->is_completed)
                                    <span class="tich-badge tich-badge--success">Completed</span>
                                @else
                                    <span class="tich-badge tich-badge--warning">Pending</span>
                                @endif
                            </td>
                            <td class="tich-caption">{{ $item->completedBy?->fullName() ?? '—' }}</td>
                            <td class="tich-caption">{{ $item->remarks ?: '—' }}</td>
                            <td>
                                @if (!$item->is_completed && $offboarding->status === 'in_progress')
                                    <form method="POST" action="{{ route('hr.offboarding.complete-item', [$offboarding, $item]) }}" class="tich-d-inline">
                                        @csrf
                                        <input type="text" name="remarks" placeholder="Remarks (optional)" class="tich-input" style="width: 150px; padding: 4px 8px; font-size: 12px;">
                                        <button type="submit" class="tich-btn tich-btn-sm tich-btn-success">Complete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No clearance items.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
