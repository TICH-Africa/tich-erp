<x-page-toolbar title="Leave" :meta="$staff->employee_number . ' · ' . ($staff->job_title ?? 'Staff')" />

@if (($teamLeavePending ?? collect())->isNotEmpty())
    <article class="tich-card tich-mb-6">
        <h2 class="tich-h3 tich-mb-4">Team leave awaiting your approval</h2>
        <p class="tich-text tich-mb-4">These requests are from staff who report to you. Open Review to read the leave, then approve it into HR's inbox or decline it with a note the employee sees.</p>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave</th>
                        <th>Dates</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($teamLeavePending as $teamLeave)
                        <tr>
                            <td>
                                <strong>{{ $teamLeave->staff?->fullName() ?? '-' }}</strong>
                                <p class="tich-caption">{{ $teamLeave->staff?->employee_number }}</p>
                            </td>
                            <td>
                                {{ $teamLeave->leaveType?->leave_name ?? 'Leave' }}
                                <p class="tich-caption">
                                    <a href="{{ route('employee.leave.review', $teamLeave) }}">{{ $teamLeave->leave_number }}</a>
                                </p>
                            </td>
                            <td class="tich-caption">
                                {{ $teamLeave->start_date->format('d M Y') }}
                                –
                                {{ $teamLeave->end_date->format('d M Y') }}
                                · {{ (int) $teamLeave->days_requested }} day(s)
                            </td>
                            <td style="text-align:right;">
                                <a href="{{ route('employee.leave.review', $teamLeave) }}" class="tich-btn tich-btn-primary">Review</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
@endif

@if ($leaveBalances->isNotEmpty())
    <section class="tich-mt-6">
        <h2 class="tich-h3 tich-mb-4">Leave balances ({{ now()->year }})</h2>
        <div class="tich-leave-balance-grid">
            @foreach ($leaveBalances as $balance)
                @php
                    $entitled = max((int) $balance->entitled_days, 1);
                    $usedPct = min(100, ((int) $balance->days_taken + (int) $balance->days_pending) / $entitled * 100);
                @endphp
                <article class="tich-leave-balance-card">
                    <div class="tich-leave-balance-card__head">
                        <span class="tich-leave-balance-card__name">{{ $balance->leave_type_name }}</span>
                        <span class="tich-leave-balance-card__remaining">{{ (int) $balance->balance_days }} left</span>
                    </div>
                    <div class="tich-leave-balance-card__meter" aria-hidden="true">
                        <span class="tich-leave-balance-card__meter-fill" style="width: {{ $usedPct }}%;"></span>
                    </div>
                    <div class="tich-leave-balance-card__meta">
                        <span>{{ (int) $balance->days_taken }} taken</span>
                        <span>{{ (int) $balance->days_pending }} pending</span>
                        <span>{{ (int) $balance->entitled_days }} entitled</span>
                    </div>
                    @if ($balance->accrual_type === 'monthly' && $balance->accrual_rate)
                        <p class="tich-caption tich-mt-2">Monthly accrual: {{ $balance->accrual_rate }} days/month</p>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif

<article id="leave-requests-section" class="tich-card tich-mt-8">
    <div class="tich-flex tich-flex--between tich-mb-4" style="flex-wrap:wrap; gap:0.75rem;">
        <h2 class="tich-h3" style="margin:0;">Leave requests</h2>
        <button type="button" class="tich-btn tich-btn-primary" data-open-modal="leave-request-modal">
            + New leave request
        </button>
    </div>

    @if (! empty($leaveApplicationNotice))
        <p class="tich-text tich-text--secondary tich-mb-4">{{ $leaveApplicationNotice }}</p>
    @endif

    @if ($leaveRequests->isEmpty())
        <div style="text-align:center; padding:2.5rem 1rem;">
            <p class="tich-text tich-text--secondary">You have not submitted any leave requests yet.</p>
        </div>
    @else
        <div class="tich-leave-request-list">
            @foreach ($leaveRequests as $request)
                <div class="tich-leave-request-item">
                    <div>
                        <div class="tich-leave-request-item__title">
                            <strong>{{ $request->leaveType?->leave_name ?? 'Leave' }}</strong>
                            @include('partials.leave-status-badge', [
                                'status' => $request->overall_status,
                                'label' => $request->statusLabel(),
                            ])
                            @if ($request->is_emergency)
                                <span class="tich-badge tich-badge--danger">Emergency</span>
                            @endif
                        </div>
                        <p class="tich-leave-request-item__meta">
                            <span>{{ $request->leave_number }}</span>
                            · {{ $request->start_date->format('d M Y') }} - {{ $request->end_date->format('d M Y') }}
                            · {{ (int) $request->days_requested }} day(s)
                        </p>
                        @if ($request->hod_approval_status === 'rejected' && $request->cancellation_reason)
                            <div class="tich-leave-request-item__note">
                                <strong>Line manager note:</strong> {{ $request->cancellation_reason }}
                            </div>
                        @endif
                        @if ($request->hr_review_notes && $request->overall_status === 'returned')
                            <div class="tich-leave-request-item__note">
                                <strong>HR feedback:</strong> {{ $request->hr_review_notes }}
                            </div>
                        @endif
                    </div>
                    <div class="tich-flex tich-gap-2" style="flex-wrap:wrap; justify-content:flex-end;">
                        @if ($request->isEditableByEmployee())
                            <a href="{{ route('employee.leave.index', ['edit' => $request->id]) }}" class="tich-btn tich-btn-secondary">Edit &amp; resubmit</a>
                        @endif
                        @if ($request->isCancellableByEmployee())
                            <form method="POST" action="{{ route('employee.leave.cancel', $request) }}" onsubmit="return confirm('Cancel this leave request?');">
                                @csrf
                                <button type="submit" class="tich-btn tich-btn-ghost">Cancel</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</article>

@include('employee.partials.leave-request-modal', [
    'editRequest' => null,
    'leaveTypes' => $leaveTypes,
    'openModal' => false,
    'familyRelations' => $familyRelations ?? [],
    'coverageDepartments' => $coverageDepartments ?? collect(),
    'eligibleCoverStaff' => $eligibleCoverStaff ?? collect(),
    'leaveTypeMeta' => $leaveTypeMeta ?? [],
    'staff' => $staff,
    'hrManagerDisplay' => $hrManagerDisplay ?? 'the HR team',
    'leaveApplicationNotice' => $leaveApplicationNotice ?? null,
])
