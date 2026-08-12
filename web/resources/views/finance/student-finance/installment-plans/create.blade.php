@extends('layouts.finance')

@section('title', 'New Installment Plan')

@section('finance-content')
    <x-page-toolbar title="New Installment Plan" meta="Create a new installment payment plan">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.installment-plans.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('finance.student-finance.installment-plans.store', ['department' => $department->id]) }}" class="tich-mt-4">
            @csrf
            <div class="tich-form-grid tich-form-grid--2">
                <div class="tich-form-group">
                    <label class="tich-label">Student</label>
                    <select name="student_id" id="student_id" class="tich-input" required>
                        <option value="">Loading students...</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Invoice</label>
                    <select name="invoice_id" id="invoice_id" class="tich-input" required>
                        <option value="">Loading invoices...</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Total Amount (KES)</label>
                    <input type="number" name="total_amount" class="tich-input" step="0.01" required placeholder="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Number of Installments</label>
                    <input type="number" name="installment_count" class="tich-input" min="2" max="12" value="3" required />
                </div>
            </div>

            <div class="tich-form-group tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-primary">Create plan</button>
                <a href="{{ route('finance.student-finance.installment-plans.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentSelect = document.getElementById('student_id');
    const invoiceSelect = document.getElementById('invoice_id');

    function loadStudents() {
        fetch('{{ route('students.index') }}')
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
        const url = new URL('{{ route('invoices.index') }}');
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
