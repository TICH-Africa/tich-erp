@extends('layouts.finance')

@section('title', 'Employee Finance')

@section('finance-content')
    @php
        $chartData = $chartData ?? [];
        $dept = $departmentParams ?? [];
        $totalRuns = \App\Models\PayrollRun::count();
        $totalStaffOnPayroll = \App\Models\Staff::whereIn('employment_status', ['active', 'on_leave'])->count();
        $approvedRuns = \App\Models\PayrollRun::where('status', 'approved')->count();
        $postedRuns = \App\Models\PayrollRun::where('status', 'posted')->count();
    @endphp

    <x-page-toolbar
        title="Employee Finance"
        meta="Payroll processing, statutory deductions, payroll reports, and payroll-to-ledger integration"
    />

    <div class="tich-stat-row tich-stat-row--4 tich-mt-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Total payroll runs</p>
            <p class="tich-stat__value">{{ number_format($totalRuns) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Staff on payroll</p>
            <p class="tich-stat__value">{{ number_format($totalStaffOnPayroll) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Approved runs</p>
            <p class="tich-stat__value">{{ number_format($approvedRuns) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Posted to GL</p>
            <p class="tich-stat__value">{{ number_format($postedRuns) }}</p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2" style="gap: 1.5rem; align-items: start;">
        <div>
            <section class="tich-dashboard-charts tich-mt-8" aria-label="Employee Finance statistics charts" style="grid-template-columns: 1fr;">
                <article class="tich-card tich-chart-card">
                    <h3 class="tich-h3">Payroll runs by status</h3>
                    <p class="tich-chart-card__meta">Run status breakdown</p>
                    <div class="tich-chart-card__canvas-wrap">
                        <canvas id="finance-chart-payroll-runs-status" aria-label="Payroll runs by status chart"></canvas>
                    </div>
                </article>

                <article class="tich-card tich-chart-card">
                    <h3 class="tich-h3">Staff by payroll scheme</h3>
                    <p class="tich-chart-card__meta">Active staff payroll scheme distribution</p>
                    <div class="tich-chart-card__canvas-wrap">
                        <canvas id="finance-chart-staff-payroll-scheme" aria-label="Staff by payroll scheme chart"></canvas>
                    </div>
                </article>
            </section>
        </div>

        <div>
            <h3 class="tich-h3 tich-mb-4">Modules</h3>
            <div class="tich-grid tich-grid--1" style="gap: 0.75rem;">
                @can('finance.read')
                    <a href="{{ route('finance.employee.payroll.runs.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Payroll runs</h3>
                        <p class="tich-caption tich-mt-2">Create monthly batches, approve, generate payslips, and export KRA/NSSF/SHA filings.</p>
                    </a>

                    <a href="{{ route('finance.employee.payroll.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Live payroll preview</h3>
                        <p class="tich-caption tich-mt-2">Current-month salary breakdown and payslip preview from staff records.</p>
                    </a>

                    <a href="{{ route('finance.employee.payroll.settings') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Payroll settings</h3>
                        <p class="tich-caption tich-mt-2">Tax bands, statutory rates, deduction types, and payroll configuration.</p>
                    </a>
                @endcan

                @can('hr.staff.view')
                    <a href="{{ route('finance.employee.payroll.runs.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Payroll runs</h3>
                        <p class="tich-caption tich-mt-2">Create monthly batches, approve, generate payslips, and export KRA/NSSF/SHA filings.</p>
                    </a>

                    <a href="{{ route('finance.employee.payroll.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Live payroll preview</h3>
                        <p class="tich-caption tich-mt-2">Current-month salary breakdown and payslip preview from staff records.</p>
                    </a>

                    <a href="{{ route('finance.employee.payroll.settings') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Payroll settings</h3>
                        <p class="tich-caption tich-mt-2">Tax bands, statutory rates, deduction types, and payroll configuration.</p>
                    </a>
                @endcan

                <a href="{{ route('finance.payroll-integration.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Payroll → GL integration</h3>
                        <p class="tich-caption tich-mt-2">Post approved payroll batches to the general ledger.</p>
                    </a>
            </div>
        </div>
    </div>

    @section('scripts')
        @parent
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script id="finance-dashboard-chart-data" type="application/json">@json($chartData)</script>
        <script src="{{ asset('js/tich-finance-dashboard.js') }}"></script>
    @endsection
@endsection
