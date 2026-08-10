@extends('layouts.finance')

@section('title', 'General ledger')

@section('finance-content')
    <x-page-toolbar title="General ledger" meta="Centralized treasury and double-entry audit trail" />

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Main treasury account ({{ $mainAccount }})</p>
            <p class="tich-stat__value">KES {{ number_format($balances[$mainAccount] ?? 0, 2) }}</p>
        </div>
    </div>

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
                    </tr>
                @empty
                    <tr><td colspan="6" class="tich-caption">No ledger entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
