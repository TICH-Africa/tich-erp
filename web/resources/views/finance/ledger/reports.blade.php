@extends('layouts.finance')

@section('title', 'Financial reports')

@section('finance-content')
    <x-page-toolbar title="Financial reports" meta="Compliance-ready statements and live treasury dashboards" />

    <div class="tich-flex tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        @foreach ([
            'trial_balance' => 'Trial balance',
            'balance_sheet' => 'Balance sheet',
            'income_statement' => 'Profit & loss',
            'cashflow' => 'Cashflow',
            'general_ledger' => 'General ledger',
        ] as $key => $label)
            <a href="{{ route('finance.reports.index', ['report' => $key]) }}" class="tich-btn {{ $report === $key ? 'tich-btn-primary' : 'tich-btn-ghost' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($report === 'trial_balance')
        <div class="tich-card tich-table-panel">
            <table class="tich-admin-table">
                <thead><tr><th>Code</th><th>Account</th><th>Type</th><th>Debit</th><th>Credit</th></tr></thead>
                <tbody>
                    @foreach ($suite['trial_balance']['rows'] as $row)
                        <tr>
                            <td>{{ $row['account_code'] }}</td>
                            <td>{{ $row['account_name'] }}</td>
                            <td>{{ ucfirst($row['account_type']) }}</td>
                            <td>{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '—' }}</td>
                            <td>{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Totals</th>
                        <th>{{ number_format($suite['trial_balance']['total_debit'], 2) }}</th>
                        <th>{{ number_format($suite['trial_balance']['total_credit'], 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @elseif ($report === 'balance_sheet')
        <article class="tich-card">
            <p><strong>Assets:</strong> KES {{ number_format($suite['balance_sheet']['assets'], 2) }}</p>
            <p><strong>Liabilities:</strong> KES {{ number_format($suite['balance_sheet']['liabilities'], 2) }}</p>
            <p><strong>Equity:</strong> KES {{ number_format($suite['balance_sheet']['equity'], 2) }}</p>
            <p><strong>Liabilities + equity:</strong> KES {{ number_format($suite['balance_sheet']['total_liabilities_equity'], 2) }}</p>
        </article>
    @elseif ($report === 'income_statement')
        <article class="tich-card">
            <p><strong>Revenue:</strong> KES {{ number_format($suite['income_statement']['revenue'], 2) }}</p>
            <p><strong>Expenses:</strong> KES {{ number_format($suite['income_statement']['expenses'], 2) }}</p>
            <p><strong>Net income:</strong> KES {{ number_format($suite['income_statement']['net_income'], 2) }}</p>
        </article>
    @elseif ($report === 'cashflow')
        <article class="tich-card">
            <p><strong>Operating cashflow:</strong> KES {{ number_format($suite['cashflow']['operating'], 2) }}</p>
            <p><strong>Investing:</strong> KES {{ number_format($suite['cashflow']['investing'], 2) }}</p>
            <p><strong>Financing:</strong> KES {{ number_format($suite['cashflow']['financing'], 2) }}</p>
        </article>
    @else
        <div class="tich-card tich-table-panel">
            <table class="tich-admin-table">
                <thead><tr><th>Date</th><th>Type</th><th>Debit</th><th>Credit</th><th>Amount</th><th>Narration</th></tr></thead>
                <tbody>
                    @foreach ($suite['general_ledger'] as $entry)
                        <tr>
                            <td>{{ $entry->ledger_date?->format('d M Y') }}</td>
                            <td>{{ str_replace('_', ' ', $entry->transaction_type) }}</td>
                            <td>{{ $entry->debit_account_code }}</td>
                            <td>{{ $entry->credit_account_code }}</td>
                            <td>KES {{ number_format(max((float) $entry->debit_amount, (float) $entry->credit_amount), 2) }}</td>
                            <td>{{ $entry->narration }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
