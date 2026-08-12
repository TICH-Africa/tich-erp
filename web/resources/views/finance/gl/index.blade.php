@extends('layouts.finance')

@section('title', 'General Ledger')

@section('finance-content')
    <x-page-toolbar title="General Ledger (GL)" meta="Chart of Accounts, journal entries, debits, credits, account balances, Trial Balance, P&amp;L, Balance Sheet and Cash Flow">
        <x-slot:actions>
            <a href="{{ route('finance.reports.index', ['report' => 'trial_balance']) }}" class="tich-btn tich-btn-secondary">Financial reports</a>
            <a href="{{ route('finance.gl.journal.create', $department) }}" class="tich-btn tich-btn-primary">+ New journal entry</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Treasury account ({{ $mainAccount }})</p>
            <p class="tich-stat__value">KES {{ number_format($balances[$mainAccount] ?? 0, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Active accounts</p>
            <p class="tich-stat__value">{{ $accounts->count() }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Trial balance</p>
            <p class="tich-stat__value" style="font-size:1rem;">
                Dr {{ number_format($trialBalance['total_debit'], 2) }} /
                Cr {{ number_format($trialBalance['total_credit'], 2) }}
            </p>
        </article>
    </div>

    <section class="tich-mt-8">
        <h2 class="tich-h3 tich-mb-4">Chart of accounts</h2>
        <div class="tich-card tich-table-panel">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Account name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts as $account)
                        <tr>
                            <td><strong>{{ $account->account_code }}</strong></td>
                            <td>{{ $account->account_name }}</td>
                            <td>{{ ucfirst($account->account_type) }}</td>
                            <td>{{ $account->account_category }}</td>
                            <td>KES {{ number_format($balances[$account->account_code] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="tich-mt-8">
        <h2 class="tich-h3 tich-mb-4">Recent journal entries</h2>
        <div class="tich-card tich-table-panel">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Amount</th>
                        <th>Narration</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->ledger_date?->format('d M Y') }}</td>
                            <td>{{ str_replace('_', ' ', $entry->transaction_type) }}</td>
                            <td>{{ $entry->debit_account_code }}</td>
                            <td>{{ $entry->credit_account_code }}</td>
                            <td>KES {{ number_format(max((float) $entry->debit_amount, (float) $entry->credit_amount), 2) }}</td>
                            <td>{{ $entry->narration }}</td>
                            <td>
                                <a href="{{ route('finance.gl.show', [$department, $entry]) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tich-table-empty">No journal entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
