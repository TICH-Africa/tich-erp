@extends('layouts.finance')

@section('title', 'Employee Finance')

@section('finance-content')
    @php($dept = $departmentParams ?? [])

    <x-page-toolbar
        title="Employee Finance"
        meta="Payroll processing, statutory deductions, payroll reports, and payroll-to-ledger integration"
    />

    <div class="tich-grid tich-grid--3 tich-mt-8">
        @can('hr.staff.view')
            <a href="{{ route('hr.payroll.runs.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                <h3 class="tich-h4">Payroll runs</h3>
                <p class="tich-caption tich-mt-2">Create monthly batches, approve, generate payslips, and export KRA/NSSF/SHA filings.</p>
            </a>

            <a href="{{ route('hr.payroll.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                <h3 class="tich-h4">Live payroll preview</h3>
                <p class="tich-caption tich-mt-2">Current-month salary breakdown and payslip preview from staff records.</p>
            </a>
        @endcan

        @can('hr.manage_contracts')
            <a href="{{ route('hr.payroll.settings') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                <h3 class="tich-h4">Payroll settings</h3>
                <p class="tich-caption tich-mt-2">Tax bands, statutory rates, deduction types, and payroll configuration.</p>
            </a>
        @endcan

        @if ($dept !== [])
            <a href="{{ route('finance.payroll-integration.index', $dept) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                <h3 class="tich-h4">Payroll → GL integration</h3>
                <p class="tich-caption tich-mt-2">Post approved payroll batches to the general ledger.</p>
            </a>
        @endif
    </div>

    @unless (auth()->user()?->can('hr.staff.view'))
        <article class="tich-card tich-mt-8">
            <p class="tich-text">You do not have payroll access. Contact HR or system admin for HR staff view permission.</p>
        </article>
    @endunless
@endsection
