@extends('layouts.finance')

@section('title', 'Projects & Donors')

@section('department-content')
    <x-page-toolbar title="Projects & Donors" meta="Donor profiles, project profiles, project budgets, donor invoices, donor disbursements, USD-to-KES conversion and donor accountability reports">
        <x-slot:actions>
            <a href="{{ route('finance.projects-donors.create', $department) }}" class="tich-btn tich-btn-primary">+ New project</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Donor</th>
                        <th>Budget (USD)</th>
                        <th>Disbursed (KES)</th>
                        <th>End date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="tich-table-empty">No projects yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

