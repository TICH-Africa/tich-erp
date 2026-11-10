@extends('layouts.finance')

@section('title', 'Payments')

@section('finance-content')
    <x-page-toolbar title="Payments" meta="M-Pesa, bank, card, and manual settlement">
        <x-slot:actions>
            <a href="{{ route('finance.payments.create') }}" class="tich-btn tich-btn-primary">Record payment</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Payment</th>
                    <th>Student</th>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reconciled</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_number }}</td>
                        <td>{{ $payment->student?->displayName() }}</td>
                        <td>{{ $payment->invoice?->invoice_number }}</td>
                        <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td>KES {{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ config('finance.payment_methods.'.$payment->payment_method, $payment->payment_method) }}</td>
                        <td>{{ $payment->is_reconciled ? 'Yes' : 'No' }}</td>
                        <td>
                            @can('finance.payments.manage')
                                <a href="{{ route('finance.payments.edit', $payment) }}" class="tich-btn tich-btn-ghost">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="tich-caption">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
@endsection
