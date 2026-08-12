@extends('layouts.finance')

@section('title', 'Invoice')

@section('finance-content')
    <x-page-toolbar title="Invoice" meta="Invoice details">
        <x-slot:actions>
            @if (in_array($invoice->status, ['issued', 'partial', 'overdue']) && $invoice->balance > 0)
                <a href="{{ route('finance.payments.create', ['invoice_id' => $invoice->id]) }}" class="tich-btn tich-btn-primary">+ Record payment</a>
            @endif
            <a href="{{ route('finance.student-finance.invoices.download', ['department' => $department->id, 'id' => $invoice->id]) }}" class="tich-btn tich-btn-primary" target="_blank">Download PDF</a>
            <a href="{{ route('finance.student-finance.invoices.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Invoice</p>
            <p class="tich-stat__value">{{ $invoice->invoice_number }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Student</p>
            <p class="tich-stat__value">{{ $invoice->student->fullName() ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Type</p>
            <p class="tich-stat__value">{{ ucfirst($invoice->invoice_type) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Status</p>
            <p class="tich-stat__value">
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
            </p>
        </div>
    </div>

    <div class="tich-grid tich-grid--3 tich-mb-8">
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Amount</p>
            <p class="tich-stat__value">KES {{ number_format($invoice->amount, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Paid</p>
            <p class="tich-stat__value">KES {{ number_format($invoice->amount_paid, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Balance</p>
            <p class="tich-stat__value">KES {{ number_format($invoice->balance, 2) }}</p>
        </div>
    </div>

    <div class="tich-card tich-mb-8">
        <h3 class="tich-h4 tich-mb-4">Invoice Items</h3>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Fee Item</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Adjustments</th>
                        <th>Net Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->items as $item)
                        <tr>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $item->fee_item)) }}</td>
                            <td>{{ $item->description }}</td>
                            <td>KES {{ number_format($item->amount, 2) }}</td>
                            <td>
                                @if ($item->scholarship_adjustment > 0)
                                    <p>Scholarship: -KES {{ number_format($item->scholarship_adjustment, 2) }}</p>
                                @endif
                                @if ($item->bursary_adjustment > 0)
                                    <p>Bursary: -KES {{ number_format($item->bursary_adjustment, 2) }}</p>
                                @endif
                                @if ($item->waiver_adjustment > 0)
                                    <p>Waiver: -KES {{ number_format($item->waiver_adjustment, 2) }}</p>
                                @endif
                            </td>
                            <td><strong>KES {{ number_format($item->net_amount, 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tich-table-empty">No invoice items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-card">
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
                    @forelse ($invoice->payments as $payment)
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
@endsection



