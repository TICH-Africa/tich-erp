@extends('layouts.finance')

@section('title', 'Student Finance')

@section('finance-content')
    <x-page-toolbar title="Student Finance" meta="Student accounts, fee structures, invoices, payments, receipts, adjustments, and clearance">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.invoices.create', ['department' => $department->id]) }}" class="tich-btn tich-btn-primary">+ New invoice</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-8">
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
