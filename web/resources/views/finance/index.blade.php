@extends('layouts.finance')

@section('title', 'Finance')

@section('department-content')
    <x-page-toolbar title="Finance" meta="Student accounts, invoicing, procurement, payroll, and financial control">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.index', $department) }}" class="tich-btn tich-btn-primary">+ New invoice</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Outstanding AR</p>
            <p class="tich-stat__value">KES 0</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">AP Pending</p>
            <p class="tich-stat__value">0</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Pending payroll</p>
            <p class="tich-stat__value">0</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Open projects</p>
            <p class="tich-stat__value">0</p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2">
        <div class="tich-card">
            <h3 class="tich-h4">Student Finance</h3>
            <p class="tich-caption tich-mb-4">Fee structures, semester fees, invoices, payments, receipts, discounts, scholarships, bursaries, installment plans, refunds, credit balances and financial clearance.</p>
            <a href="{{ route('finance.student-finance.index', $department) }}" class="tich-btn tich-btn-ghost">Open</a>
        </div>
        <div class="tich-card">
            <h3 class="tich-h4">Accounts Receivable (AR)</h3>
            <p class="tich-caption tich-mb-4">Student invoices, outstanding balances, overdue invoices, payment allocation, receivable ageing and payment reminders.</p>
            <a href="{{ route('finance.ar.index', $department) }}" class="tich-btn tich-btn-ghost">Open</a>
        </div>
        <div class="tich-card">
            <h3 class="tich-h4">Accounts Payable (AP)</h3>
            <p class="tich-caption tich-mb-4">Supplier accounts, supplier invoices, verification, approval, payment and the supplier ledger. Uses the three-way match.</p>
            <a href="{{ route('finance.ap.index', $department) }}" class="tich-btn tich-btn-ghost">Open</a>
        </div>
        <div class="tich-card">
            <h3 class="tich-h4">General Ledger (GL)</h3>
            <p class="tich-caption tich-mb-4">Chart of Accounts, journal entries, debits, credits, account balances, Trial Balance, P&L, Balance Sheet and Cash Flow.</p>
            <a href="{{ route('finance.gl.index', $department) }}" class="tich-btn tich-btn-ghost">Open</a>
        </div>
        <div class="tich-card">
            <h3 class="tich-h4">Budgeting</h3>
            <p class="tich-caption tich-mb-4">Annual, departmental and project budgets, budget requests, approvals and budget vs actual tracking. Uses the multi-tier approval.</p>
            <a href="{{ route('finance.budgeting.index', $department) }}" class="tich-btn tich-btn-ghost">Open</a>
        </div>
        <div class="tich-card">
            <h3 class="tich-h4">Projects &amp; Donors</h3>
            <p class="tich-caption tich-mb-4">Donor profiles, project profiles, project budgets, donor invoices, donor disbursements, USD-to-KES conversion and donor accountability reports.</p>
            <a href="{{ route('finance.projects-donors.index', $department) }}" class="tich-btn tich-btn-ghost">Open</a>
        </div>
        <div class="tich-card">
            <h3 class="tich-h4">Payroll Integration</h3>
            <p class="tich-caption tich-mb-4">Finance consumes approved payroll data from HR/Payroll (Workpay is the source of truth) and posts the matching accounting entries.</p>
            <a href="{{ route('finance.payroll-integration.index', $department) }}" class="tich-btn tich-btn-ghost">Open</a>
        </div>
    </div>
@endsection

