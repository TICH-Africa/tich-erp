@extends('layouts.finance')

@section('title', 'Finance Dashboard')

@section('finance-content')
<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">Treasury &amp; student accounts</p>
            <h1 class="tich-mod-dash__title">Finance command center</h1>
            <p class="tich-mod-dash__lede">Student fees, accounts receivable, treasury, payroll, and compliance reporting — live overview.</p>
        </div>
        <div class="tich-mod-dash__hero-actions">
            <a href="{{ route('finance.invoices.create') }}" class="tich-btn tich-btn-primary">Generate invoice</a>
            <a href="{{ route('finance.reports.index') }}" class="tich-btn tich-btn-secondary">Financial reports</a>
        </div>
    </header>

    @include('qa.partials.assigned-tasks-panel')

    <section class="tich-mod-dash__metrics" aria-label="Key finance metrics">
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Accounts receivable</p>
            <p class="tich-mod-dash__metric-value" style="font-size:1.05rem;">KES {{ number_format($stats['accounts_receivable'], 0) }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--ok">
            <p class="tich-mod-dash__metric-label">Collected today</p>
            <p class="tich-mod-dash__metric-value" style="font-size:1.05rem;">KES {{ number_format($stats['collected_today'], 0) }}</p>
        </article>
        <article class="tich-mod-dash__metric {{ ($stats['open_invoices'] ?? 0) > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Open invoices</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['open_invoices'] }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--info">
            <p class="tich-mod-dash__metric-label">Treasury</p>
            <p class="tich-mod-dash__metric-value" style="font-size:1.05rem;">KES {{ number_format($stats['treasury_balance'], 0) }}</p>
            <p class="tich-mod-dash__metric-hint">Main account</p>
        </article>
    </section>

    <div class="tich-mod-dash__charts" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
        <article class="tich-mod-dash__panel">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Receivables</p>
                    <h2 class="tich-mod-dash__panel-title">Recent invoices</h2>
                </div>
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

        <article class="tich-mod-dash__panel">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Collections</p>
                    <h2 class="tich-mod-dash__panel-title">Recent payments</h2>
                </div>
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

    <section class="tich-mod-dash__nav" aria-label="Finance shortcuts">
        <p class="tich-mod-dash__section-label">Quick routes</p>
        <div class="tich-mod-dash__nav-grid">
            <a href="{{ route('finance.student-finance.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">01</span>
                <h3 class="tich-mod-dash__nav-title">Student finance</h3>
                <p class="tich-mod-dash__nav-text">Accounts, fee structures, invoices, payments, receipts, and clearance.</p>
            </a>
            <a href="{{ route('finance.records.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">02</span>
                <h3 class="tich-mod-dash__nav-title">Finance records</h3>
                <p class="tich-mod-dash__nav-text">General ledger, AR/AP, budgeting, projects, and treasury.</p>
            </a>
            <a href="{{ route('finance.employee.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">03</span>
                <h3 class="tich-mod-dash__nav-title">Employee finance</h3>
                <p class="tich-mod-dash__nav-text">Payroll runs, statutory settings, and GL integration.</p>
            </a>
            <a href="{{ route('finance.invoices.create') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">04</span>
                <h3 class="tich-mod-dash__nav-title">Generate invoice</h3>
                <p class="tich-mod-dash__nav-text">Bill tuition, application, exam, or graduation fees.</p>
            </a>
            <a href="{{ route('finance.reports.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">05</span>
                <h3 class="tich-mod-dash__nav-title">Financial reports</h3>
                <p class="tich-mod-dash__nav-text">Trial balance, balance sheet, P&amp;L, and cashflow.</p>
            </a>
            @can('finance.payments.manage')
                <a href="{{ route('finance.mpesa.settings') }}" class="tich-mod-dash__nav-card">
                    <span class="tich-mod-dash__nav-index">06</span>
                    <h3 class="tich-mod-dash__nav-title">M-Pesa settings</h3>
                    <p class="tich-mod-dash__nav-text">Configure Daraja STK push for student self-pay.</p>
                </a>
            @endcan
        </div>
    </section>
</div>
@endsection
