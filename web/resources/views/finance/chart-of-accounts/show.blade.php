@extends('layouts.finance')

@section('title', $chartOfAccount->account_name)

@section('finance-content')
    <x-page-toolbar title="{{ $chartOfAccount->account_code }} - {{ $chartOfAccount->account_name }}" meta="{{ $chartOfAccount->isTopLevel() ? 'Main account' : 'Child account' }} - parent, children and balances">
        <x-slot:actions>
            @can('finance.chart_of_accounts.manage')
                <a href="{{ route('finance.chart-of-accounts.create', ['parent' => $chartOfAccount->account_code]) }}" class="tich-btn tich-btn-secondary">+ Add Child</a>
                <a href="{{ route('finance.chart-of-accounts.edit', $chartOfAccount) }}" class="tich-btn tich-btn-ghost">Edit</a>
            @endcan
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
            @if ($ancestors->isNotEmpty())
                <p class="tich-text tich-text--sm tich-text--muted tich-mb-4">
                    @foreach ($ancestors as $ancestor)
                        <a href="{{ route('finance.chart-of-accounts.show', $ancestor) }}"><code>{{ $ancestor->account_code }}</code></a>
                        <span class="tich-text--muted">&rsaquo;</span>
                    @endforeach
                    <strong><code>{{ $chartOfAccount->account_code }}</code></strong>
                </p>
            @endif

            <h3 class="tich-h3 tich-mb-4">Account Details</h3>
            <dl class="tich-dl tich-mb-6">
                <dt>Account Code</dt><dd><code>{{ $chartOfAccount->account_code }}</code></dd>
                <dt>Account Name</dt><dd>{{ $chartOfAccount->account_name }}</dd>
                <dt>Level</dt>
                <dd>
                    @if ($chartOfAccount->isTopLevel())
                        <span class="tich-badge bg-blue-100 text-blue-800">Main account</span>
                    @else
                        <span class="tich-badge bg-gray-100 text-gray-600">Child account</span>
                    @endif
                </dd>
                <dt>Type</dt><dd>{{ ucfirst($chartOfAccount->account_type) }}</dd>
                <dt>Currency</dt><dd>{{ $chartOfAccount->currency ?? 'KES' }}</dd>
                <dt>Parent Account</dt>
                <dd>
                    @if ($chartOfAccount->parent)
                        <a href="{{ route('finance.chart-of-accounts.show', $chartOfAccount->parent) }}">
                            <code>{{ $chartOfAccount->parent->account_code }}</code> - {{ $chartOfAccount->parent->account_name }}
                        </a>
                    @else
                        <span class="tich-text--muted">None - this is a main account</span>
                    @endif
                </dd>
                <dt>Child Accounts</dt><dd>{{ $descendants->count() }}</dd>
                <dt>Status</dt>
                <dd>
                    @if ($chartOfAccount->is_active)
                        <span class="tich-badge bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="tich-badge bg-gray-100 text-gray-600">Inactive</span>
                    @endif
                </dd>
            </dl>

            <h3 class="tich-h3 tich-mb-4">Balances</h3>
            <dl class="tich-dl">
                <dt>Total Debits</dt><dd>{{ number_format($debitTotal, 2) }}</dd>
                <dt>Total Credits</dt><dd>{{ number_format($creditTotal, 2) }}</dd>
                <dt>Posted on this account</dt>
                <dd>{{ number_format(abs($ownBalance), 2) }} ({{ $ownBalance >= 0 ? 'Debit' : 'Credit' }})</dd>
                <dt>From child accounts</dt>
                <dd>{{ number_format(abs($childBalance), 2) }} ({{ $childBalance >= 0 ? 'Debit' : 'Credit' }})</dd>
                <dt>Balance including children</dt>
                <dd>
                    <strong>{{ number_format(abs($balance), 2) }}</strong>
                    <span class="tich-text--muted">({{ $balance >= 0 ? 'Debit' : 'Credit' }}, {{ $chartOfAccount->isDebitNormal() ? 'debit normal' : 'credit normal' }})</span>
                </dd>
                <dt>Ledger entries on this account</dt><dd>{{ $entryCount }}</dd>
            </dl>

            <a href="{{ route('finance.gl.index') }}" class="tich-btn tich-btn-ghost tich-mt-6">Open General Ledger</a>
        </div>
    </div>

    <div class="tich-card tich-mt-6">
        <div class="tich-card__body">
            <h3 class="tich-h3 tich-mb-4">Parent / Child Relationship</h3>

            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Level</th>
                            <th>Type</th>
                            <th>Currency</th>
                            <th>Balance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ancestors as $ancestor)
                            <tr>
                                <td>
                                    <a href="{{ route('finance.chart-of-accounts.show', $ancestor) }}">
                                        <code>{{ $ancestor->account_code }}</code> - {{ $ancestor->account_name }}
                                    </a>
                                </td>
                                <td><span class="tich-badge bg-blue-100 text-blue-800">Main</span></td>
                                <td>{{ ucfirst($ancestor->account_type) }}</td>
                                <td>{{ $ancestor->currency ?? 'KES' }}</td>
                                <td>
                                    @php $ancestorBalance = $descendantBalances[$ancestor->account_code] ?? null; @endphp
                                    @if ($ancestorBalance)
                                        {{ number_format(abs($ancestorBalance['net']), 2) }} {{ $ancestorBalance['net'] >= 0 ? 'Dr' : 'Cr' }}
                                    @else
                                        <span class="tich-text--muted">Parent roll-up</span>
                                    @endif
                                </td>
                                <td><a href="{{ route('finance.chart-of-accounts.show', $ancestor) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a></td>
                            </tr>
                        @endforeach

                        <tr style="background: #f9fafb;">
                            <td><strong><code>{{ $chartOfAccount->account_code }}</code> - {{ $chartOfAccount->account_name }}</strong></td>
                            <td>
                                <span class="tich-badge {{ $chartOfAccount->isTopLevel() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $chartOfAccount->isTopLevel() ? 'Main' : 'Child' }}
                                </span>
                            </td>
                            <td>{{ ucfirst($chartOfAccount->account_type) }}</td>
                            <td>{{ $chartOfAccount->currency ?? 'KES' }}</td>
                            <td>
                                <strong>{{ number_format(abs($balance), 2) }} {{ $balance >= 0 ? 'Dr' : 'Cr' }}</strong>
                                <br><span class="tich-text--sm tich-text--muted">own {{ number_format(abs($ownBalance), 2) }}</span>
                            </td>
                            <td class="tich-text--muted">This account</td>
                        </tr>

                        @foreach ($descendants as $row)
                            @php
                                $child = $row['account'];
                                $childBalance = $descendantBalances[$child->account_code] ?? ['debit' => 0.0, 'credit' => 0.0, 'net' => 0.0, 'ownNet' => 0.0, 'childNet' => 0.0];
                            @endphp
                            <tr>
                                <td style="padding-left: {{ 1 + $row['depth'] }}rem;">
                                    <a href="{{ route('finance.chart-of-accounts.show', $child) }}">
                                        <code>{{ $child->account_code }}</code> - {{ $child->account_name }}
                                    </a>
                                </td>
                                <td><span class="tich-badge bg-gray-100 text-gray-600">Child</span></td>
                                <td>{{ ucfirst($child->account_type) }}</td>
                                <td>{{ $child->currency ?? 'KES' }}</td>
                                <td>
                                    {{ number_format(abs($childBalance['net']), 2) }} {{ $childBalance['net'] >= 0 ? 'Dr' : 'Cr' }}
                                    <br><span class="tich-text--sm tich-text--muted">Dr {{ number_format($childBalance['debit'], 2) }} / Cr {{ number_format($childBalance['credit'], 2) }}</span>
                                </td>
                                <td>
                                    <div class="tich-flex" style="gap:0.35rem; align-items:center;">
                                        <a href="{{ route('finance.chart-of-accounts.show', $child) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a>
                                        @can('finance.chart_of_accounts.manage')
                                            <a href="{{ route('finance.chart-of-accounts.edit', $child) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Edit</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($descendants->isEmpty())
                <p class="tich-text tich-text--muted tich-mt-4">
                    This account has no child accounts.
                    @can('finance.chart_of_accounts.manage')
                        <a href="{{ route('finance.chart-of-accounts.create', ['parent' => $chartOfAccount->account_code]) }}">Add a child account</a>.
                    @endcan
                </p>
            @endif
        </div>
    </div>
@endsection