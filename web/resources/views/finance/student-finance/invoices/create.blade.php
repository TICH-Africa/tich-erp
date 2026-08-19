@extends('layouts.finance')

@section('title', 'New Invoice')

@section('finance-content')
    <x-page-toolbar title="New Invoice" meta="Create a new student invoice (posts to general ledger)">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.invoices.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            <ul style="margin:0; padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('finance.student-finance.invoices.store', $department) }}" class="tich-card tich-form-grid tich-form-grid--2" id="invoice-create-form">
        @csrf
        <div class="tich-form-group">
            <label class="tich-label" for="student_id">Student <span class="tich-text--danger">*</span></label>
            <select id="student_id" name="student_id" class="tich-input" required>
                <option value="">Select student</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                        {{ $student->registration_number }} - {{ $student->displayName() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="fee_structure_id">Fee structure <span class="tich-caption">(optional)</span></label>
            <select id="fee_structure_id" name="fee_structure_id" class="tich-input">
                <option value="">Manual amount</option>
                @foreach ($feeStructures as $structure)
                    <option value="{{ $structure->id }}" data-amount="{{ $structure->total_semester_fee }}" @selected(old('fee_structure_id') == $structure->id)>
                        {{ $structure->program?->program_name ?? 'Programme' }} · {{ $structure->academicYear?->year_label ?? 'Year' }} - KES {{ number_format((float) $structure->total_semester_fee, 2) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="invoice_type">Invoice type <span class="tich-text--danger">*</span></label>
            <select id="invoice_type" name="invoice_type" class="tich-input" required>
                @foreach (['tuition', 'application', 'supplementary', 'graduation', 'hostel', 'other'] as $type)
                    <option value="{{ $type }}" @selected(old('invoice_type', 'tuition') === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="due_date">Due date <span class="tich-text--danger">*</span></label>
            <input type="date" id="due_date" name="due_date" class="tich-input" value="{{ old('due_date', now()->addDays(config('finance.invoice_due_days', 30))->toDateString()) }}" required>
        </div>
        <div class="tich-form-group" id="amount-group">
            <label class="tich-label" for="amount">Amount (KES) <span class="tich-text--danger">*</span></label>
            <input type="number" id="amount" name="amount" class="tich-input" min="0.01" step="0.01" value="{{ old('amount') }}" placeholder="Required when no fee structure selected" required>
        </div>

        <div class="tich-form-group" style="grid-column: 1 / -1;">
            <label class="tich-label" for="description">Description <span class="tich-text--danger">*</span></label>
            <textarea id="description" name="description" class="tich-input" rows="4" placeholder="Enter invoice description…">{{ old('description') }}</textarea>
        </div>

        <div class="tich-form-group" style="grid-column: 1 / -1;">
            <button type="submit" class="tich-btn tich-btn-primary">Create invoice</button>
            <a href="{{ route('finance.student-finance.invoices.index', $department) }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>

    <script>
        (function () {
            var feeSelect = document.getElementById('fee_structure_id');
            var amountGroup = document.getElementById('amount-group');
            var amountInput = amountGroup ? amountGroup.querySelector('input[name="amount"]') : null;

            function syncAmountField() {
                if (!feeSelect || !amountInput) return;
                var hasStructure = feeSelect.value !== '';
                amountInput.required = !hasStructure;
                amountGroup.style.display = hasStructure ? 'none' : '';
                if (hasStructure) {
                    var option = feeSelect.options[feeSelect.selectedIndex];
                    amountInput.value = option.dataset.amount || '';
                }
            }

            feeSelect?.addEventListener('change', syncAmountField);
            syncAmountField();
        })();
    </script>
@endsection
