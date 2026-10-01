@extends('layouts.finance')

@section('title', 'Edit ' . $chartOfAccount->account_name)

@section('finance-content')
    <x-page-toolbar title="Edit Chart of Account" meta="{{ $chartOfAccount->account_code }} - {{ $chartOfAccount->account_name }}">
        <x-slot:actions>
            <a href="{{ route('finance.chart-of-accounts.show', $chartOfAccount) }}" class="tich-btn tich-btn-ghost">View</a>
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

    <form method="POST" action="{{ route('finance.chart-of-accounts.update', $chartOfAccount) }}" class="tich-card tich-mt-6" style="padding-left: 2rem; padding-right: 2rem;">
        @csrf
        @method('PUT')

        <div class="tich-card__body">
            <div class="uf-form-section">
                <div class="uf-section-head">Account Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="account_code">Account Code <span class="uf-req">*</span></label>
                            <input type="text" id="account_code" name="account_code" class="uf-input" value="{{ old('account_code', $chartOfAccount->account_code) }}" required maxlength="30">
                            @error('account_code')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="account_name">Account Name <span class="uf-req">*</span></label>
                            <input type="text" id="account_name" name="account_name" class="uf-input" value="{{ old('account_name', $chartOfAccount->account_name) }}" required maxlength="200">
                            @error('account_name')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="account_type">Account Type <span class="uf-req">*</span></label>
                            @php $typeLocked = $chartOfAccount->children()->exists() || $chartOfAccount->hasLedgerEntries(); @endphp
                            <select id="account_type" name="account_type" class="uf-input" required @if($typeLocked) disabled @endif>
                                @foreach(['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expense'] as $key => $label)
                                    <option value="{{ $key }}" @selected($chartOfAccount->account_type === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if($typeLocked)
                                <input type="hidden" name="account_type" value="{{ $chartOfAccount->account_type }}">
                                <p class="uf-hint tich-mt-1 tich-text--muted">Cannot change type: account has children or ledger entries.</p>
                            @endif
                            @error('account_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="currency">Currency <span class="uf-req">*</span></label>
                            <select id="currency" name="currency" class="uf-input" required>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency }}" @selected(old('currency', $chartOfAccount->currency) === $currency)>{{ $currency }}</option>
                                @endforeach
                            </select>
                            @error('currency')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            @if($chartOfAccount->hasLedgerEntries())
                <div class="tich-alert tich-alert--info tich-mt-6">
                    <h4 class="tich-h5 tich-mb-2">Account Has Ledger Entries</h4>
                    <p class="tich-text tich-mb-0">This account has existing ledger entries. The account type cannot be changed. Deletion is not allowed.</p>
                </div>
            @endif

            <div class="tich-flex tich-flex--end tich-gap-3 tich-mt-6 tich-pt-4 tich-border-t">
                <a href="{{ route('finance.chart-of-accounts.show', $chartOfAccount) }}" class="tich-btn tich-btn-secondary">Cancel</a>
                <button type="submit" class="tich-btn tich-btn-primary">Update Account</button>
            </div>
        </div>
    </form>
@endsection