@extends('layouts.finance')

@section('title', $parentAccount ? 'Create Child Account' : 'Create Main Account')

@section('finance-content')
    <x-page-toolbar title="{{ $parentAccount ? 'Add Child Account' : 'Create Main Account' }}" meta="{{ $parentAccount ? 'New child of ' . $parentAccount->account_code . ' ' . $parentAccount->account_name : 'Add a new main account to the chart of accounts' }}">
        <x-slot:actions>
            @if ($parentAccount)
                <a href="{{ route('finance.chart-of-accounts.show', $parentAccount) }}" class="tich-btn tich-btn-ghost">Parent account</a>
            @endif
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

    @if ($parentAccount)
        <div class="tich-alert tich-alert--info tich-mt-4">
            <strong>Parent account:</strong>
            <a href="{{ route('finance.chart-of-accounts.show', $parentAccount) }}">
                <code>{{ $parentAccount->account_code }}</code> - {{ $parentAccount->account_name }}
            </a>
            <br>
            <span class="tich-text--sm">The child code must start with the parent code, e.g. <code>{{ $parentAccount->account_code }}.01</code>.</span>
        </div>
    @endif

    <form method="POST" action="{{ route('finance.chart-of-accounts.store') }}" class="tich-card tich-mt-6" style="padding-left: 2rem; padding-right: 2rem;">
        @csrf

        <div class="tich-card__body">
            <div class="uf-form-section">
                <div class="uf-section-head">Account Details</div>
                <div class="uf-section-body">
                    @if ($parentAccount)
                        <input type="hidden" name="parent_account_code" value="{{ $parentAccount->account_code }}">
                    @endif

                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="account_code">Account Code <span class="uf-req">*</span></label>
                            <input type="text" id="account_code" name="account_code" class="uf-input"
                                   value="{{ old('account_code', $suggestedCode) }}" required maxlength="30"
                                   placeholder="{{ $suggestedCode ?? 'e.g., 1114, 4100, 5100' }}"
                                   @if ($parentAccount) pattern="[0-9A-Za-z\-\.]+" @endif>
                            <p class="uf-hint tich-mt-1">
                                @if ($parentAccount)
                                    Child code of {{ $parentAccount->account_code }}. Use the parent code plus a decimal part.
                                @else
                                    Unique code (max 30 chars). Use numbers, letters, hyphens or dots only.
                                @endif
                            </p>
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
                            <select id="account_type" name="account_type" class="uf-input" required @if ($parentAccount) disabled @endif>
                                @foreach($typeGroups as $group => $options)
                                    <optgroup label="{{ $group }}">
                                        @foreach($options as $option)
                                            <option value="{{ $option['label'] }}" @selected(old('account_type', $selectedTypeLabel) === $option['label'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @if ($parentAccount)
                                <input type="hidden" name="account_type" value="{{ old('account_type', $selectedTypeLabel) }}">
                                <p class="uf-hint tich-mt-1 tich-text--muted">A child must use the same type as its parent.</p>
                            @else
                                <p class="uf-hint tich-mt-1">Grouped by assets, liabilities, equity, revenue and expenses.</p>
                            @endif
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

                        @unless ($parentAccount)
                            <div class="uf-field">
                                <label for="parent_account_code">Parent Account</label>
                                <select id="parent_account_code" name="parent_account_code" class="uf-input">
                                    <option value="">None - this is a main account</option>
                                    @foreach($parentAccounts as $option)
                                        <option value="{{ $option->account_code }}" @selected(old('parent_account_code') === $option->account_code)>
                                            {{ $option->account_code }} - {{ $option->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="uf-hint tich-mt-1">Leave empty for a main account. Child codes must start with the parent code.</p>
                                @error('parent_account_code')
                                    <span class="uf-error">{{ $message }}</span>
                                @enderror
                            </div>
                        @endunless
                    </div>
                </div>
            </div>

            <div class="uf-form-section tich-mt-6">
                <div class="uf-section-head">Opening Balance</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="opening_balance">Balance Amount</label>
                            <input type="number" id="opening_balance" name="opening_balance" class="uf-input" step="0.01" min="0"
                                   value="{{ old('opening_balance') }}" placeholder="0.00">
                            <p class="uf-hint tich-mt-1">
                                The actual money value in the account currency. Leave at 0 if the account starts empty.
                            </p>
                            @error('opening_balance')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="balance_side">Balance Side</label>
                            <select id="balance_side" name="balance_side" class="uf-input">
                                <option value="dr" @selected(old('balance_side', 'dr') === 'dr')>Debit (Dr)</option>
                                <option value="cr" @selected(old('balance_side') === 'cr')>Credit (Cr)</option>
                            </select>
                            <p class="uf-hint tich-mt-1">
                                Dr increases the account, Cr reduces it or increases it when it is a credit normal account.
                            </p>
                            @error('balance_side')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <p class="tich-text tich-text--sm tich-text--muted tich-mt-2">
                        The amount is posted to the ledger against opening balance account
                        <code>{{ $openingBalanceAccount }}</code>, and the parent account balance picks it up automatically.
                    </p>
                </div>
            </div>

            <div class="tich-flex tich-flex--end tich-gap-3 tich-mt-6 tich-pt-4 tich-border-t">
                <a href="{{ $parentAccount ? route('finance.chart-of-accounts.show', $parentAccount) : route('finance.gl.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
                <button type="submit" class="tich-btn tich-btn-primary">{{ $parentAccount ? 'Add Child Account' : 'Create Main Account' }}</button>
            </div>
        </div>
    </form>
@endsection