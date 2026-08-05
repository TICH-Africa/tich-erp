@extends('layouts.employee')

@section('employee-content')
    @php
        $editing = $editRequest !== null;
        $openModal = $editing || $errors->any();
    @endphp

    <x-page-toolbar title="My leave" meta="Balances, requests, and applications">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="leave-request-modal">
                + New leave request
            </button>
        </x-slot:actions>
    </x-page-toolbar>

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
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <article class="tich-card tich-mt-8">
        <div class="tich-flex tich-flex--between tich-mb-4" style="flex-wrap:wrap; gap:0.75rem;">
            <h2 class="tich-h3" style="margin:0;">Leave requests</h2>
            <span class="tich-caption">{{ $leaveRequests->count() }} total</span>
        </div>

        @if ($leaveRequests->isEmpty())
            <div style="text-align:center; padding:2.5rem 1rem;">
                <p class="tich-text tich-text--secondary">You have not submitted any leave requests yet.</p>
                <button type="button" class="tich-btn tich-btn-primary tich-mt-4" data-open-modal="leave-request-modal">
                    Submit your first request
                </button>
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
                                · {{ $request->start_date->format('d M Y') }} – {{ $request->end_date->format('d M Y') }}
                                · {{ (int) $request->days_requested }} day(s)
                            </p>
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
        'editRequest' => $editRequest,
        'leaveTypes' => $leaveTypes,
        'openModal' => $openModal,
    ])
@endsection
