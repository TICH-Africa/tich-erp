@extends('layouts.finance')

@section('title', 'Create Chart of Account')

@section('finance-content')
    <x-page-toolbar title="Create Chart of Account" meta="Add a new account to the chart of accounts">
        <x-slot:actions>
            <a href="{{ route('finance.gl.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.chart-of-accounts.store') }}" class="tich-card tich-mt-6" style="padding-left: 2rem; padding-right: 2rem;">
        @csrf

        <div class="tich-card__body">
            <div class="uf-form-section">
                <div class="uf-section-head">Account Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="account_code">Account Code <span class="uf-req">*</span></label>
                            <input type="text" id="account_code" name="account_code" class="uf-input" value="{{ old('account_code') }}" required maxlength="30" placeholder="e.g., 1114, 4100, 5100">
                            <p class="uf-hint tich-mt-1">Unique code (max 30 chars). Use numbers, letters, hyphens, dots only.</p>
                            @error('account_code')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="account_name">Account Name <span class="uf-req">*</span></label>
                            <input type="text" id="account_name" name="account_name" class="uf-input" value="{{ old('account_name') }}" required maxlength="200" placeholder="e.g., Bank - Savings Account">
                            @error('account_name')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="account_type">Account Type <span class="uf-req">*</span></label>
                            <select id="account_type" name="account_type" class="uf-input" required>
                                <option value="">Select Type</option>
                                @foreach(['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expense'] as $key => $label)
                                    <option value="{{ $key }}" @selected(old('account_type') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('account_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="currency">Currency <span class="uf-req">*</span></label>
                            <select id="currency" name="currency" class="uf-input" required>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency }}" @selected(old('currency', $defaultCurrency) === $currency)>{{ $currency }}</option>
                                @endforeach
                            </select>
                            <p class="uf-hint tich-mt-1">ISO 4217 code the account is denominated in.</p>
                            @error('currency')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="tich-flex tich-flex--end tich-gap-3 tich-mt-6 tich-pt-4 tich-border-t">
                <a href="{{ route('finance.gl.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
                <button type="submit" class="tich-btn tich-btn-primary">Create Account</button>
            </div>
        </div>
    </form>
@endsection