@extends('layouts.finance')

@section('title', 'Payroll Integration')

@section('department-content')
    <x-page-toolbar title="Payroll Integration" meta="Finance consumes approved payroll data from HR/Payroll (Workpay is the source of truth) and posts the matching accounting entries">
        <x-slot:actions>
            <a href="{{ route('finance.payroll-integration.sync', $department) }}" class="tich-btn tich-btn-primary">Sync payroll</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Journal entry</th>
                        <th>Posted at</th>
                        <th>Posted by</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="tich-table-empty">No payroll integrations yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

