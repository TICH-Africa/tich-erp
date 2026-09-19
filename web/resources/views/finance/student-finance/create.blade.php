@extends('layouts.finance')

@section('title', 'Student Finance')

@section('finance-content')
    <x-page-toolbar title="Student Finance" meta="Create student invoice">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.student-finance.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">SF · Student finance</div>
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
                            <label for="fee_structure_id">Fee structure <span class="uf-req">*</span></label>
                            <select
                                id="fee_structure_id"
                                name="fee_structure_id"
                                required
                                class="{{ $errors->has('fee_structure_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select fee structure</option>
                            </select>
                            @error('fee_structure_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="amount">Amount (KES) <span class="uf-req">*</span></label>
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
                        <label for="notes">Notes</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            placeholder="Optional notes..."
                            class="{{ $errors->has('notes') ? 'is-invalid' : '' }}"
                        ></textarea>
                        @error('notes')
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
                        <a href="{{ route('finance.student-finance.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
