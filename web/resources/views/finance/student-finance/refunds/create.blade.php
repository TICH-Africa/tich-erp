@extends('layouts.finance')

@section('title', 'New Refund')

@section('finance-content')
    <x-page-toolbar title="New Refund" meta="Create a new refund request">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.refunds.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.student-finance.refunds.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">REF · Refund request</div>
                    <div class="uf-amount-bar__sum">Maker-checker approval required</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Student &amp; Payment</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="refund-student">Student <span class="uf-req">*</span></label>
                            <select
                                name="student_id"
                                id="refund-student"
                                required
                                class="{{ $errors->has('student_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Search/select student</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}">
                                        {{ $student->applicant?->surname ?? '' }}, {{ $student->applicant?->first_name ?? '' }} ({{ $student->registration_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="refund-payment">Payment</label>
                            <select
                                name="payment_id"
                                id="refund-payment"
                                required
                                disabled
                                class="{{ $errors->has('payment_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select student first</option>
                            </select>
                            @error('payment_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="refund-invoice">Invoice</label>
                            <select
                                name="invoice_id"
                                id="refund-invoice"
                                required
                                disabled
                                class="{{ $errors->has('invoice_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select student first</option>
                            </select>
                            @error('invoice_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="amount">Refund Amount (KES) <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                step="0.01"
                                placeholder="0.00"
                                required
                                class="{{ $errors->has('amount') ? 'is-invalid' : '' }}"
                            >
                            @error('amount')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="reason">Reason <span class="uf-req">*</span></label>
                        <textarea
                            id="reason"
                            name="reason"
                            rows="4"
                            placeholder="Explain the reason for this refund..."
                            class="{{ $errors->has('reason') ? 'is-invalid' : '' }}"
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <p class="uf-hint"><strong>Maker-checker rule:</strong> The person who creates this refund request must NOT approve their own refund.</p>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create refund request</button>
                        <a href="{{ route('finance.student-finance.refunds.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

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
