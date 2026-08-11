@extends('layouts.finance')

@section('title', 'New Refund')

@section('finance-content')
    <x-page-toolbar title="New Refund" meta="Create a new refund request">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.refunds.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('finance.student-finance.refunds.store', ['department' => $department->id]) }}" class="tich-mt-4">
            @csrf
            <div class="tich-form-grid tich-form-grid--2">
                <div class="tich-form-group">
                    <label class="tich-label">Student</label>
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
                    <label class="tich-label">Payment</label>
                    <select name="payment_id" id="refund-payment" class="tich-input" required disabled>
                        <option value="">Select student first</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Invoice</label>
                    <select name="invoice_id" id="refund-invoice" class="tich-input" required disabled>
                        <option value="">Select student first</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Refund Amount (KES)</label>
                    <input type="number" name="amount" class="tich-input" step="0.01" required placeholder="0.00" />
                </div>
            </div>

            <div class="tich-form-group tich-mt-4">
                <label class="tich-label">Reason</label>
                <textarea name="reason" class="tich-input" rows="3" required placeholder="Explain the reason for this refund..."></textarea>
            </div>

            <div class="tich-inset-panel tich-mt-4">
                <p class="tich-caption">
                    <strong>Maker-checker rule:</strong> The person who creates this refund request must NOT approve their own refund.
                </p>
            </div>

            <div class="tich-form-group tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-primary">Create refund request</button>
                <a href="{{ route('finance.student-finance.refunds.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>

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
