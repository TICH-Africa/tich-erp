@extends('layouts.finance')

@section('title', 'Finance Records')

@section('finance-content')
    @php
        $chartData = $chartData ?? [];
        $dept = $departmentParams ?? [];
        $totalLedgerEntries = \App\Models\AccountLedger::count();
        $totalInvoices = \App\Models\Finance\Invoice::count();
        $totalJournalEntries = \App\Models\AccountLedger::where('transaction_type', 'journal_entry')->count();
        $totalGlAccounts = \App\Models\Finance\FeeStructure::count();
    @endphp

    <x-page-toolbar
        title="Finance Records"
        meta="General ledger, treasury, accounts receivable/payable, budgeting, and institutional reporting"
    />

    <div class="tich-stat-row tich-stat-row--4 tich-mt-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Ledger entries</p>
            <p class="tich-stat__value">{{ number_format($totalLedgerEntries) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Total invoices</p>
            <p class="tich-stat__value">{{ number_format($totalInvoices) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Journal entries</p>
            <p class="tich-stat__value">{{ number_format($totalJournalEntries) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Fee structures</p>
            <p class="tich-stat__value">{{ number_format($totalGlAccounts) }}</p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2" style="gap: 1.5rem; align-items: start;">
        <div>
            <section class="tich-dashboard-charts tich-mt-8" aria-label="Finance Records statistics charts" style="grid-template-columns: 1fr;">
                <article class="tich-card tich-chart-card">
                    <h3 class="tich-h3">Ledger by transaction type</h3>
                    <p class="tich-chart-card__meta">General ledger transaction breakdown</p>
                    <div class="tich-chart-card__canvas-wrap">
                        <canvas id="finance-chart-ledger-type" aria-label="Ledger by transaction type chart"></canvas>
                    </div>
                </article>

                <article class="tich-card tich-chart-card">
                    <h3 class="tich-h3">Invoices by type</h3>
                    <p class="tich-chart-card__meta">Invoice category breakdown</p>
                    <div class="tich-chart-card__canvas-wrap">
                        <canvas id="finance-chart-invoices-type" aria-label="Invoices by type chart"></canvas>
                    </div>
                </article>
            </section>
        </div>

        <div>
            <h3 class="tich-h3 tich-mb-4">Modules</h3>
            <div class="tich-grid tich-grid--1" style="gap: 0.75rem;">
                <a href="{{ route('finance.ledger.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                    <h3 class="tich-h4">General ledger</h3>
                    <p class="tich-caption tich-mt-2">Live ledger entries, account balances, and treasury movements.</p>
                </a>

                <a href="{{ route('finance.reports.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                    <h3 class="tich-h4">Financial reports</h3>
                    <p class="tich-caption tich-mt-2">Trial balance, balance sheet, profit &amp; loss, cashflow, and general ledger export.</p>
                </a>

                @if ($dept !== [])
                    <a href="{{ route('finance.ar.index', $dept) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Accounts receivable</h3>
                        <p class="tich-caption tich-mt-2">Outstanding invoices, ageing, payment allocation, and collection follow-up.</p>
                    </a>

                    <a href="{{ route('finance.ap.index', $dept) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Accounts payable</h3>
                        <p class="tich-caption tich-mt-2">Supplier invoices, verification, approval, and supplier payments.</p>
                    </a>

                    <a href="{{ route('finance.gl.index', $dept) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Chart of accounts</h3>
                        <p class="tich-caption tich-mt-2">COA structure, journal entries, debits, credits, and account balances.</p>
                    </a>

                    <a href="{{ route('finance.budgeting.index', $dept) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Budgeting</h3>
                        <p class="tich-caption tich-mt-2">Annual and departmental budgets with budget vs actual tracking.</p>
                    </a>

                    <a href="{{ route('finance.projects-donors.index', $dept) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Projects &amp; donors</h3>
                        <p class="tich-caption tich-mt-2">Donor projects, disbursements, and accountability reporting.</p>
                    </a>
                @endif

                @can('finance.payments.manage')
                    <a href="{{ route('finance.mpesa.settings') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
                        <h3 class="tich-h4">Treasury / M-Pesa</h3>
                        <p class="tich-caption tich-mt-2">Daraja credentials, STK push settings, and payment reconciliation.</p>
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script id="finance-dashboard-chart-data" type="application/json">@json($chartData)</script>
    <script src="{{ asset('js/tich-finance-dashboard.js') }}" defer></script>
@endsection
