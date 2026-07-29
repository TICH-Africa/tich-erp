@php($finance = $portalData['finance'])

<header class="tich-dept-header">
    <p class="tich-caption">Learning</p>
    <h1 class="tich-h1 tich-dept-header__title">Finance</h1>
    <p class="tich-text tich-dept-header__meta">Fee accounts, invoices, and payments.</p>
</header>

<div class="tich-grid tich-grid--3 tich-dept-stats tich-mt-8">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Outstanding balance</p>
        <p class="tich-stat__value">KES {{ number_format((float) $finance['summary']['outstanding_balance'], 2) }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Total paid</p>
        <p class="tich-stat__value">KES {{ number_format((float) $finance['summary']['total_paid'], 2) }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Fee clearance</p>
        <p class="tich-stat__value" style="font-size: 1.125rem;">{{ ucfirst($finance['summary']['fee_clearance_status'] ?? 'pending') }}</p>
    </article>
</div>

@if ($finance['accounts']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Fee accounts</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Academic year</th>
                        <th>Chargeable</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Cleared</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($finance['accounts'] as $account)
                        <tr>
                            <td>{{ $account->year_label }}</td>
                            <td>KES {{ number_format((float) $account->total_chargeable, 2) }}</td>
                            <td>KES {{ number_format((float) $account->total_paid, 2) }}</td>
                            <td>KES {{ number_format((float) $account->outstanding_balance, 2) }}</td>
                            <td>{{ $account->is_cleared ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($finance['invoices']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Invoices</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($finance['invoices'] as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $invoice->invoice_type)) }}</td>
                            <td>KES {{ number_format((float) $invoice->amount, 2) }}</td>
                            <td>KES {{ number_format((float) $invoice->amount_paid, 2) }}</td>
                            <td>KES {{ number_format((float) $invoice->balance, 2) }}</td>
                            <td>{{ $invoice->due_date ? \Illuminate\Support\Carbon::parse($invoice->due_date)->format('d M Y') : '-' }}</td>
                            <td>{{ ucfirst($invoice->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($finance['payments']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Payments</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($finance['payments'] as $payment)
                        <tr>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ $payment->payment_date ? \Illuminate\Support\Carbon::parse($payment->payment_date)->format('d M Y') : '-' }}</td>
                            <td>KES {{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td>{{ $payment->payment_reference ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@elseif ($finance['accounts']->isEmpty() && $finance['invoices']->isEmpty())
    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">No invoices yet</h2>
        <p class="tich-text tich-mt-2">
            Your current balance is <strong>KES {{ number_format((float) $finance['summary']['outstanding_balance'], 2) }}</strong>
            with fee clearance status <strong>{{ ucfirst($finance['summary']['fee_clearance_status'] ?? 'pending') }}</strong>.
            Invoices and receipts will appear here when the finance office posts them to your account.
        </p>
    </article>
@endif
