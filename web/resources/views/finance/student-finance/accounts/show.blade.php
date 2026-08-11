@extends('layouts.finance')

@section('title', 'Student Account')

@section('finance-content')
    <x-page-toolbar title="Student Account" meta="Financial account details">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.accounts.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Student</p>
            <p class="tich-stat__value">{{ $account->student->fullName() ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Registration No</p>
            <p class="tich-stat__value">{{ $account->student->registration_number ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Academic Year</p>
            <p class="tich-stat__value">{{ $account->academicYear->year_label ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Clearance</p>
            <p class="tich-stat__value">
                @if ($account->is_cleared)
                    <span class="tich-badge tich-badge--success">CLEARED</span>
                @else
                    <span class="tich-badge tich-badge--warning">NOT CLEARED</span>
                @endif
            </p>
        </div>
    </div>

    <div class="tich-grid tich-grid--3 tich-mb-8">
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Total Chargeable</p>
            <p class="tich-stat__value">KES {{ number_format($account->total_chargeable, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Total Paid</p>
            <p class="tich-stat__value">KES {{ number_format($account->total_paid, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Outstanding Balance</p>
            <p class="tich-stat__value">KES {{ number_format($account->outstanding_balance, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Credit Balance</p>
            <p class="tich-stat__value">KES {{ number_format($account->credit_balance, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Scholarship</p>
            <p class="tich-stat__value">KES {{ number_format($account->scholarship_amount, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">HELB</p>
            <p class="tich-stat__value">KES {{ number_format($account->helb_amount, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Sponsor</p>
            <p class="tich-stat__value">KES {{ number_format($account->sponsor_amount, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Work Study Credit</p>
            <p class="tich-stat__value">KES {{ number_format($account->work_study_credit, 2) }}</p>
        </div>
    </div>

    <div class="tich-card tich-mb-8">
        <h3 class="tich-h4 tich-mb-4">Invoices</h3>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->invoices as $invoice)
                        <tr>
                            <td><strong>{{ $invoice->invoice_number }}</strong></td>
                            <td class="tich-caption">{{ ucfirst($invoice->invoice_type) }}</td>
                            <td>KES {{ number_format($invoice->amount, 2) }}</td>
                            <td>KES {{ number_format($invoice->amount_paid, 2) }}</td>
                            <td>KES {{ number_format($invoice->balance, 2) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($invoice->status) {
                                    'issued' => 'info',
                                    'partial' => 'warning',
                                    'paid' => 'success',
                                    'overdue' => 'danger',
                                    'waived' => 'secondary',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.student-finance.invoices.show', ['department' => $department->id, 'id' => $invoice->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tich-table-empty">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-card tich-mb-8">
        <h3 class="tich-h4 tich-mb-4">Payments</h3>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->payments as $payment)
                        <tr>
                            <td><strong>{{ $payment->payment_number }}</strong></td>
                            <td class="tich-caption">{{ $payment->payment_date?->format('d M Y') }}</td>
                            <td class="tich-caption">{{ ucfirst($payment->payment_method) }}</td>
                            <td>KES {{ number_format($payment->amount, 2) }}</td>
                            <td class="tich-caption">{{ $payment->payment_reference ?? '—' }}</td>
                            <td>
                                <a href="{{ route('finance.student-finance.payments.show', ['department' => $department->id, 'id' => $payment->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-card tich-mb-8">
        <h3 class="tich-h4 tich-mb-4">Adjustments</h3>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->adjustments as $adjustment)
                        <tr>
                            <td class="tich-caption">{{ ucfirst($adjustment->adjustment_type) }}</td>
                            <td>KES {{ number_format($adjustment->amount, 2) }}</td>
                            <td>{{ $adjustment->reason }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($adjustment->status) {
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($adjustment->status) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $adjustment->requestedBy?->fullName() ?? ($adjustment->requested_by ?? 'N/A') }}</td>
                            <td class="tich-caption">{{ $adjustment->created_at?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No adjustments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-card tich-mb-8">
        <h3 class="tich-h4 tich-mb-4">Installment Plans</h3>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->installmentPlans as $plan)
                        <tr>
                            <td><strong>{{ $plan->plan_number }}</strong></td>
                            <td>KES {{ number_format($plan->total_amount, 2) }}</td>
                            <td>KES {{ number_format($plan->paid_amount, 2) }}</td>
                            <td>KES {{ number_format($plan->remaining_amount, 2) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($plan->status) {
                                    'active' => 'info',
                                    'completed' => 'success',
                                    'defaulted' => 'danger',
                                    'cancelled' => 'secondary',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($plan->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.student-finance.installment-plans.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No installment plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-card">
        <h3 class="tich-h4 tich-mb-4">Refunds</h3>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Refund</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->refunds as $refund)
                        <tr>
                            <td><strong>{{ $refund->refund_number }}</strong></td>
                            <td>KES {{ number_format($refund->amount, 2) }}</td>
                            <td>{{ $refund->reason }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($refund->status) {
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'processed' => 'info',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($refund->status) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $refund->requestedBy?->fullName() ?? 'N/A' }}</td>
                            <td class="tich-caption">{{ $refund->created_at?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No refunds found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-card">
        <h3 class="tich-h4 tich-mb-4">Payment Milestones</h3>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Milestone</th>
                        <th>Percentage</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->milestones as $milestone)
                        <tr>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $milestone->milestone_type)) }}</td>
                            <td>{{ $milestone->percentage }}%</td>
                            <td>KES {{ number_format($milestone->milestone_amount, 2) }}</td>
                            <td>KES {{ number_format($milestone->paid_amount, 2) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($milestone->status) {
                                    'pending' => 'secondary',
                                    'partial' => 'warning',
                                    'paid' => 'success',
                                    'overdue' => 'danger',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($milestone->status) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $milestone->due_date?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No payment milestones found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection



