@extends('layouts.finance')

@section('title', 'Accounts Payable')

@section('department-content')
    <x-page-toolbar title="Accounts Payable (AP)" meta="Supplier accounts, supplier invoices, verification, approval, payment and the supplier ledger. Uses the three-way match">
        <x-slot:actions>
            <a href="{{ route('finance.ap.create', $department) }}" class="tich-btn tich-btn-primary">+ New invoice</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Match status</th>
                        <th>Approval</th>
                        <th>Due date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="tich-table-empty">No AP records yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

