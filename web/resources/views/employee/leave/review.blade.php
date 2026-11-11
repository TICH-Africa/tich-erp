@extends('layouts.employee')

@section('employee-content')
    @php
        $employee = $staffMember;
        $documentPath = $leaveRequest->supporting_document_path ?: $leaveRequest->medical_certificate_path;
        $managerDecided = in_array($leaveRequest->hod_approval_status, ['approved', 'rejected'], true);
        $declineNote = $leaveRequest->hod_approval_status === 'rejected' ? $leaveRequest->cancellation_reason : null;
    @endphp

    <div class="tich-mb-4">
        <a href="{{ route('employee.leave.index') }}" class="tich-btn tich-btn-ghost">← Back to My leave</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Leave request from {{ $employee?->fullName() }}</p>
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
                    <span class="tich-kv-grid__value">{{ $employee?->fullName() ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Employee no.</span>
                    <span class="tich-kv-grid__value">{{ $employee?->employee_number ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Department</span>
                    <span class="tich-kv-grid__value">{{ $employee?->department?->dept_name ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Job title</span>
                    <span class="tich-kv-grid__value">{{ $employee?->job_title ?? '-' }}</span>
                </div>
            </div>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Leave balances ({{ now()->year }})</h2>
            @if (($leaveBalances ?? collect())->isEmpty())
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
        <h2 class="tich-h3">What they asked for</h2>
        <div class="tich-kv-grid tich-mt-4">
            <div>
                <span class="tich-kv-grid__label">Leave type</span>
                <span class="tich-kv-grid__value">{{ $leaveRequest->leaveType?->leave_name ?? 'Leave' }}</span>
            </div>
            <div>
                <span class="tich-kv-grid__label">Submitted</span>
                <span class="tich-kv-grid__value">{{ $leaveRequest->created_at?->format('d M Y, H:i') ?? '-' }}</span>
            </div>
            <div>
                <span class="tich-kv-grid__label">First day</span>
                <span class="tich-kv-grid__value">{{ $leaveRequest->start_date->format('d M Y') }}</span>
            </div>
            <div>
                <span class="tich-kv-grid__label">Last day</span>
                <span class="tich-kv-grid__value">{{ $leaveRequest->end_date->format('d M Y') }}</span>
            </div>
            @if ($leaveRequest->return_date)
                <div>
                    <span class="tich-kv-grid__label">Back at work</span>
                    <span class="tich-kv-grid__value">{{ $leaveRequest->return_date->format('d M Y') }}</span>
                </div>
            @endif
            @if ($leaveRequest->family_relation)
                <div>
                    <span class="tich-kv-grid__label">Family member</span>
                    <span class="tich-kv-grid__value">{{ ucfirst($leaveRequest->family_relation) }}</span>
                </div>
            @endif
            <div style="grid-column: 1 / -1;">
                <span class="tich-kv-grid__label">Reason given</span>
                <p class="tich-kv-grid__value tich-kv-grid__value--block tich-mt-2">{{ $leaveRequest->reason ?: '—' }}</p>
            </div>
            <div style="grid-column: 1 / -1;">
                <span class="tich-kv-grid__label">Handover notes</span>
                <p class="tich-kv-grid__value tich-kv-grid__value--block tich-mt-2">{{ $leaveRequest->handover_notes ?: '—' }}</p>
            </div>
            @if ($leaveRequest->contact_mobile || $leaveRequest->contact_email || $leaveRequest->contact_postal_address)
                <div style="grid-column: 1 / -1;">
                    <span class="tich-kv-grid__label">Contact while on leave</span>
                    <p class="tich-kv-grid__value tich-kv-grid__value--block tich-mt-2">
                        @if ($leaveRequest->contact_mobile) Mobile: {{ $leaveRequest->contact_mobile }}<br>@endif
                        @if ($leaveRequest->contact_email) Email: {{ $leaveRequest->contact_email }}<br>@endif
                        @if ($leaveRequest->contact_postal_address) Postal: {{ $leaveRequest->contact_postal_address }}@endif
                    </p>
                </div>
            @endif
            @if ($documentPath)
                <div style="grid-column: 1 / -1;">
                    <span class="tich-kv-grid__label">Supporting document</span>
                    <span class="tich-kv-grid__value">
                        <a href="{{ route('employee.leave.review.document', $leaveRequest) }}">{{ $leaveRequest->supporting_document_name ?: basename($documentPath) }}</a>
                    </span>
                </div>
            @endif
        </div>
    </article>

    @if ($leaveRequest->coverages->isNotEmpty())
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Who is standing in</h2>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Stand-in</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaveRequest->coverages as $coverage)
                            <tr>
                                <td>{{ $coverage->department?->dept_name ?? '-' }}</td>
                                <td>
                                    <strong>{{ $coverage->coverStaff?->fullName() ?? '-' }}</strong>
                                    <p class="tich-caption">{{ $coverage->coverStaff?->employee_number }}</p>
                                </td>
                                <td class="tich-caption">
                                    {{ $coverage->statusLabel() }}
                                    @if ($coverage->response_notes)
                                        <p>“{{ $coverage->response_notes }}”</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Your decision</h2>
        @if ($awaitingManager)
            <p class="tich-text tich-mt-2">Approving sends this request to HR for the final approval. Declining needs a note, which the employee sees with their request.</p>
            <form method="POST" action="{{ route('employee.leave.manager-approve', $leaveRequest) }}" class="tich-mt-4">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Approve and send to HR</button>
            </form>
            <form method="POST" action="{{ route('employee.leave.manager-reject', $leaveRequest) }}" class="tich-mt-6">
                @csrf
                <label class="tich-label" for="manager-decline-note">Note for the employee</label>
                <textarea id="manager-decline-note" name="reason" rows="3" maxlength="2000" class="tich-input tich-mt-2" required
                    placeholder="Why you cannot approve this leave."></textarea>
                @error('reason')
                    <p class="tich-caption tich-text--danger tich-mt-2">{{ $message }}</p>
                @enderror
                <div class="tich-mt-4">
                    <button type="submit" class="tich-btn tich-btn--danger">Decline with note</button>
                </div>
            </form>
        @else
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Your decision</span>
                    <span class="tich-kv-grid__value">
                        {{ ucfirst($leaveRequest->hod_approval_status) }}
                        @if ($leaveRequest->hodApprovedBy)
                            <span class="tich-caption">by {{ $leaveRequest->hodApprovedBy->fullName() }}</span>
                        @endif
                        @if ($leaveRequest->hod_approved_at)
                            <span class="tich-caption"> · {{ $leaveRequest->hod_approved_at->format('d M Y, H:i') }}</span>
                        @endif
                    </span>
                </div>
                @if ($declineNote)
                    <div style="grid-column: 1 / -1;">
                        <span class="tich-kv-grid__label">Note you sent</span>
                        <p class="tich-kv-grid__value tich-kv-grid__value--block tich-mt-2">{{ $declineNote }}</p>
                    </div>
                @endif
                <div style="grid-column: 1 / -1;">
                    <span class="tich-kv-grid__label">Next step</span>
                    <span class="tich-kv-grid__value">
                        @if ($leaveRequest->overall_status === 'pending_hr')
                            HR is doing the final approval now.
                        @elseif ($leaveRequest->overall_status === 'approved')
                            HR approved this leave.
                        @elseif ($leaveRequest->overall_status === 'returned')
                            HR sent it back to {{ $employee?->fullName() }} for edits.
                        @elseif ($leaveRequest->overall_status === 'rejected')
                            The request is closed after your decline.
                        @elseif ($leaveRequest->overall_status === 'cancelled')
                            The employee cancelled this request.
                        @else
                            {{ $leaveRequest->statusLabel() }}
                        @endif
                    </span>
                </div>
            </div>
        @endif
    </article>
@endsection
