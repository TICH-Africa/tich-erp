@extends('layouts.finance')

@section('title', 'Generate invoice')

@section('finance-content')
    <x-page-toolbar title="Generate invoice" meta="Automated billing from fee structures or manual line items">
        <x-slot:actions>
            <a href="{{ route('finance.invoices.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.invoices.store') }}" class="tich-card tich-form-grid">
        @csrf
        <div class="tich-form-row">
            <label class="tich-label" for="student_id">Student <span class="tich-text--danger">*</span></label>
            <select id="student_id" name="student_id" class="tich-input" required>
                <option value="">Select student</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected(old('student_id', request('student_id')) == $student->id)>
                        {{ $student->registration_number }} - {{ $student->displayName() }} ({{ $student->program?->program_name }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="fee_structure_id">From approved fee structure</label>
            <select id="fee_structure_id" name="fee_structure_id" class="tich-input">
                <option value="">Manual invoice</option>
                @foreach ($feeStructures as $feeStructure)
                    <option value="{{ $feeStructure->id }}" @selected(old('fee_structure_id') == $feeStructure->id)>
                        {{ $feeStructure->program?->program_name }} · {{ $feeStructure->academicYear?->year_label }} - Semester KES {{ number_format((float) $feeStructure->total_semester_fee, 2) }}
                    </option>
                @endforeach
            </select>
            <p class="tich-caption tich-mt-2">Select a fee structure to auto-populate the invoice, or leave as Manual to enter details below.</p>
        </div>

        <div id="fee-structure-options" class="tich-form-row" hidden>
            <label class="tich-label" for="fee_structure_charge">Charge to bill</label>
            <select id="fee_structure_charge" name="fee_structure_charge" class="tich-input">
                <option value="semester" @selected(old('fee_structure_charge', 'semester') === 'semester')>Semester charges</option>
                <option value="application" @selected(old('fee_structure_charge') === 'application')>Application fee (once, after approval)</option>
                <option value="qa_annual" @selected(old('fee_structure_charge') === 'qa_annual')>Quality assurance (annual)</option>
                <option value="indexing_nck" @selected(old('fee_structure_charge') === 'indexing_nck')>Indexing (NCK) - once per programme</option>
                <option value="graduation" @selected(old('fee_structure_charge') === 'graduation')>Graduation fees (post learning)</option>
            </select>
        </div>

        <div id="include-optional-row" class="tich-form-row" hidden>
            <label class="tich-label">
                <input type="checkbox" name="include_optional_charges" value="1" @checked(old('include_optional_charges'))>
                Include optional transport &amp; accommodation on semester invoice
            </label>
        </div>

        <div id="manual-invoice-fields">
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
                <textarea id="description" name="description" class="tich-input" rows="4" placeholder="Invoice description...">{{ old('description') }}</textarea>
            </div>
            <div class="tich-form-row">
                <label class="tich-label" for="amount">Amount (KES) <span class="tich-text--danger">*</span></label>
                <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="tich-input" placeholder="0.00" value="{{ old('amount') }}" required>
            </div>
        </div>

        <p class="tich-caption" style="grid-column: 1 / -1;">Invoice numbers follow the format [Registration Number] - 001 and are dispatched to the student portal and email automatically.</p>

        <div class="tich-form-row">
            <button type="submit" class="tich-btn tich-btn-primary">Generate &amp; dispatch</button>
            <a href="{{ route('finance.invoices.index') }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>

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
