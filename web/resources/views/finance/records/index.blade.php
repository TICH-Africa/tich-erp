@extends('layouts.finance')

@section('title', 'Finance Records')

@section('finance-content')
    @php($dept = $departmentParams ?? [])

    <x-page-toolbar
        title="Finance Records"
        meta="General ledger, treasury, accounts receivable/payable, budgeting, and institutional reporting"
    />

    <div class="tich-grid tich-grid--3 tich-mt-8">
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
@endsection
