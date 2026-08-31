@extends('layouts.finance')

@section('title', 'Issue credit memo')

@section('finance-content')
    <x-page-toolbar title="Issue credit memo" meta="Reduce invoice balance with a formal credit document" />

    <form method="post" action="{{ route('finance.ar.credit-memos.store') }}" class="tich-card tich-form-grid tich-mt-8">
        @csrf

        <div class="tich-form-row">
            <label class="tich-label" for="invoice_id">Invoice</label>
            <select id="invoice_id" name="invoice_id" class="tich-input" required>
                <option value="">Select invoice…</option>
                @foreach ($invoices as $invoice)
                    <option value="{{ $invoice->id }}" data-balance="{{ $invoice->balance }}" @selected(old('invoice_id') == $invoice->id)>
                        {{ $invoice->invoice_number }} - {{ $invoice->student?->displayName() }} - balance KES {{ number_format((float) $invoice->balance, 2) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="amount">Credit amount (KES)</label>
            <input type="number" id="amount" name="amount" class="tich-input" min="0.01" step="0.01" value="{{ old('amount') }}" required>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="reason">Reason</label>
            <textarea id="reason" name="reason" class="tich-input" rows="3" required>{{ old('reason') }}</textarea>
        </div>

        <div>
            <button type="submit" class="tich-btn tich-btn-primary">Issue credit memo</button>
        </div>
    </form>
@endsection
