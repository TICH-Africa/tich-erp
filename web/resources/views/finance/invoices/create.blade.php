@extends('layouts.finance')

@section('title', 'Generate invoice')

@section('finance-content')
    <x-page-toolbar title="Generate invoice" meta="Automated billing from fee structures or manual line items">
        <x-slot:actions>
            <a href="{{ route('finance.invoices.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.invoices.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">INV · Generate invoice</div>
                    <div class="uf-amount-bar__sum">Automated billing &amp; dispatch</div>
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
                                    <option value="{{ $student->id }}" @selected(old('student_id', request('student_id')) == $student->id)>
                                        {{ $student->registration_number }} - {{ $student->displayName() }} ({{ $student->program?->program_name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="fee_structure_id">From approved fee structure</label>
                            <select
                                id="fee_structure_id"
                                name="fee_structure_id"
                                class="{{ $errors->has('fee_structure_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Manual invoice</option>
                                @foreach ($feeStructures as $feeStructure)
                                    <option value="{{ $feeStructure->id }}" @selected(old('fee_structure_id') == $feeStructure->id)>
                                        {{ $feeStructure->program?->program_name }} · {{ $feeStructure->academicYear?->year_label }} - Semester KES {{ number_format((float) $feeStructure->total_semester_fee, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fee_structure_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            <span class="uf-hint">Select a fee structure to auto-populate the invoice, or leave as Manual to enter details below.</span>
                        </div>
                    </div>
                    <div id="fee-structure-options" class="uf-field" hidden>
                        <label for="fee_structure_charge">Charge to bill</label>
                        <select
                            id="fee_structure_charge"
                            name="fee_structure_charge"
                            class="{{ $errors->has('fee_structure_charge') ? 'is-invalid' : '' }}"
                        >
                            <option value="semester" @selected(old('fee_structure_charge', 'semester') === 'semester')>Semester charges</option>
                            <option value="application" @selected(old('fee_structure_charge') === 'application')>Application fee (once, after approval)</option>
                            <option value="qa_annual" @selected(old('fee_structure_charge') === 'qa_annual')>Quality assurance (annual)</option>
                            <option value="indexing_nck" @selected(old('fee_structure_charge') === 'indexing_nck')>Indexing (NCK) - once per programme</option>
                            <option value="graduation" @selected(old('fee_structure_charge') === 'graduation')>Graduation fees (post learning)</option>
                        </select>
                        @error('fee_structure_charge')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div id="include-optional-row" class="uf-field" hidden>
                        <label>
                            <input type="checkbox" name="include_optional_charges" value="1" @checked(old('include_optional_charges'))>
                            Include optional transport &amp; accommodation on semester invoice
                        </label>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Manual Invoice Details</div>
                <div class="uf-section-body">
                    <div id="manual-invoice-fields">
                        <div class="uf-form-grid-2">
                            <div class="uf-field">
                                <label for="invoice_type">Invoice type</label>
                                <select
                                    id="invoice_type"
                                    name="invoice_type"
                                    class="{{ $errors->has('invoice_type') ? 'is-invalid' : '' }}"
                                >
                                    @foreach ($invoiceTypes as $key => $label)
                                        <option value="{{ $key }}" @selected(old('invoice_type', 'tuition') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('invoice_type')
                                    <span class="uf-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="uf-field">
                                <label for="amount">Amount (KES) <span class="uf-req">*</span></label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    id="amount"
                                    name="amount"
                                    placeholder="0.00"
                                    value="{{ old('amount') }}"
                                    required
                                    class="{{ $errors->has('amount') ? 'is-invalid' : '' }}"
                                >
                                @error('amount')
                                    <span class="uf-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="uf-field">
                            <label for="description">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Invoice description..."
                                class="{{ $errors->has('description') ? 'is-invalid' : '' }}"
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <p class="uf-hint">Invoice numbers follow the format [Registration Number] - 001 and are dispatched to the student portal and email automatically.</p>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Generate &amp; dispatch</button>
                        <a href="{{ route('finance.invoices.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <script>
        (function () {
            const feeSelect = document.getElementById('fee_structure_id');
            const chargeRow = document.getElementById('fee-structure-options');
            const optionalRow = document.getElementById('include-optional-row');
            const manualFields = document.getElementById('manual-invoice-fields');
            const chargeSelect = document.getElementById('fee_structure_charge');

            function sync() {
                const fromStructure = feeSelect.value !== '';
                chargeRow.hidden = !fromStructure;
                manualFields.hidden = fromStructure;
                optionalRow.hidden = !fromStructure || chargeSelect.value !== 'semester';
            }

            feeSelect.addEventListener('change', sync);
            chargeSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
