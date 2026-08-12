@extends('layouts.finance')

@section('title', 'Receipt')

@section('finance-content')
    <x-page-toolbar title="Receipt" meta="Receipt details">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.receipts.download', ['department' => $department->id, 'id' => $receipt->id]) }}" class="tich-btn tich-btn-primary" target="_blank">Download PDF</a>
            <a href="{{ route('finance.student-finance.receipts.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Receipt</p>
            <p class="tich-stat__value">{{ $receipt->receipt_number }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Student</p>
            <p class="tich-stat__value">{{ $receipt->student->fullName() ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Invoice</p>
            <p class="tich-stat__value">{{ $receipt->invoice->invoice_number ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Amount</p>
            <p class="tich-stat__value">KES {{ number_format($receipt->amount, 2) }}</p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <div class="tich-card">
            <h3 class="tich-h4 tich-mb-4">Payment Details</h3>
            <p class="tich-text"><strong>Method:</strong> {{ ucfirst($receipt->payment_method) }}</p>
            <p class="tich-text"><strong>Reference:</strong> {{ $receipt->payment_reference ?? 'N/A' }}</p>
            <p class="tich-text"><strong>Issued At:</strong> {{ $receipt->issued_at?->format('d M Y H:i') }}</p>
        </div>
        <div class="tich-card">
            <h3 class="tich-h4 tich-mb-4">Student Info</h3>
            <p class="tich-text"><strong>Name:</strong> {{ $receipt->student->fullName() ?? 'N/A' }}</p>
            <p class="tich-text"><strong>Reg No:</strong> {{ $receipt->student->registration_number ?? 'N/A' }}</p>
        </div>
    </div>
@endsection


