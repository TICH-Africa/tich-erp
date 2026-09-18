@extends('layouts.finance')

@section('title', 'Payroll Integration')

@section('finance-content')
    <x-page-toolbar title="Payroll Integration" meta="Sync approved payroll data from HR/Payroll">
        <x-slot:actions>
            <a href="{{ route('finance.payroll-integration.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.payroll-integration.sync') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">PAY · Payroll integration</div>
                    <div class="uf-amount-bar__sum">Sync approved payroll data</div>
                </div>
                <span class="uf-badge">Sync</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Sync Parameters</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="period">Payroll period <span class="uf-req">*</span></label>
                            <input
                                type="month"
                                id="period"
                                name="period"
                                required
                                class="{{ $errors->has('period') ? 'is-invalid' : '' }}"
                            >
                            @error('period')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="source">Source <span class="uf-req">*</span></label>
                            <select
                                id="source"
                                name="source"
                                required
                                class="{{ $errors->has('source') ? 'is-invalid' : '' }}"
                            >
                                <option value="workpay">Workpay</option>
                            </select>
                            @error('source')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="gl_account_id">GL account <span class="uf-req">*</span></label>
                            <select
                                id="gl_account_id"
                                name="gl_account_id"
                                required
                                class="{{ $errors->has('gl_account_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select GL account</option>
                            </select>
                            @error('gl_account_id')
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
                        <button type="submit" class="uf-btn uf-btn-primary">Sync payroll</button>
                        <a href="{{ route('finance.payroll-integration.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
