@extends('layouts.finance')

@section('title', 'Edit payment')

@section('finance-content')
    <x-page-toolbar title="Edit payment" meta="Payment {{ $payment->payment_number }}">
        <x-slot:actions>
            @if ($payment->invoice)
                <a href="{{ route('finance.invoices.show', $payment->invoice) }}" class="tich-btn tich-btn-ghost">Back to invoice</a>
            @else
                <a href="{{ route('finance.payments.index') }}" class="tich-btn tich-btn-ghost">Back to payments</a>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    @php
        $invoice = $payment->invoice;
        $others = $invoice
            ? round($invoice->payments->where('id', '!=', $payment->id)->sum('amount'), 2)
            : 0.0;
        $creditApplied = $invoice
            ? round(max((float) $invoice->amount_paid - $others - (float) $payment->amount, 0), 2)
            : 0.0;
        $roomOnBill = $invoice
            ? round(max((float) $invoice->amount - $creditApplied - $others, 0), 2)
            : 0.0;
    @endphp

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.payments.update', $payment) }}" data-uf="ready">
            @csrf
            @method('PUT')

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">PAY · {{ $payment->payment_number }}</div>
                    <div class="uf-amount-bar__sum">
                        {{ $payment->student?->displayName() ?? $invoice?->student?->displayName() }}
                        · {{ $payment->student?->registration_number ?? $invoice?->student?->registration_number }}
                    </div>
                </div>
                <span class="uf-badge">{{ ucfirst($payment->status) }}</span>
            </div>

            @if ($invoice)
                <div class="uf-form-section">
                    <div class="uf-section-head">Invoice this payment was made against</div>
                    <div class="uf-section-body">
                        <div class="uf-form-grid-2">
                            <div class="uf-field">
                                <label>Invoice</label>
                                <p class="uf-hint">
                                    {{ $invoice->invoice_number }} ·
                                    {{ config('finance.invoice_types.'.$invoice->invoice_type, $invoice->invoice_type) }}
                                </p>
                            </div>
                            <div class="uf-field">
                                <label>Billed</label>
                                <p class="uf-hint">KES {{ number_format((float) $invoice->amount, 2) }}</p>
                            </div>
                            <div class="uf-field">
                                <label>Other payments on this invoice</label>
                                <p class="uf-hint">KES {{ number_format($others, 2) }}</p>
                            </div>
                            <div class="uf-field">
                                <label>Excess carried onto this invoice</label>
                                <p class="uf-hint">KES {{ number_format($creditApplied, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="uf-form-section">
                <div class="uf-section-head">Payment details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="amount">Amount received (KES) <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                id="amount"
                                name="amount"
                                value="{{ old('amount', number_format((float) $payment->amount, 2, '.', '')) }}"
                                required
                                class="{{ $errors->has('amount') ? 'is-invalid' : '' }}"
                            >
                            @error('amount')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            @if ($invoice)
                                <span class="uf-hint">
                                    KES {{ number_format($roomOnBill, 2) }} is still outstanding on
                                    {{ $invoice->invoice_number }} after the other payments. Anything above that stays
                                    on the student's account as an excess payment that carries to the next term or semester.
                                </span>
                            @endif
                        </div>
                        <div class="uf-field">
                            <label for="payment_method">Method <span class="uf-req">*</span></label>
                            <select
                                id="payment_method"
                                name="payment_method"
                                required
                                class="{{ $errors->has('payment_method') ? 'is-invalid' : '' }}"
                            >
                                @foreach ($paymentMethods as $key => $label)
                                    <option value="{{ $key }}" @selected(old('payment_method', $payment->payment_method) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            <span class="uf-hint">Changing the method moves the money from the old cash or bank account to the new one in the ledger.</span>
                        </div>
                        <div class="uf-field">
                            <label for="payment_date">Date payment was made <span class="uf-req">*</span></label>
                            <input
                                type="date"
                                id="payment_date"
                                name="payment_date"
                                value="{{ old('payment_date', $payment->payment_date?->format('Y-m-d')) }}"
                                required
                                class="{{ $errors->has('payment_date') ? 'is-invalid' : '' }}"
                            >
                            @error('payment_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="payment_reference">Reference</label>
                            <input
                                type="text"
                                id="payment_reference"
                                name="payment_reference"
                                placeholder="Payment reference"
                                value="{{ old('payment_reference', $payment->payment_reference) }}"
                                class="{{ $errors->has('payment_reference') ? 'is-invalid' : '' }}"
                            >
                            @error('payment_reference')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Save</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Save changes</button>
                        <a
                            href="{{ $invoice ? route('finance.invoices.show', $invoice) : route('finance.payments.index') }}"
                            class="uf-btn uf-btn-secondary"
                        >Cancel</a>
                    </div>
                    <p class="uf-hint">
                        Saving corrects the payment in place. The invoice re-opens or closes to match the new figure,
                        the ledger takes the difference, the receipt prints the corrected amount, and the old figure is
                        kept in the Finance audit trail.
                    </p>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
