@extends('layouts.finance')

@section('title', 'Record payment')

@section('finance-content')
    <x-page-toolbar title="Record payment" meta="Record a payment against an open invoice">
        <x-slot:actions>
            <a href="{{ route('finance.payments.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.payments.store') }}" class="tich-card tich-form-grid">
        @csrf
        <div class="tich-form-row">
            <label class="tich-label" for="invoice_id">Invoice <span class="tich-text--danger">*</span></label>
            <select id="invoice_id" name="invoice_id" class="tich-input" required>
                <option value="">Select invoice</option>
                @foreach ($openInvoices as $openInvoice)
                    <option value="{{ $openInvoice->id }}" @selected(old('invoice_id', $invoice?->id) == $openInvoice->id)>
                        {{ $openInvoice->invoice_number }} - {{ $openInvoice->student?->displayName() }} - Balance KES {{ number_format((float) $openInvoice->balance, 2) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="amount">Amount (KES) <span class="tich-text--danger">*</span></label>
            <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="tich-input" placeholder="0.00" value="{{ old('amount', $invoice?->balance) }}" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="payment_method">Payment method <span class="tich-text--danger">*</span></label>
            <select id="payment_method" name="payment_method" class="tich-input" required>
                @foreach ($paymentMethods as $key => $label)
                    <option value="{{ $key }}" @selected(old('payment_method', 'mpesa') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="payment_reference">Reference</label>
            <input type="text" id="payment_reference" name="payment_reference" class="tich-input" placeholder="Payment reference" value="{{ old('payment_reference') }}">
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="transaction_channel_ref">Channel reference (M-Pesa / bank)</label>
            <input type="text" id="transaction_channel_ref" name="transaction_channel_ref" class="tich-input" placeholder="Channel reference" value="{{ old('transaction_channel_ref') }}">
        </div>
        <div class="tich-form-row">
            <button type="submit" class="tich-btn tich-btn-primary">Record payment</button>
            <a href="{{ route('finance.payments.index') }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>
@endsection
