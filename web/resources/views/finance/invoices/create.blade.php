@extends('layouts.finance')

@section('title', 'Generate invoice')

@section('finance-content')
    <x-page-toolbar title="Generate invoice" meta="Automated billing from fee structures or manual line items" />

    <form method="post" action="{{ route('finance.invoices.store') }}" class="tich-card tich-form-grid">
        @csrf
        <div class="tich-form-row">
            <label class="tich-label" for="student_id">Student</label>
            <select id="student_id" name="student_id" class="tich-input" required>
                <option value="">Select student</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected(old('student_id', request('student_id')) == $student->id)>
                        {{ $student->registration_number }} — {{ $student->displayName() }} ({{ $student->program?->program_name }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="fee_structure_id">From approved fee structure (optional)</label>
            <select id="fee_structure_id" name="fee_structure_id" class="tich-input">
                <option value="">Manual invoice</option>
                @foreach ($feeStructures as $feeStructure)
                    <option value="{{ $feeStructure->id }}" @selected(old('fee_structure_id') == $feeStructure->id)>
                        {{ $feeStructure->program?->program_name }} · {{ $feeStructure->academicYear?->year_label }} · Sem {{ $feeStructure->semester_number }} — KES {{ number_format((float) $feeStructure->total_semester_fee, 2) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="invoice_type">Invoice type</label>
            <select id="invoice_type" name="invoice_type" class="tich-input">
                @foreach ($invoiceTypes as $key => $label)
                    <option value="{{ $key }}" @selected(old('invoice_type', 'tuition') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="description">Description</label>
            <textarea id="description" name="description" class="tich-input" rows="3">{{ old('description') }}</textarea>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="amount">Amount (KES)</label>
            <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="tich-input" value="{{ old('amount') }}">
        </div>
        <p class="tich-caption">Invoice numbers follow the format [Registration Number] - 001 and are dispatched to the student portal and email automatically.</p>
        <div><button type="submit" class="tich-btn tich-btn-primary">Generate &amp; dispatch</button></div>
    </form>
@endsection
