@extends('layouts.finance')

@section('title', 'Accounts Receivable')

@section('department-content')
    <x-page-toolbar title="Accounts Receivable (AR)" meta="Student invoices, outstanding balances, overdue invoices, payment allocation, receivable ageing and payment reminders">
        <x-slot:actions>
            <a href="{{ route('finance.ar.create', $department) }}" class="tich-btn tich-btn-primary">+ New invoice</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Age (days)</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="tich-table-empty">No AR records yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

