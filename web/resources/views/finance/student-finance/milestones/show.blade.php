@extends('layouts.department')

@section('title', 'Payment Milestone')

@section('finance-content')
    <x-page-toolbar title="Payment Milestone" meta="Milestone details">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.milestones.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Student</p>
            <p class="tich-stat__value">{{ $milestone->student->fullName() ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Milestone</p>
            <p class="tich-stat__value">{{ ucfirst(str_replace('_', ' ', $milestone->milestone_type)) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Percentage</p>
            <p class="tich-stat__value">{{ $milestone->percentage }}%</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Status</p>
            <p class="tich-stat__value">
                <span class="tich-badge tich-badge--{{ match($milestone->status) {
                    'pending' => 'secondary',
                    'partial' => 'warning',
                    'paid' => 'success',
                    'overdue' => 'danger',
                    default => 'secondary',
                } }}">
                    {{ ucfirst($milestone->status) }}
                </span>
            </p>
        </div>
    </div>

    <div class="tich-grid tich-grid--3 tich-mb-8">
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Milestone Amount</p>
            <p class="tich-stat__value">KES {{ number_format($milestone->milestone_amount, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Paid Amount</p>
            <p class="tich-stat__value">KES {{ number_format($milestone->paid_amount, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Due Date</p>
            <p class="tich-stat__value">{{ $milestone->due_date?->format('d M Y') }}</p>
        </div>
    </div>
@endsection


