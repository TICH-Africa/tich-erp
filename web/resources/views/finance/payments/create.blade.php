@extends('layouts.finance')

@section('title', 'Record payment')

@section('finance-content')
    <x-page-toolbar title="Record payment" />

    <form method="post" action="{{ route('finance.payments.store') }}" class="tich-card tich-form-grid">
        @csrf
        <div class="tich-form-row">
            <label class="tich-label" for="invoice_id">Invoice</label>
            <select id="invoice_id" name="invoice_id" class="tich-input" required>
                @foreach ($openInvoices as $openInvoice)
                    <option value="{{ $openInvoice->id }}" @selected(old('invoice_id', $invoice?->id) == $openInvoice->id)>
                        {{ $openInvoice->invoice_number }} — {{ $openInvoice->student?->displayName() }} — Balance KES {{ number_format((float) $openInvoice->balance, 2) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="amount">Amount (KES)</label>
            <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="tich-input" value="{{ old('amount', $invoice?->balance) }}" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="payment_method">Payment method</label>
            <select id="payment_method" name="payment_method" class="tich-input" required>
                @foreach ($paymentMethods as $key => $label)
                    <option value="{{ $key }}" @selected(old('payment_method', 'mpesa') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="payment_reference">Reference</label>
            <input type="text" id="payment_reference" name="payment_reference" class="tich-input" value="{{ old('payment_reference') }}">
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="transaction_channel_ref">Channel reference (M-Pesa / bank)</label>
            <input type="text" id="transaction_channel_ref" name="transaction_channel_ref" class="tich-input" value="{{ old('transaction_channel_ref') }}">
        </div>
        <div><button type="submit" class="tich-btn tich-btn-primary">Record payment</button></div>
    </form>
@endsection
