@extends('layouts.finance')

@section('title', 'General Ledger')

@section('department-content')
    <x-page-toolbar title="General Ledger (GL)" meta="Chart of Accounts, journal entries, debits, credits, account balances, Trial Balance, P&amp;L, Balance Sheet and Cash Flow">
        <x-slot:actions>
            <a href="{{ route('finance.gl.journal.create', $department) }}" class="tich-btn tich-btn-primary">+ New journal entry</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Posted by</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="tich-table-empty">No journal entries yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

