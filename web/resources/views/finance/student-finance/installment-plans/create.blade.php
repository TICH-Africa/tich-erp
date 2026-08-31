@extends('layouts.finance')

@section('title', 'New Installment Plan')

@section('finance-content')
    <x-page-toolbar title="New Installment Plan" meta="Create a new installment payment plan">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.installment-plans.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.student-finance.installment-plans.store') }}" class="tich-card tich-form-grid tich-form-grid--2">
        @csrf
        <div class="tich-form-group">
            <label class="tich-label" for="student_id">Student <span class="tich-text--danger">*</span></label>
            <select name="student_id" id="student_id" class="tich-input" required>
                <option value="">Loading students...</option>
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="invoice_id">Invoice <span class="tich-text--danger">*</span></label>
            <select name="invoice_id" id="invoice_id" class="tich-input" required>
                <option value="">Loading invoices...</option>
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="total_amount">Total Amount (KES) <span class="tich-text--danger">*</span></label>
            <input type="number" id="total_amount" name="total_amount" class="tich-input" step="0.01" placeholder="0.00" required>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="installment_count">Number of Installments <span class="tich-text--danger">*</span></label>
            <input type="number" id="installment_count" name="installment_count" class="tich-input" min="2" max="12" value="3" required>
        </div>

        <div class="tich-form-group" style="grid-column: 1 / -1;">
            <button type="submit" class="tich-btn tich-btn-primary">Create plan</button>
            <a href="{{ route('finance.student-finance.installment-plans.index') }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>

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
