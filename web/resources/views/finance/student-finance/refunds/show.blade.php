@extends('layouts.finance')

@section('title', 'Refund')

@section('finance-content')
    <x-page-toolbar title="Refund" meta="Refund request details">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.refunds.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Refund</p>
            <p class="tich-stat__value">{{ $refund->refund_number }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Student</p>
            <p class="tich-stat__value">{{ $refund->student->fullName() ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Invoice</p>
            <p class="tich-stat__value">{{ $refund->invoice->invoice_number ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Amount</p>
            <p class="tich-stat__value">KES {{ number_format($refund->amount, 2) }}</p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <div class="tich-card">
            <h3 class="tich-h4 tich-mb-4">Refund Details</h3>
            <p class="tich-text"><strong>Reason:</strong> {{ $refund->reason }}</p>
            <p class="tich-text"><strong>Status:</strong>
                <span class="tich-badge tich-badge--{{ match($refund->status) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'processed' => 'info',
                    default => 'secondary',
                } }}">
                    {{ ucfirst($refund->status) }}
                </span>
            </p>
            <p class="tich-text"><strong>Requested By:</strong> {{ $refund->requestedBy?->fullName() ?? ($refund->requested_by ?? 'N/A') }}</p>
            <p class="tich-text"><strong>Date:</strong> {{ $refund->created_at?->format('d M Y') }}</p>
            @if ($refund->payment)
                <p class="tich-text"><strong>Payment:</strong> {{ $refund->payment->payment_number ?? 'N/A' }}</p>
                <p class="tich-text"><strong>Payment Method:</strong> {{ ucfirst($refund->payment->payment_method ?? 'N/A') }}</p>
            @endif
            @if ($refund->approved_by)
                <p class="tich-text"><strong>Approved By:</strong> {{ $refund->approvedBy?->fullName() ?? 'N/A' }}</p>
            @endif
            @if ($refund->approved_at)
                <p class="tich-text"><strong>Approved At:</strong> {{ $refund->approved_at?->format('d M Y H:i') }}</p>
            @endif
        </div>
        <div class="tich-card">
            <h3 class="tich-h4 tich-mb-4">Student Info</h3>
            <p class="tich-text"><strong>Name:</strong> {{ $refund->student->fullName() ?? 'N/A' }}</p>
            <p class="tich-text"><strong>Reg No:</strong> {{ $refund->student->registration_number ?? 'N/A' }}</p>
            <p class="tich-text"><strong>Invoice:</strong> {{ $refund->invoice->invoice_number ?? 'N/A' }}</p>
        </div>
    </div>
@endsection


