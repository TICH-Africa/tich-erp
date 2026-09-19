@extends('layouts.finance')

@section('title', 'New Installment Plan')

@section('finance-content')
    <x-page-toolbar title="New Installment Plan" meta="Create a new installment payment plan">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.installment-plans.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.student-finance.installment-plans.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">INS · Installment plan</div>
                    <div class="uf-amount-bar__sum">Split invoice into payments</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Plan Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="student_id">Student <span class="uf-req">*</span></label>
                            <select
                                name="student_id"
                                id="student_id"
                                required
                                class="{{ $errors->has('student_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Loading students...</option>
                            </select>
                            @error('student_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="invoice_id">Invoice <span class="uf-req">*</span></label>
                            <select
                                name="invoice_id"
                                id="invoice_id"
                                required
                                class="{{ $errors->has('invoice_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Loading invoices...</option>
                            </select>
                            @error('invoice_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="total_amount">Total Amount (KES) <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                id="total_amount"
                                name="total_amount"
                                step="0.01"
                                placeholder="0.00"
                                required
                                class="{{ $errors->has('total_amount') ? 'is-invalid' : '' }}"
                            >
                            @error('total_amount')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="installment_count">Number of Installments <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                id="installment_count"
                                name="installment_count"
                                min="2"
                                max="12"
                                value="3"
                                required
                                class="{{ $errors->has('installment_count') ? 'is-invalid' : '' }}"
                            >
                            @error('installment_count')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create plan</button>
                        <a href="{{ route('finance.student-finance.installment-plans.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const studentSelect = document.getElementById('student_id');
            const invoiceSelect = document.getElementById('invoice_id');

            function loadStudents() {
                fetch('{{ route('finance.api.students') }}')
                    .then(response => response.json())
                    .then(data => {
                        studentSelect.innerHTML = '<option value="">Select student</option>';
                        data.forEach(function(student) {
                            const option = document.createElement('option');
                            option.value = student.id;
                            option.textContent = student.text;
                            studentSelect.appendChild(option);
                        });
                    })
                    .catch(() => {
                        studentSelect.innerHTML = '<option value="">Failed to load students</option>';
                    });
            }

            function loadInvoices(studentId) {
                const url = new URL('{{ route('finance.api.invoices') }}');
                if (studentId) {
                    url.searchParams.set('student_id', studentId);
                }
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        invoiceSelect.innerHTML = '<option value="">Select invoice</option>';
                        data.forEach(function(invoice) {
                            const option = document.createElement('option');
                            option.value = invoice.id;
                            option.textContent = invoice.text;
                            invoiceSelect.appendChild(option);
                        });
                    })
                    .catch(() => {
                        invoiceSelect.innerHTML = '<option value="">Failed to load invoices</option>';
                    });
            }

            loadStudents();
            loadInvoices();

            studentSelect.addEventListener('change', function() {
                loadInvoices(this.value);
            });
        });
    </script>
@endsection
