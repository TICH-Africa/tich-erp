@extends('layouts.finance')

@section('title', $chartOfAccount->account_name)

@section('finance-content')
    <x-page-toolbar title="{{ $chartOfAccount->account_code }} - {{ $chartOfAccount->account_name }}" meta="Account details and ledger activity">
        <x-slot:actions>
            @unless ($chartOfAccount->is_system_account)
                <a href="{{ route('finance.chart-of-accounts.edit', $chartOfAccount) }}" class="tich-btn tich-btn-ghost">Edit</a>
            @endunless
            <a href="{{ route('finance.gl.index') }}" class="tich-btn tich-btn-ghost">Back to List</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            <ul style="margin:0; padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tich-card tich-mt-6">
        <div class="tich-card__body">
            <h3 class="tich-h3 tich-mb-4">Account Details</h3>
            <dl class="tich-dl tich-mb-6">
                <dt>Account Code</dt><dd><code>{{ $chartOfAccount->account_code }}</code></dd>
                <dt>Account Name</dt><dd>{{ $chartOfAccount->account_name }}</dd>
                <dt>Type</dt><dd>{{ ucfirst($chartOfAccount->account_type) }}</dd>
                <dt>Currency</dt><dd>{{ $chartOfAccount->currency ?? 'KES' }}</dd>
                <dt>Parent Account</dt>
                <dd>
                    @if ($chartOfAccount->parent)
                        <a href="{{ route('finance.chart-of-accounts.show', $chartOfAccount->parent) }}">
                            <code>{{ $chartOfAccount->parent->account_code }}</code> - {{ $chartOfAccount->parent->account_name }}
                        </a>
                    @else
                        <span class="tich-text--muted">Top level</span>
                    @endif
                </dd>
                <dt>Status</dt>
                <dd>
                    @if ($chartOfAccount->is_active)
                        <span class="tich-badge bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="tich-badge bg-gray-100 text-gray-600">Inactive</span>
                    @endif
                </dd>
                <dt>System Account</dt>
                <dd>
                    @if ($chartOfAccount->is_system_account)
                        <span class="tich-badge bg-blue-100 text-blue-800">System</span>
                    @else
                        <span class="tich-badge bg-gray-100 text-gray-600">User</span>
                    @endif
                </dd>
                <dt>Child Accounts</dt><dd>{{ $chartOfAccount->children->count() }}</dd>
            </dl>

            <h3 class="tich-h3 tich-mb-4">Balances</h3>
            <dl class="tich-dl">
                <dt>Total Debits</dt><dd>{{ number_format($debitTotal, 2) }}</dd>
                <dt>Total Credits</dt><dd>{{ number_format($creditTotal, 2) }}</dd>
                <dt>Balance</dt>
                <dd>
                    <strong>{{ number_format(abs($balance), 2) }}</strong>
                    <span class="tich-text--muted">({{ $balance >= 0 ? 'Debit' : 'Credit' }}, {{ $chartOfAccount->isDebitNormal() ? 'debit normal' : 'credit normal' }})</span>
                </dd>
                <dt>Ledger Entries</dt><dd>{{ $entryCount }}</dd>
            </dl>

                <a href="{{ route('finance.gl.index') }}" class="tich-btn tich-btn-ghost tich-mt-6">Open General Ledger</a>
        </div>
    </div>

    @if ($chartOfAccount->children->isNotEmpty())
        <div class="tich-card tich-mt-6">
            <div class="tich-card__body">
                <h3 class="tich-h3 tich-mb-4">Child Accounts</h3>
                <div class="tich-table-wrap">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Currency</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($chartOfAccount->children as $child)
                                <tr>
                                    <td><code>{{ $child->account_code }}</code></td>
                                    <td>{{ $child->account_name }}</td>
                                    <td>{{ $child->currency ?? 'KES' }}</td>
                                    <td>
                                        @if ($child->is_active)
                                            <span class="tich-badge bg-green-100 text-green-800">Active</span>
                                        @else
                                            <span class="tich-badge bg-gray-100 text-gray-600">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('finance.chart-of-accounts.show', $child) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
