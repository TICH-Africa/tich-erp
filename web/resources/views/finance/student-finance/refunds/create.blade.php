@extends('layouts.finance')

@section('title', 'New Refund')

@section('finance-content')
    <x-page-toolbar title="New Refund" meta="Create a new refund request">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.refunds.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.student-finance.refunds.store') }}" class="tich-card tich-form-grid tich-form-grid--2">
        @csrf
        <div class="tich-form-group">
            <label class="tich-label" for="refund-student">Student <span class="tich-text--danger">*</span></label>
            <select name="student_id" id="refund-student" class="tich-input" required>
                <option value="">Search/select student</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">
                        {{ $student->applicant?->surname ?? '' }}, {{ $student->applicant?->first_name ?? '' }} ({{ $student->registration_number }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="refund-payment">Payment</label>
            <select name="payment_id" id="refund-payment" class="tich-input" required disabled>
                <option value="">Select student first</option>
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="refund-invoice">Invoice</label>
            <select name="invoice_id" id="refund-invoice" class="tich-input" required disabled>
                <option value="">Select student first</option>
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="amount">Refund Amount (KES) <span class="tich-text--danger">*</span></label>
            <input type="number" id="amount" name="amount" class="tich-input" step="0.01" placeholder="0.00" required>
        </div>

        <div class="tich-form-group" style="grid-column: 1 / -1;">
            <label class="tich-label" for="reason">Reason <span class="tich-text--danger">*</span></label>
            <textarea id="reason" name="reason" class="tich-input" rows="4" placeholder="Explain the reason for this refund...">{{ old('reason') }}</textarea>
        </div>

        <div class="tich-inset-panel" style="grid-column: 1 / -1;">
            <p class="tich-caption">
                <strong>Maker-checker rule:</strong> The person who creates this refund request must NOT approve their own refund.
            </p>
        </div>

        <div class="tich-form-group" style="grid-column: 1 / -1;">
            <button type="submit" class="tich-btn tich-btn-primary">Create refund request</button>
            <a href="{{ route('finance.student-finance.refunds.index') }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>

    @push('scripts')
        <script>
            const payments = @json($payments);
            const invoices = @json($invoices);

            function toOption(text, value) {
                return `<option value="${value}">${text}</option>`;
            }

            document.getElementById('refund-student').addEventListener('change', function () {
                const studentId = this.value;
                const paymentSelect = document.getElementById('refund-payment');
                const invoiceSelect = document.getElementById('refund-invoice');

                paymentSelect.innerHTML = '<option value="">Select payment</option>';
                invoiceSelect.innerHTML = '<option value="">Select invoice</option>';

                if (!studentId) {
                    paymentSelect.disabled = true;
                    invoiceSelect.disabled = true;
                    return;
                }

                const studentPayments = payments.filter(p => p.invoice && p.invoice.student && p.invoice.student.id == studentId);
                const studentInvoices = invoices.filter(i => i.student && i.student.id == studentId);

                studentPayments.forEach(payment => {
                    paymentSelect.innerHTML += toOption(`${payment.payment_number} - ${payment.invoice?.invoice_number ?? 'N/A'} - KES ${parseFloat(payment.amount).toFixed(2)} (${payment.payment_date || ''})`, payment.id);
                });

                studentInvoices.forEach(invoice => {
                    invoiceSelect.innerHTML += toOption(`${invoice.invoice_number} - ${invoice.status} - KES ${parseFloat(invoice.balance || 0).toFixed(2)}`, invoice.id);
                });

                paymentSelect.disabled = false;
                invoiceSelect.disabled = false;
            });
        </script>
    @endpush
@endsection
