@extends('layouts.finance')

@section('title', 'Edit invoice')

@section('finance-content')
    <x-page-toolbar title="Edit invoice" meta="Invoice {{ $invoice->invoice_number }}">
        <x-slot:actions>
            <a href="{{ route('finance.invoices.show', $invoice) }}" class="tich-btn tich-btn-ghost">Back to invoice</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.invoices.update', $invoice) }}" data-uf="ready">
            @csrf
            @method('PUT')

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">INV · {{ $invoice->invoice_number }}</div>
                    <div class="uf-amount-bar__sum">{{ $invoice->student?->displayName() }} · {{ $invoice->student?->registration_number }}</div>
                </div>
                <span class="uf-badge">{{ ucfirst($invoice->status) }}</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Bill details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="invoice_type">Invoice type <span class="uf-req">*</span></label>
                            <select
                                id="invoice_type"
                                name="invoice_type"
                                required
                                class="{{ $errors->has('invoice_type') ? 'is-invalid' : '' }}"
                            >
                                @foreach ($invoiceTypes as $key => $label)
                                    <option value="{{ $key }}" @selected(old('invoice_type', $invoice->invoice_type) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('invoice_type')
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
                                value="{{ old('amount', number_format((float) $invoice->amount, 2, '.', '')) }}"
                                required
                                class="{{ $errors->has('amount') ? 'is-invalid' : '' }}"
                            >
                            @error('amount')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            <span class="uf-hint">KES {{ number_format((float) $invoice->amount_paid, 2) }} has already been paid on this bill.</span>
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description <span class="uf-req">*</span></label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            required
                            class="{{ $errors->has('description') ? 'is-invalid' : '' }}"
                        >{{ old('description', $invoice->description) }}</textarea>
                        @error('description')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="due_date">Due date <span class="uf-req">*</span></label>
                            <input
                                type="date"
                                id="due_date"
                                name="due_date"
                                value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}"
                                required
                                class="{{ $errors->has('due_date') ? 'is-invalid' : '' }}"
                            >
                            @error('due_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label>Payments already recorded</label>
                            @forelse ($invoice->payments as $payment)
                                <p class="uf-hint">
                                    {{ $payment->payment_number }} · {{ $payment->payment_date?->format('d M Y') }} · KES {{ number_format((float) $payment->amount, 2) }}
                                    @can('finance.payments.manage')
                                        · <a href="{{ route('finance.payments.edit', $payment->id) }}">Edit this payment</a>
                                    @endcan
                                </p>
                            @empty
                                <p class="uf-hint">None yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Save</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Save changes</button>
                        <a href="{{ route('finance.invoices.show', $invoice) }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                    <p class="uf-hint">
                        Saving replaces the bill as it stands. Money already received stays against it, and
                        lowering the amount below what was paid leaves the difference on the student's account as
                        an excess payment that carries to the next term or semester.
                    </p>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
