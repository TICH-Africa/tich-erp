@extends('layouts.finance')

@section('title', 'Issue credit memo')

@section('finance-content')
    <x-page-toolbar title="Issue credit memo" meta="Reduce invoice balance with a formal credit document" />

    <div class="uf-form">
        <form method="post" action="{{ route('finance.ar.credit-memos.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">CM · Credit memo</div>
                    <div class="uf-amount-bar__sum">Reduce invoice balance</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Credit Details</div>
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
                                <option value="">Select invoice…</option>
                                @foreach ($invoices as $invoice)
                                    <option value="{{ $invoice->id }}" data-balance="{{ $invoice->balance }}" @selected(old('invoice_id') == $invoice->id)>
                                        {{ $invoice->invoice_number }} - {{ $invoice->student?->displayName() }} - balance KES {{ number_format((float) $invoice->balance, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('invoice_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="amount">Credit amount (KES) <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                min="0.01"
                                step="0.01"
                                value="{{ old('amount') }}"
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
                            rows="3"
                            required
                            class="{{ $errors->has('reason') ? 'is-invalid' : '' }}"
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Issue credit memo</button>
                        <a href="{{ route('finance.ar.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
