@extends('layouts.finance')

@section('title', 'Fee Structure')

@section('finance-content')
    <x-page-toolbar title="Fee Structure" meta="Fee structure details">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.fee-structures.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Program</p>
            <p class="tich-stat__value">{{ $feeStructure->program->program_name ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Academic Year</p>
            <p class="tich-stat__value">{{ $feeStructure->academicYear->year_label ?? 'N/A' }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Semester</p>
            <p class="tich-stat__value">{{ $feeStructure->semester_number }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Status</p>
            <p class="tich-stat__value">
                <span class="tich-badge tich-badge--{{ $feeStructure->is_active ? 'success' : 'secondary' }}">
                    {{ $feeStructure->is_active ? 'Active' : 'Inactive' }}
                </span>
            </p>
        </div>
    </div>

    <div class="tich-grid tich-grid--3 tich-mb-8">
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Tuition Fee</p>
            <p class="tich-stat__value">KES {{ number_format($feeStructure->tuition_fee ?? 0, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Application Fee</p>
            <p class="tich-stat__value">KES {{ number_format($feeStructure->application_fee ?? 0, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Accommodation Fee</p>
            <p class="tich-stat__value">KES {{ number_format($feeStructure->accommodation_fee ?? 0, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Transport Fee</p>
            <p class="tich-stat__value">KES {{ number_format($feeStructure->transport_fee ?? 0, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Library Fee</p>
            <p class="tich-stat__value">KES {{ number_format($feeStructure->library_fee ?? 0, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Other Fees</p>
            <p class="tich-stat__value">KES {{ number_format($feeStructure->other_fees ?? 0, 2) }}</p>
        </div>
        <div class="tich-card tich-stat">
            <p class="tich-stat__label">Total Semester Fee</p>
            <p class="tich-stat__value">KES {{ number_format($feeStructure->total_semester_fee ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="tich-card">
        <h3 class="tich-h4 tich-mb-4">Additional Details</h3>
        <p class="tich-text"><strong>Effective From:</strong> {{ $feeStructure->effective_from?->format('d M Y') }}</p>
        <p class="tich-text"><strong>Approved By:</strong> {{ $feeStructure->approvedBy?->fullName() ?? 'N/A' }}</p>
        <p class="tich-text"><strong>Approved At:</strong> {{ $feeStructure->approved_at?->format('d M Y H:i') }}</p>
        @if ($feeStructure->notes)
            <p class="tich-text"><strong>Notes:</strong> {{ $feeStructure->notes }}</p>
        @endif
    </div>
@endsection


