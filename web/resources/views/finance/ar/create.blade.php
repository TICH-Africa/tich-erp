@extends('layouts.finance')

@section('title', 'Accounts Receivable')

@section('finance-content')
    <x-page-toolbar title="Accounts Receivable" meta="Create AR invoice">
        <x-slot:actions>
            <a href="{{ route('finance.ar.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.ar.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">AR · Accounts receivable</div>
                    <div class="uf-amount-bar__sum">Create student invoice</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Invoice Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="student_id">Student <span class="uf-req">*</span></label>
                            <select
                                id="student_id"
                                name="student_id"
                                required
                                class="{{ $errors->has('student_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select student</option>
                            </select>
                            @error('student_id')
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
                            <label for="due_date">Due date <span class="uf-req">*</span></label>
                            <input
                                type="date"
                                id="due_date"
                                name="due_date"
                                required
                                class="{{ $errors->has('due_date') ? 'is-invalid' : '' }}"
                            >
                            @error('due_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Optional notes..."
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
                        <button type="submit" class="uf-btn uf-btn-primary">Create invoice</button>
                        <a href="{{ route('finance.ar.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
