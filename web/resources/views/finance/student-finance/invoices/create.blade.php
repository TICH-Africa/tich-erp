@extends('layouts.finance')

@section('title', 'New Invoice')

@section('finance-content')
    <x-page-toolbar title="New Invoice" meta="Create a new student invoice (posts to general ledger)">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.invoices.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('finance.student-finance.invoices.store', $department) }}" class="tich-mt-4" id="invoice-create-form">
            @csrf
            <div class="tich-form-grid tich-form-grid--2">
                <div class="tich-form-group">
                    <label class="tich-label">Student</label>
                    <select name="student_id" class="tich-input" required>
                        <option value="">Select student</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                                {{ $student->registration_number }} — {{ $student->displayName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Fee structure <span class="tich-caption">(optional)</span></label>
                    <select name="fee_structure_id" class="tich-input" id="fee-structure-select">
                        <option value="">Manual amount</option>
                        @foreach ($feeStructures as $structure)
                            <option value="{{ $structure->id }}" data-amount="{{ $structure->total_semester_fee }}" @selected(old('fee_structure_id') == $structure->id)>
                                {{ $structure->program?->program_name ?? 'Programme' }} · {{ $structure->academicYear?->year_label ?? 'Year' }} — KES {{ number_format((float) $structure->total_semester_fee, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Invoice type</label>
                    <select name="invoice_type" class="tich-input" required>
                        @foreach (['tuition', 'application', 'supplementary', 'graduation', 'hostel', 'other'] as $type)
                            <option value="{{ $type }}" @selected(old('invoice_type', 'tuition') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Due date</label>
                    <input type="date" name="due_date" class="tich-input" value="{{ old('due_date', now()->addDays(config('finance.invoice_due_days', 30))->toDateString()) }}" required />
                </div>
                <div class="tich-form-group" id="amount-group">
                    <label class="tich-label">Amount (KES)</label>
                    <input type="number" name="amount" class="tich-input" min="0.01" step="0.01" value="{{ old('amount') }}" placeholder="Required when no fee structure selected" />
                </div>
            </div>

            <div class="tich-form-group tich-mt-4">
                <label class="tich-label">Description</label>
                <textarea name="description" class="tich-input" rows="3" required placeholder="Enter invoice description…">{{ old('description') }}</textarea>
            </div>

            <div class="tich-form-group tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-primary">Create invoice</button>
                <a href="{{ route('finance.student-finance.invoices.index', $department) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>

    <script>
        (function () {
            var feeSelect = document.getElementById('fee-structure-select');
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
