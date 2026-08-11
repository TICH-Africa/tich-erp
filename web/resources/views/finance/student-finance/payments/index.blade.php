@extends('layouts.finance')

@section('title', 'Payments')

@section('finance-content')
    <x-page-toolbar title="Payments" meta="Student payment records">
<x-slot:actions>
            <a href="{{ route('finance.student-finance.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Student</th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td><strong>{{ $payment->payment_number }}</strong></td>
                            <td>
                                <strong>{{ $payment->student->fullName() ?? 'N/A' }}</strong>
                                <p class="tich-caption">{{ $payment->student->registration_number ?? 'N/A' }}</p>
                            </td>
                            <td class="tich-caption">{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
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
                            <td colspan="8" class="tich-table-empty">No payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments instanceof \Illuminate\Contracts\Pagination\Paginator && $payments->hasPages())
            <div class="tich-mt-4">{{ $payments->links() }}</div>
        @endif
    </div>
@endsection



