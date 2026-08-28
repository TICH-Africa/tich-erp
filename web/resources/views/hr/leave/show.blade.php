@extends('layouts.hr')

@section('title', 'Review leave request')

@section('hr-content')
    @php
        $canReview = in_array($leaveRequest->overall_status, ['pending_hr', 'returned'], true)
            && auth()->user()->hasPermission('hr.manage_leave');
        $pendingReview = $leaveRequest->overall_status === 'pending_hr';
        $staffMember = $leaveRequest->staff;
    @endphp

    <div class="tich-mb-6">
        <a href="{{ route('hr.leave.index') }}" class="tich-btn tich-btn-ghost">← Back to leave inbox</a>
    </div>

    <article class="tich-card tich-mb-6" style="background:#f8fafc; border-left:4px solid #2563eb;">
        <h2 class="tich-h3">Leave policy reference</h2>
        <p class="tich-text tich-mt-2 tich-text--secondary">Use this when reviewing the request below.</p>
        <div class="tich-grid tich-grid--2 tich-mt-4">
            <div>
                <strong>Annual Leave</strong>
                <p class="tich-caption tich-mt-1">21 working days per year. Accrues monthly at 1.75 days/month. Counts working days only (Mon-Fri, excludes public holidays). Carry forward max 10 days.</p>
            </div>
            <div>
                <strong>Sick Leave</strong>
                <p class="tich-caption tich-mt-1">7 calendar days. Full salary. HR approval required. No carry forward.</p>
            </div>
            <div>
                <strong>Maternity Leave</strong>
                <p class="tich-caption tich-mt-1">90 calendar days. Includes weekends and public holidays. No carry forward.</p>
            </div>
            <div>
                <strong>Paternity Leave</strong>
                <p class="tich-caption tich-mt-1">14 calendar days. No carry forward.</p>
            </div>
            <div>
                <strong>Adoption Leave</strong>
                <p class="tich-caption tich-mt-1">30 calendar days. Includes weekends and public holidays. No carry forward.</p>
            </div>
            <div>
                <strong>Compassionate Leave</strong>
                <p class="tich-caption tich-mt-1">7 calendar days. No carry forward.</p>
            </div>
        </div>
    </article>

    <div class="tich-mb-6">
        <a href="{{ route('hr.leave.index') }}" class="tich-btn tich-btn-ghost">← Back to leave inbox</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Leave request</p>
            <h1 class="tich-leave-hero__title">{{ $leaveRequest->leave_number }}</h1>
            <div class="tich-leave-hero__meta">
                @include('partials.leave-status-badge', [
                    'status' => $leaveRequest->overall_status,
                    'label' => $leaveRequest->statusLabel(),
                ])
                <span class="tich-leave-hero__dates">
                    {{ $leaveRequest->start_date->format('d M Y') }} → {{ $leaveRequest->end_date->format('d M Y') }}
                </span>
                <span class="tich-caption">{{ $leaveRequest->leaveType?->leave_name }}</span>
                @if ($leaveRequest->is_emergency)
                    <span class="tich-badge tich-badge--danger">Emergency</span>
                @endif
            </div>
        </div>
        <div class="tich-leave-hero__stat">
            <span class="tich-caption">Days requested</span>
            <span class="tich-leave-hero__stat-value">{{ (int) $leaveRequest->days_requested }}</span>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mb-8" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Employee</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Name</span>
                    <span class="tich-kv-grid__value">{{ $staffMember->fullName() }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Employee no.</span>
                    <span class="tich-kv-grid__value">{{ $staffMember->employee_number }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Department</span>
                    <span class="tich-kv-grid__value">{{ $staffMember->department?->dept_name ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Job title</span>
                    <span class="tich-kv-grid__value">{{ $staffMember->job_title ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Line manager</span>
                    <span class="tich-kv-grid__value">{{ $staffMember->lineManager?->fullName() ?? '-' }}</span>
                </div>
            </div>
            <a href="{{ route('hr.staff.show', $staffMember) }}" class="tich-btn tich-btn-ghost tich-mt-4">View staff profile</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Leave balances ({{ now()->year }})</h2>
            @if ($leaveBalances->isEmpty())
                <p class="tich-text tich-mt-4 tich-text--secondary">No balances recorded.</p>
            @else
                <div class="tich-leave-balance-grid tich-mt-4">
                    @foreach ($leaveBalances as $balance)
                        @php
                            $entitled = max((int) $balance->entitled_days, 1);
                            $usedPct = min(100, ((int) $balance->days_taken + (int) $balance->days_pending) / $entitled * 100);
                            $isRequestedType = ($leaveRequest->leaveType?->leave_name ?? '') === $balance->leave_type_name;
                        @endphp
                        <article class="tich-leave-balance-card" @if($isRequestedType) style="border-color:var(--tich-green);" @endif>
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
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </article>
    </div>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Request details</h2>
        <div class="tich-kv-grid tich-mt-4">
            <div>
                <span class="tich-kv-grid__label">Submitted</span>
                <span class="tich-kv-grid__value">{{ $leaveRequest->created_at?->format('d M Y, H:i') ?? '-' }}</span>
            </div>
            <div>
                <span class="tich-kv-grid__label">Leave type</span>
                <span class="tich-kv-grid__value">{{ $leaveRequest->leaveType?->leave_name }}</span>
            </div>
            <div>
                <span class="tich-kv-grid__label">Start date</span>
                <span class="tich-kv-grid__value">{{ $leaveRequest->start_date->format('d M Y') }}</span>
            </div>
            <div>
                <span class="tich-kv-grid__label">End date</span>
                <span class="tich-kv-grid__value">{{ $leaveRequest->end_date->format('d M Y') }}</span>
            </div>
            <div style="grid-column: 1 / -1;">
                <span class="tich-kv-grid__label">Reason</span>
                <p class="tich-kv-grid__value tich-kv-grid__value--block tich-mt-2">{{ $leaveRequest->reason }}</p>
            </div>
            @if ($leaveRequest->handover_notes)
                <div style="grid-column: 1 / -1;">
                    <span class="tich-kv-grid__label">Handover notes</span>
                    <p class="tich-kv-grid__value tich-kv-grid__value--block tich-mt-2">{{ $leaveRequest->handover_notes }}</p>
                </div>
            @endif
            @if ($leaveRequest->cancellation_reason && $leaveRequest->overall_status === 'rejected')
                <div style="grid-column: 1 / -1;">
                    <span class="tich-kv-grid__label">Rejection reason</span>
                    <p class="tich-kv-grid__value tich-kv-grid__value--block tich-mt-2">{{ $leaveRequest->cancellation_reason }}</p>
                </div>
            @endif
            @if ($leaveRequest->hr_review_notes)
                <div style="grid-column: 1 / -1;">
                    <span class="tich-kv-grid__label">HR review notes</span>
                    <p class="tich-kv-grid__value tich-kv-grid__value--block tich-mt-2">{{ $leaveRequest->hr_review_notes }}</p>
                </div>
            @endif
        </div>
    </article>

    @if ($canReview)
        <article class="tich-card">
            <h2 class="tich-h3">HR decision</h2>
            <p class="tich-text tich-mt-2 tich-text--secondary">Approve as submitted, return to the employee for changes, or reject with a reason.</p>

            <div class="tich-leave-actions tich-mt-4">
                <div class="tich-leave-actions__tabs" role="tablist">
                    <button type="button" class="tich-leave-actions__tab is-active" data-leave-action-tab="approve" role="tab" aria-selected="true">Approve</button>
                    @if ($pendingReview)
                        <button type="button" class="tich-leave-actions__tab" data-leave-action-tab="return" role="tab" aria-selected="false">Return for changes</button>
                    @endif
                    <button type="button" class="tich-leave-actions__tab is-danger" data-leave-action-tab="reject" role="tab" aria-selected="false">Reject</button>
                </div>

                <div class="tich-leave-actions__panel" data-leave-action-panel="approve" role="tabpanel">
                    <form method="POST" action="{{ route('hr.leave.approve', $leaveRequest) }}">
                        @csrf
                        <div class="tich-form-stack">
                            <p class="tich-caption">You may adjust dates or notes before approving.</p>
                            <div class="tich-grid tich-grid--2">
                                <div>
                                    <label for="approve_start_date" class="tich-label">Start date</label>
                                    <input type="date" id="approve_start_date" name="start_date" class="tich-input"
                                        value="{{ old('start_date', $leaveRequest->start_date->format('Y-m-d')) }}">
                                </div>
                                <div>
                                    <label for="approve_end_date" class="tich-label">End date</label>
                                    <input type="date" id="approve_end_date" name="end_date" class="tich-input"
                                        value="{{ old('end_date', $leaveRequest->end_date->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div>
                                <label for="approve_reason" class="tich-label">Reason (optional edit)</label>
                                <textarea id="approve_reason" name="reason" class="tich-input" rows="3">{{ old('reason', $leaveRequest->reason) }}</textarea>
                            </div>
                            <div>
                                <label for="approve_handover_notes" class="tich-label">Handover notes (optional edit)</label>
                                <textarea id="approve_handover_notes" name="handover_notes" class="tich-input" rows="2">{{ old('handover_notes', $leaveRequest->handover_notes) }}</textarea>
                            </div>
                            <div>
                                <button type="submit" class="tich-btn tich-btn-primary">Approve leave</button>
                            </div>
                        </div>
                    </form>
                </div>

                @if ($pendingReview)
                    <div class="tich-leave-actions__panel" data-leave-action-panel="return" role="tabpanel" hidden>
                        <form method="POST" action="{{ route('hr.leave.return', $leaveRequest) }}">
                            @csrf
                            <div class="tich-form-stack">
                                <label for="return_notes" class="tich-label">Notes for employee</label>
                                <textarea id="return_notes" name="notes" class="tich-input" rows="4" required placeholder="Explain what the employee should update…">{{ old('notes') }}</textarea>
                                <div>
                                    <button type="submit" class="tich-btn tich-btn-secondary">Return to employee</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif

                <div class="tich-leave-actions__panel" data-leave-action-panel="reject" role="tabpanel" hidden>
                    <form method="POST" action="{{ route('hr.leave.reject', $leaveRequest) }}">
                        @csrf
                        <div class="tich-form-stack">
                            <label for="reject_reason" class="tich-label">Rejection reason</label>
                            <textarea id="reject_reason" name="reason" class="tich-input" rows="4" required placeholder="Reason shown to the employee…">{{ old('reason') }}</textarea>
                            <div>
                                <button type="submit" class="tich-btn tich-btn-ghost" onclick="return confirm('Reject this leave request?');">Reject leave</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </article>

        <script>
            (function () {
                var tabs = document.querySelectorAll('[data-leave-action-tab]');
                var panels = document.querySelectorAll('[data-leave-action-panel]');

                tabs.forEach(function (tab) {
                    tab.addEventListener('click', function () {
                        var target = tab.getAttribute('data-leave-action-tab');

                        tabs.forEach(function (item) {
                            var active = item.getAttribute('data-leave-action-tab') === target;
                            item.classList.toggle('is-active', active);
                            item.setAttribute('aria-selected', active ? 'true' : 'false');
                        });

                        panels.forEach(function (panel) {
                            var show = panel.getAttribute('data-leave-action-panel') === target;
                            panel.hidden = !show;
                        });
                    });
                });
            })();
        </script>
    @endif
@endsection
