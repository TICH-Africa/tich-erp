@extends('layouts.finance')

@section('title', 'General Ledger')

@section('finance-content')
    <x-page-toolbar title="General Ledger" meta="Create journal entry">
        <x-slot:actions>
            <a href="{{ route('finance.gl.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.gl.journal.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">GL · General ledger</div>
                    <div class="uf-amount-bar__sum">Journal entry</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Entry Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="date">Date <span class="uf-req">*</span></label>
                            <input
                                type="date"
                                id="date"
                                name="date"
                                required
                                class="{{ $errors->has('date') ? 'is-invalid' : '' }}"
                            >
                            @error('date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="amount">Amount (KES) <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="amount"
                                name="amount"
                                placeholder="0.00"
                                required
                                class="{{ $errors->has('amount') ? 'is-invalid' : '' }}"
                            >
                            @error('amount')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="debit_account_id">Debit account <span class="uf-req">*</span></label>
                            <select
                                id="debit_account_id"
                                name="debit_account_id"
                                required
                                class="{{ $errors->has('debit_account_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select account</option>
                            </select>
                            @error('debit_account_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="credit_account_id">Credit account <span class="uf-req">*</span></label>
                            <select
                                id="credit_account_id"
                                name="credit_account_id"
                                required
                                class="{{ $errors->has('credit_account_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select account</option>
                            </select>
                            @error('credit_account_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description <span class="uf-req">*</span></label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Journal entry description..."
                            required
                            class="{{ $errors->has('description') ? 'is-invalid' : '' }}"
                        ></textarea>
                        @error('description')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Post entry</button>
                        <a href="{{ route('finance.gl.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
