@extends('layouts.finance')

@section('title', 'New Invoice')

@section('finance-content')
    <x-page-toolbar title="New Invoice" meta="Create a new student invoice (posts to general ledger)">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.invoices.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.student-finance.invoices.store') }}" data-uf="ready" id="invoice-create-form">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">INV · Student invoice</div>
                    <div class="uf-amount-bar__sum">Posts to general ledger</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Student &amp; Fee Structure</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="student_id">Student <span class="uf-req">*</span></label>
                            <select
                                id="student_id"
                                name="student_id"
                                required
                                class="{{ $errors->has('student_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select student</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                                        {{ $student->registration_number }} - {{ $student->displayName() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="fee_structure_id">Fee structure</label>
                            <select
                                id="fee_structure_id"
                                name="fee_structure_id"
                                class="{{ $errors->has('fee_structure_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Manual amount</option>
                                @foreach ($feeStructures as $structure)
                                    <option value="{{ $structure->id }}" data-amount="{{ $structure->total_semester_fee }}" @selected(old('fee_structure_id') == $structure->id)>
                                        {{ $structure->program?->program_name ?? 'Programme' }} · {{ $structure->academicYear?->year_label ?? 'Year' }} - KES {{ number_format((float) $structure->total_semester_fee, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fee_structure_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            <span class="uf-hint">Optional</span>
                        </div>
                        <div class="uf-field">
                            <label for="invoice_type">Invoice type <span class="uf-req">*</span></label>
                            <select
                                id="invoice_type"
                                name="invoice_type"
                                required
                                class="{{ $errors->has('invoice_type') ? 'is-invalid' : '' }}"
                            >
                                @foreach (['tuition', 'application', 'supplementary', 'graduation', 'hostel', 'other'] as $type)
                                    <option value="{{ $type }}" @selected(old('invoice_type', 'tuition') === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                            @error('invoice_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="due_date">Due date <span class="uf-req">*</span></label>
                            <input
                                type="date"
                                id="due_date"
                                name="due_date"
                                value="{{ old('due_date', now()->addDays(config('finance.invoice_due_days', 30))->toDateString()) }}"
                                required
                                class="{{ $errors->has('due_date') ? 'is-invalid' : '' }}"
                            >
                            @error('due_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field" id="amount-group">
                            <label for="amount">Amount (KES) <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                min="0.01"
                                step="0.01"
                                value="{{ old('amount') }}"
                                placeholder="Required when no fee structure selected"
                                required
                                class="{{ $errors->has('amount') ? 'is-invalid' : '' }}"
                            >
                            @error('amount')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description <span class="uf-req">*</span></label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Enter invoice description…"
                            class="{{ $errors->has('description') ? 'is-invalid' : '' }}"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create invoice</button>
                        <a href="{{ route('finance.student-finance.invoices.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

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
