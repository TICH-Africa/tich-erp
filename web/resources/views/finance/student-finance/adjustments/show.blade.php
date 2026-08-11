@extends('layouts.finance')

@section('title', 'Financial Adjustment')

@section('finance-content')
    <x-page-toolbar title="Financial Adjustment" meta="Adjustment request details">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.adjustments.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Student</p>
            <p class="tich-stat__value">{{ $adjustment->student->fullName() ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Type</p>
            <p class="tich-stat__value">{{ ucfirst($adjustment->adjustment_type) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Amount</p>
            <p class="tich-stat__value">KES {{ number_format($adjustment->amount, 2) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Status</p>
            <p class="tich-stat__value">
                <span class="tich-badge tich-badge--{{ match($adjustment->status) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'secondary',
                } }}">
                    {{ ucfirst($adjustment->status) }}
                </span>
            </p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <div class="tich-card">
            <h3 class="tich-h4 tich-mb-4">Adjustment Details</h3>
            <p class="tich-text"><strong>Reason:</strong> {{ $adjustment->reason }}</p>
            <p class="tich-text"><strong>Invoice:</strong> {{ $adjustment->invoice->invoice_number ?? 'N/A' }}</p>
            <p class="tich-text"><strong>Requested By:</strong> {{ $adjustment->requestedBy?->fullName() ?? ($adjustment->requested_by ?? 'N/A') }}</p>
            <p class="tich-text"><strong>Date:</strong> {{ $adjustment->created_at?->format('d M Y') }}</p>
            @if ($adjustment->approved_by)
                <p class="tich-text"><strong>Approved By:</strong> {{ $adjustment->approvedBy?->fullName() ?? 'N/A' }}</p>
            @endif
            @if ($adjustment->approved_at)
                <p class="tich-text"><strong>Approved At:</strong> {{ $adjustment->approved_at?->format('d M Y H:i') }}</p>
            @endif
        </div>
        <div class="tich-card">
            <h3 class="tich-h4 tich-mb-4">Student Info</h3>
            <p class="tich-text"><strong>Name:</strong> {{ $adjustment->student->fullName() ?? 'N/A' }}</p>
            <p class="tich-text"><strong>Reg No:</strong> {{ $adjustment->student->registration_number ?? 'N/A' }}</p>
        </div>
    </div>
@endsection


