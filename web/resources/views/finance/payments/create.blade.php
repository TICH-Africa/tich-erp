@extends('layouts.finance')

@section('title', 'Record payment')

@section('finance-content')
    <x-page-toolbar title="Record payment" meta="Record a payment against an open invoice">
        <x-slot:actions>
            <a href="{{ route('finance.payments.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.payments.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">PAY · Record payment</div>
                    <div class="uf-amount-bar__sum">Payment against open invoice</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Invoice &amp; Amount</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="invoice_id">Invoice <span class="uf-req">*</span></label>
                            <select
                                id="invoice_id"
                                name="invoice_id"
                                required
                                class="{{ $errors->has('invoice_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select invoice</option>
                                @foreach ($openInvoices as $openInvoice)
                                    <option value="{{ $openInvoice->id }}" @selected(old('invoice_id', $invoice?->id) == $openInvoice->id)>
                                        {{ $openInvoice->invoice_number }} - {{ $openInvoice->student?->displayName() }} - Balance KES {{ number_format((float) $openInvoice->balance, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('invoice_id')
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
                                value="{{ old('amount', $invoice?->balance) }}"
                                required
                                class="{{ $errors->has('amount') ? 'is-invalid' : '' }}"
                            >
                            @error('amount')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Payment Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="payment_method">Payment method <span class="uf-req">*</span></label>
                            <select
                                id="payment_method"
                                name="payment_method"
                                required
                                class="{{ $errors->has('payment_method') ? 'is-invalid' : '' }}"
                            >
                                @foreach ($paymentMethods as $key => $label)
                                    <option value="{{ $key }}" @selected(old('payment_method', 'mpesa') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')
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
                                value="{{ old('payment_reference') }}"
                                class="{{ $errors->has('payment_reference') ? 'is-invalid' : '' }}"
                            >
                            @error('payment_reference')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="transaction_channel_ref">Channel reference (M-Pesa / bank)</label>
                            <input
                                type="text"
                                id="transaction_channel_ref"
                                name="transaction_channel_ref"
                                placeholder="Channel reference"
                                value="{{ old('transaction_channel_ref') }}"
                                class="{{ $errors->has('transaction_channel_ref') ? 'is-invalid' : '' }}"
                            >
                            @error('transaction_channel_ref')
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
                        <button type="submit" class="uf-btn uf-btn-primary">Record payment</button>
                        <a href="{{ route('finance.payments.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
