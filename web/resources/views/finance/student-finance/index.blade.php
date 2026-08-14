@extends('layouts.finance')

@section('title', 'Student Finance')

@section('finance-content')
    @php
        $chartData = $chartData ?? [];
        $stats = $stats ?? [];
        $totalInvoices = $stats['pending_invoices'] ?? \App\Models\Finance\Invoice::count();
        $totalPayments = \App\Models\Finance\Payment::count();
        $totalAccounts = $stats['total_accounts'] ?? \App\Models\Finance\StudentAccount::count();
        $outstandingBalance = $stats['outstanding_balance'] ?? \App\Models\Finance\StudentAccount::sum('outstanding_balance');
    @endphp

    <x-page-toolbar title="Student Finance" meta="Student accounts, fee structures, invoices, payments, receipts, adjustments, and clearance">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.invoices.create', ['department' => $department->id]) }}" class="tich-btn tich-btn-primary">+ New invoice</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-stat-row--4 tich-mt-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Total invoices</p>
            <p class="tich-stat__value">{{ number_format($totalInvoices) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Total payments</p>
            <p class="tich-stat__value">{{ number_format($totalPayments) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Student accounts</p>
            <p class="tich-stat__value">{{ number_format($totalAccounts) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Outstanding balance</p>
            <p class="tich-stat__value">KES {{ number_format($outstandingBalance, 2) }}</p>
        </div>
    </div>

    <section class="tich-dashboard-charts tich-mt-8" aria-label="Student Finance statistics charts" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
        <article class="tich-card tich-chart-card">
            <h3 class="tich-h3">Invoices by status</h3>
            <p class="tich-chart-card__meta">Invoice status breakdown</p>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="finance-chart-invoices-status" aria-label="Invoices by status chart"></canvas>
            </div>
        </article>

        <article class="tich-card tich-chart-card">
            <h3 class="tich-h3">Payments by method</h3>
            <p class="tich-chart-card__meta">Payment channel breakdown</p>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="finance-chart-payments-method" aria-label="Payments by method chart"></canvas>
            </div>
        </article>

        <article class="tich-card tich-chart-card">
            <h3 class="tich-h3">Accounts by clearance</h3>
            <p class="tich-chart-card__meta">Student account clearance status</p>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="finance-chart-accounts-clearance" aria-label="Accounts by clearance chart"></canvas>
            </div>
        </article>
    </section>

    <h3 class="tich-h3 tich-mt-8 tich-mb-4">Modules</h3>
    <div class="tich-grid tich-grid--2" style="gap: 0.75rem;">
        <a href="{{ route('finance.student-finance.accounts.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Student accounts</h3>
            <p class="tich-caption tich-mt-2">Charges, payments, credits, outstanding balance, and clearance status.</p>
        </a>
        <a href="{{ route('finance.student-finance.fee-structures.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Fee structures</h3>
            <p class="tich-caption tich-mt-2">Programme, year, and semester fee schedules.</p>
        </a>
        <a href="{{ route('finance.student-finance.invoices.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Invoices</h3>
            <p class="tich-caption tich-mt-2">Tuition, application, exam, graduation, and other student bills.</p>
        </a>
        <a href="{{ route('finance.student-finance.payments.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Payments</h3>
            <p class="tich-caption tich-mt-2">M-Pesa, bank, cash, and other payment channels.</p>
        </a>
        <a href="{{ route('finance.student-finance.receipts.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Receipts</h3>
            <p class="tich-caption tich-mt-2">Official receipts issued against student payments.</p>
        </a>
        <a href="{{ route('finance.student-finance.adjustments.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Adjustments</h3>
            <p class="tich-caption tich-mt-2">Scholarships, bursaries, and fee waivers.</p>
        </a>
        <a href="{{ route('finance.student-finance.installment-plans.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Installment plans</h3>
            <p class="tich-caption tich-mt-2">Structured payment plans for large balances.</p>
        </a>
        <a href="{{ route('finance.student-finance.milestones.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Payment milestones</h3>
            <p class="tich-caption tich-mt-2">50% registration, 75% mid-semester, 100% before finals - auto-tracked.</p>
        </a>
    </div>
@endsection

@section('scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script id="finance-dashboard-chart-data" type="application/json">@json($chartData)</script>
    <script>
        console.log('Student Finance chartData:', @json($chartData));
        console.log('Student Finance Chart.js loaded:', typeof Chart !== 'undefined');
        console.log('Student Finance dataEl:', document.getElementById('finance-dashboard-chart-data'));
    </script>
    <script src="{{ asset('js/tich-finance-dashboard.js') }}"></script>
@endsection
