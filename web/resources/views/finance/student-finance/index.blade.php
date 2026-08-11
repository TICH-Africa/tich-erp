@extends('layouts.finance')

@section('title', 'Student Finance')

@section('finance-content')
    <x-page-toolbar title="Student Finance" meta="Student accounts, fee structures, invoices, payments, receipts, and refunds">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.invoices.create', ['department' => $department->id]) }}" class="tich-btn tich-btn-primary">+ New invoice</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2">
        <a href="{{ route('finance.student-finance.accounts.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Student Accounts</h3>
            <p class="tich-caption tich-mb-4">Automatic financial accounts for active students. Shows charges, payments, adjustments, scholarships/bursaries, outstanding balance, and clearance status.</p>
        </a>
        <a href="{{ route('finance.student-finance.fee-structures.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Fee Structures</h3>
            <p class="tich-caption tich-mb-4">Define fees applicable to a program, academic year, and semester. Contains configurable fee items and amounts.</p>
        </a>
        <a href="{{ route('finance.student-finance.invoices.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Invoices</h3>
            <p class="tich-caption tich-mb-4">Formal bills given to students. Supports tuition, application, supplementary, graduation, hostel, and other charges.</p>
        </a>
        <a href="{{ route('finance.student-finance.payments.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Payments & Receipts</h3>
            <p class="tich-caption tich-mb-4">Record student payments. M-Pesa Daraja callback validation, idempotency, payment allocation, and receipt generation.</p>
        </a>
        <a href="{{ route('finance.student-finance.refunds.index', ['department' => $department->id]) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Refunds</h3>
            <p class="tich-caption tich-mb-4">Refund workflow with maker-checker rule. The person who creates the refund request must NOT approve their own refund.</p>
        </a>
    </div>
@endsection


