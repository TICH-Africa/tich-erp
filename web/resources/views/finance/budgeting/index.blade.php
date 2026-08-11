@extends('layouts.finance')

@section('title', 'Budgeting')

@section('department-content')
    <x-page-toolbar title="Budgeting" meta="Annual, departmental and project budgets, budget requests, approvals and budget vs actual tracking">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.create', $department) }}" class="tich-btn tich-btn-primary">+ New budget</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Budget</th>
                        <th>Period</th>
                        <th>Department</th>
                        <th>Amount</th>
                        <th>Spent</th>
                        <th>Committed</th>
                        <th>Available</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="9" class="tich-table-empty">No budgets yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

