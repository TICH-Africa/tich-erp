@extends('layouts.finance')

@section('title', 'Installment Plan')

@section('finance-content')
    <x-page-toolbar title="Installment Plan" meta="{{ $plan->plan_number }} - {{ $plan->student->fullName() ?? 'N/A' }}">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.installment-plans.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Student</p>
            <p class="tich-stat__value">{{ $plan->student->fullName() ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Registration No</p>
            <p class="tich-stat__value">{{ $plan->student->registration_number ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Invoice</p>
            <p class="tich-stat__value">{{ $plan->invoice->invoice_number ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Semester</p>
            <p class="tich-stat__value">{{ $plan->semester?->displayLabel() ?? ($plan->invoice->semester?->displayLabel() ?? 'N/A') }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Academic Year</p>
            <p class="tich-stat__value">{{ $plan->academicYear?->year_label ?? ($plan->invoice->semester?->academicYear?->year_label ?? 'N/A') }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Total Amount</p>
            <p class="tich-stat__value">KES {{ number_format($plan->total_amount, 2) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Paid</p>
            <p class="tich-stat__value">KES {{ number_format($plan->paid_amount, 2) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Remaining</p>
            <p class="tich-stat__value">KES {{ number_format($plan->remaining_amount, 2) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Status</p>
            <p class="tich-stat__value">
                <span class="tich-badge tich-badge--{{ match($plan->status) {
                    'active' => 'info',
                    'completed' => 'success',
                    'defaulted' => 'danger',
                    'cancelled' => 'secondary',
                    default => 'secondary',
                } }}">
                    {{ ucfirst($plan->status) }}
                </span>
            </p>
        </div>
    </div>

    <div class="tich-card tich-mb-8">
        <h3 class="tich-h4 tich-mb-4">Installment Schedule</h3>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plan->items ?? [] as $item)
                        <tr>
                            <td>Installment {{ $item->installment_number }}</td>
                            <td class="tich-caption">{{ $item->due_date?->format('d M Y') }}</td>
                            <td>KES {{ number_format($item->amount, 2) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($item->status) {
                                    'paid' => 'success',
                                    'pending' => 'secondary',
                                    'partial' => 'warning',
                                    'overdue' => 'danger',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="tich-table-empty">No installments scheduled.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($plan->milestones->count() > 0)
        <div class="tich-card tich-mb-8">
            <h3 class="tich-h4 tich-mb-4">Payment Milestones</h3>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Milestone</th>
                            <th>Percentage</th>
                            <th>Target Amount</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plan->milestones as $milestone)
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
                                <td colspan="6" class="tich-table-empty">No milestones found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="tich-card">
        <h3 class="tich-h4 tich-mb-4">Payment History</h3>
        @if ($paymentHistory->count() > 0)
            @foreach ($paymentHistory as $period => $payments)
                <h4 class="tich-h5 tich-mb-2">{{ \Carbon\Carbon::createFromFormat('Y-m', $period)->format('F Y') }}</h4>
                <div class="tich-table-wrap tich-mb-4">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Payment</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td class="tich-caption">{{ $payment->payment_date?->format('d M Y') }}</td>
                                    <td><strong>{{ $payment->payment_number }}</strong></td>
                                    <td class="tich-caption">{{ ucfirst($payment->payment_method) }}</td>
                                    <td>KES {{ number_format($payment->amount, 2) }}</td>
                                    <td class="tich-caption">{{ $payment->payment_reference ?? '—' }}</td>
                                    <td>
                                        @if ($payment->receipt)
                                            <a href="{{ route('finance.student-finance.receipts.show', ['department' => $department->id, 'id' => $payment->receipt->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                                        @else
                                            <span class="tich-caption">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @else
            <p class="tich-text">No payments recorded for this student yet.</p>
        @endif
    </div>
@endsection
