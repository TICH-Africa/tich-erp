@extends('layouts.finance')

@section('title', 'Student account')

@section('finance-content')
    <x-page-toolbar title="{{ $account->student?->displayName() }}" meta="{{ $account->student?->registration_number }} · {{ $account->student?->program?->program_name }}">
        <a href="{{ route('finance.invoices.create', ['student_id' => $account->student_id]) }}" class="tich-btn tich-btn-primary">Generate invoice</a>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat"><p class="tich-stat__label">Chargeable</p><p class="tich-stat__value">KES {{ number_format((float) $account->total_chargeable, 0) }}</p></div>
        <div class="tich-stat"><p class="tich-stat__label">Paid</p><p class="tich-stat__value">KES {{ number_format((float) $account->total_paid, 0) }}</p></div>
        <div class="tich-stat"><p class="tich-stat__label">Outstanding</p><p class="tich-stat__value">KES {{ number_format((float) $account->outstanding_balance, 0) }}</p></div>
    </div>

    <section class="tich-mb-8">
        <h2 class="tich-h3">Invoices</h2>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead><tr><th>Number</th><th>Type</th><th>Amount</th><th>Balance</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($account->invoices as $invoice)
                        <tr>
                            <td><a href="{{ route('finance.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ config('finance.invoice_types.'.$invoice->invoice_type, $invoice->invoice_type) }}</td>
                            <td>KES {{ number_format((float) $invoice->amount, 2) }}</td>
                            <td>KES {{ number_format((float) $invoice->balance, 2) }}</td>
                            <td>{{ ucfirst($invoice->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2 class="tich-h3">Payments</h2>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead><tr><th>Payment</th><th>Date</th><th>Amount</th><th>Method</th></tr></thead>
                <tbody>
                    @foreach ($account->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                            <td>KES {{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ config('finance.payment_methods.'.$payment->payment_method, $payment->payment_method) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
