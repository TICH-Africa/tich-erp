@extends('layouts.finance')

@section('title', 'Finance Dashboard')

@section('finance-content')
    <x-page-toolbar title="Finance Dashboard" meta="Student fees, accounts receivable, treasury, and compliance reporting" />

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Accounts receivable</p>
            <p class="tich-stat__value">KES {{ number_format($stats['accounts_receivable'], 0) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Collected today</p>
            <p class="tich-stat__value">KES {{ number_format($stats['collected_today'], 0) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Open invoices</p>
            <p class="tich-stat__value">{{ $stats['open_invoices'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Treasury (main account)</p>
            <p class="tich-stat__value">KES {{ number_format($stats['treasury_balance'], 0) }}</p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <article class="tich-card">
            <div class="tich-flex tich-flex--between tich-mb-4">
                <h2 class="tich-h3" style="margin:0;">Recent invoices</h2>
                <a href="{{ route('finance.invoices.index') }}" class="tich-btn tich-btn-ghost">View all</a>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead><tr><th>Invoice</th><th>Student</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($stats['recent_invoices'] as $invoice)
                            <tr>
                                <td><a href="{{ route('finance.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                <td>{{ $invoice->student?->displayName() }}</td>
                                <td>KES {{ number_format((float) $invoice->balance, 2) }}</td>
                                <td>{{ ucfirst($invoice->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="tich-caption">No invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="tich-card">
            <div class="tich-flex tich-flex--between tich-mb-4">
                <h2 class="tich-h3" style="margin:0;">Recent payments</h2>
                <a href="{{ route('finance.payments.index') }}" class="tich-btn tich-btn-ghost">View all</a>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead><tr><th>Payment</th><th>Student</th><th>Amount</th><th>Method</th></tr></thead>
                    <tbody>
                        @forelse ($stats['recent_payments'] as $payment)
                            <tr>
                                <td>{{ $payment->payment_number }}</td>
                                <td>{{ $payment->student?->displayName() }}</td>
                                <td>KES {{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ config('finance.payment_methods.'.$payment->payment_method, $payment->payment_method) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="tich-caption">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    <div class="tich-grid tich-grid--3">
        <a href="{{ route('finance.fee-structures.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h3">Fee structures</h3>
            <p class="tich-text tich-mt-2">Configure programme, year, and semester fee schedules.</p>
        </a>
        <a href="{{ route('finance.invoices.create') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h3">Generate invoice</h3>
            <p class="tich-text tich-mt-2">Bill tuition, application, exam, or graduation fees.</p>
        </a>
        <a href="{{ route('finance.reports.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h3">Financial reports</h3>
            <p class="tich-text tich-mt-2">Trial balance, balance sheet, P&amp;L, and cashflow.</p>
        </a>
    </div>
@endsection
