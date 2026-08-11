@extends('layouts.finance')

@section('title', 'Payment')

@section('finance-content')
    <x-page-toolbar title="Payment" meta="Payment details">
<x-slot:actions>
            <a href="{{ route('finance.student-finance.payments.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Payment</p>
            <p class="tich-stat__value">{{ $payment->payment_number }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Student</p>
            <p class="tich-stat__value">{{ $payment->student->fullName() ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Invoice</p>
            <p class="tich-stat__value">{{ $payment->invoice->invoice_number ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Amount</p>
            <p class="tich-stat__value">KES {{ number_format($payment->amount, 2) }}</p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <div class="tich-card">
            <h3 class="tich-h4 tich-mb-4">Payment Details</h3>
            <p class="tich-text"><strong>Date:</strong> {{ $payment->payment_date?->format('d M Y') }}</p>
            <p class="tich-text"><strong>Method:</strong> {{ ucfirst($payment->payment_method) }}</p>
            <p class="tich-text"><strong>Reference:</strong> {{ $payment->payment_reference ?? 'N/A' }}</p>
            <p class="tich-text"><strong>Channel Ref:</strong> {{ $payment->transaction_channel_ref ?? 'N/A' }}</p>
            <p class="tich-text"><strong>Reconciled:</strong> {{ $payment->is_reconciled ? 'Yes' : 'No' }}</p>
            <p class="tich-text"><strong>Recorded By:</strong> {{ $payment->recordedBy?->fullName() ?? 'N/A' }}</p>
        </div>
        <div class="tich-card">
            <h3 class="tich-h4 tich-mb-4">Receipt</h3>
            @if ($payment->receipt)
                <p class="tich-text"><strong>Receipt:</strong> {{ $payment->receipt->receipt_number }}</p>
                <p class="tich-text"><strong>Issued:</strong> {{ $payment->receipt->issued_at?->format('d M Y H:i') }}</p>
                <a href="{{ route('finance.student-finance.receipts.show', ['department' => $department->id, 'id' => $payment->receipt->id]) }}" class="tich-btn tich-btn-primary">Download Receipt</a>
            @else
                <p class="tich-table-empty">No receipt generated yet.</p>
            @endif
        </div>
    </div>

    <div class="tich-card">
        <h3 class="tich-h4 tich-mb-4">Allocations</h3>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Allocated Amount</th>
                        <th>Allocated At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payment->allocations as $allocation)
                        <tr>
                            <td class="tich-caption">{{ $allocation->invoice->invoice_number ?? 'N/A' }}</td>
                            <td>KES {{ number_format($allocation->allocated_amount, 2) }}</td>
                            <td class="tich-caption">{{ $allocation->allocated_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="tich-table-empty">No allocations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection



