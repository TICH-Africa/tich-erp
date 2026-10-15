@extends('layouts.finance')

@section('title', $chartOfAccount->account_name)

@section('finance-content')
    <x-page-toolbar title="{{ $chartOfAccount->account_code }} - {{ $chartOfAccount->account_name }}" meta="{{ $chartOfAccount->isTopLevel() ? 'Main account' : 'Child account' }} - parent, children and balances">
        <x-slot:actions>
            @can('finance.chart_of_accounts.manage')
                @if ($canRecordExpenses)
                    <button type="button" class="tich-btn tich-btn-primary" data-open-modal="expense-modal">+ Record transaction</button>
                @endif
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
                <dd>{{ number_format(abs($ownBalance), 2) }} ({{ $chartOfAccount->signedBalanceLabel($ownBalance, true) }})</dd>
                <dt>From child accounts</dt>
                <dd>{{ number_format(abs($childBalance), 2) }} ({{ $chartOfAccount->signedBalanceLabel($childBalance, true) }})</dd>
                <dt>Balance including children</dt>
                <dd>
                    <strong>{{ number_format(abs($balance), 2) }}</strong>
                    <span class="tich-text--muted">({{ $chartOfAccount->signedBalanceLabel($balance, true) }} balance, {{ $chartOfAccount->isDebitNormal() ? 'debit normal' : 'credit normal' }})</span>
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
                                        {{ number_format(abs($ancestorBalance['net']), 2) }} {{ $ancestor->signedBalanceLabel($ancestorBalance['net']) }}
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
                                <strong>{{ number_format(abs($balance), 2) }} {{ $chartOfAccount->signedBalanceLabel($balance) }}</strong>
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
                                    {{ number_format(abs($childBalance['net']), 2) }} {{ $child->signedBalanceLabel($childBalance['net']) }}
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

    @if ($canRecordExpenses || $expenseEntries->isNotEmpty())
        <section class="tich-card tich-mt-6 tich-table-panel">
            <div class="tich-card__body">
                <div class="tich-flex tich-flex--middle" style="justify-content: space-between;">
                    <h3 class="tich-h3" style="margin: 0;">Transaction Breakdown</h3>
                    @can('finance.chart_of_accounts.manage')
                        @if ($canRecordExpenses)
                            <button type="button" class="tich-btn tich-btn-primary tich-btn--sm" data-open-modal="expense-modal">+ Record transaction</button>
                        @endif
                    @endcan
                </div>

                @if ($expenseEntries->isNotEmpty())
                    <div class="tich-table-wrap tich-mt-4">
                        <table class="tich-admin-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Num</th>
                                    <th>Name</th>
                                    <th>Memo</th>
                                    <th>Split</th>
                                    <th style="text-align: right;">VAT (KES)</th>
                                    <th style="text-align: right;">Effect on account (KES)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($expenseEntries as $entry)
                                    <tr>
                                        <td>{{ $entry->paidOut() ? 'Payment (money out)' : 'Receipt (money in)' }}</td>
                                        <td>{{ $entry->expense_date?->format('d/m/Y') }}</td>
                                        <td>{{ $entry->reference_no }}</td>
                                        <td>{{ $entry->payee_name ?? '-' }}</td>
                                        <td>{{ $entry->memo ?? '-' }}</td>
                                        <td><code>{{ $entry->credit_account_code }}</code></td>
                                        <td style="text-align: right;">{{ number_format((float) $entry->vat_amount, 2) }}</td>
                                        <td style="text-align: right;">{{ $entry->increasesAccount() ? '+' : '-' }}{{ number_format((float) $entry->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" style="text-align: right;"><strong>Net effect on this account</strong></td>
                                    <td style="text-align: right;">
                                        @php $netEffect = $expenseEntries->sum(fn ($entry) => $entry->increasesAccount() ? (float) $entry->amount : -(float) $entry->amount); @endphp
                                        <strong>{{ $netEffect < 0 ? '-' : '+' }}{{ number_format(abs($netEffect), 2) }}</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="tich-text tich-text--muted tich-mt-4">No transactions recorded on this account yet.</p>
                @endif
            </div>
        </section>
    @endif

    @can('finance.chart_of_accounts.manage')
        @if ($canRecordExpenses)
            <div id="expense-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
                <div class="tich-modal__backdrop" data-close-modal="expense-modal"></div>
                <div class="tich-modal__dialog" style="max-width: 860px;">
                    <header class="tich-modal__header">
                        <h2 class="tich-h3" style="margin: 0;">Record Transaction - <code>{{ $chartOfAccount->account_code }}</code> {{ $chartOfAccount->account_name }}</h2>
                        <button type="button" class="tich-modal__close" data-close-modal="expense-modal" aria-label="Close">&times;</button>
                    </header>
                    <form method="POST" action="{{ route('finance.chart-of-accounts.expenses.store', $chartOfAccount) }}" id="expense-form" class="tich-modal__body">
                        @csrf
                        <input type="hidden" name="after_save" id="after_save" value="close">

                        <div class="tich-flex tich-flex--wrap tich-gap-2 tich-flex--middle tich-mb-4">
                            @php $debitNormal = $chartOfAccount->isDebitNormal(); @endphp
                            <div>
                                <label class="tich-caption" for="bank_account_code">BANK ACCOUNT</label>
                                <select id="bank_account_code" name="bank_account_code" class="tich-select" required>
                                    @foreach ($bankOptions as $bank)
                                        <option value="{{ $bank['code'] }}" data-balance="{{ $bank['balance'] }}" @selected(old('bank_account_code', $defaultBankCode) === $bank['code'])>
                                            {{ $bank['code'] }} - {{ $bank['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="tich-caption" for="direction">DIRECTION</label>
                                <select id="direction" name="direction" class="tich-select" data-debit-normal="{{ $debitNormal ? 'true' : 'false' }}">
                                    <option value="increase" @selected(old('direction', 'increase') === 'increase')>
                                        Increase account ({{ $debitNormal ? 'money paid out' : 'money received' }})
                                    </option>
                                    <option value="decrease" @selected(old('direction') === 'decrease')>
                                        Decrease account ({{ $debitNormal ? 'money refunded to us' : 'money paid out' }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <span class="tich-caption">ENDING BALANCE</span><br>
                                <strong id="bank-ending-balance">KES 0.00</strong>
                            </div>
                            <div style="flex: 1;"></div>
                            <p class="tich-text tich-text--sm tich-text--muted" style="margin: 0;">
                                Posts Dr <code id="effect-dr">{{ $debitNormal ? $chartOfAccount->account_code : $defaultBankCode }}</code> / Cr <code id="effect-cr">{{ $debitNormal ? $defaultBankCode : $chartOfAccount->account_code }}</code>@if ($chartOfAccount->parent)
                                    , then rolls up to <code>{{ $chartOfAccount->parent->account_code }}</code>
                                @endif.
                            </p>
                        </div>

                        <div class="tich-grid tich-grid--2 tich-mb-4" style="gap: 1rem;">
                            <div>
                                <label class="tich-caption" for="reference_no">NO.</label>
                                <input type="text" id="reference_no" name="reference_no" class="tich-input" value="{{ old('reference_no', $nextExpenseReference) }}">
                            </div>
                            <div>
                                <label class="tich-caption" for="expense_date">DATE</label>
                                <input type="date" id="expense_date" name="expense_date" class="tich-input" required value="{{ old('expense_date', now()->toDateString()) }}">
                            </div>
                            <div>
                                <label class="tich-caption" for="payee_name">PAY TO THE ORDER OF</label>
                                <input type="text" id="payee_name" name="payee_name" class="tich-input" value="{{ old('payee_name') }}">
                            </div>
                            <div>
                                <label class="tich-caption" for="address">ADDRESS</label>
                                <textarea id="address" name="address" class="tich-input" rows="2">{{ old('address') }}</textarea>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label class="tich-caption" for="header_memo">MEMO</label>
                                <input type="text" id="header_memo" name="header_memo" class="tich-input" value="{{ old('header_memo') }}">
                            </div>
                        </div>

                        <p class="tich-caption" style="margin: 0 0 0.35rem;">BREAKDOWN</p>
                        <div class="tich-table-wrap" style="max-height: 240px; overflow-y: auto;">
                            <table class="tich-admin-table" id="expense-lines">
                                <thead>
                                    <tr>
                                        <th style="width: 34%;">MEMO</th>
                                        <th style="width: 15%;">VAT (KES)</th>
                                        <th style="width: 17%;">AMOUNT (KES)</th>
                                        <th style="width: 20%;">CLASS</th>
                                        <th style="width: 8%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (old('lines', [[]]) as $i => $line)
                                        <tr class="expense-line">
                                            <td><input type="text" name="lines[{{ $i }}][memo]" class="tich-input" value="{{ $line['memo'] ?? '' }}"></td>
                                            <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][vat_amount]" class="tich-input line-vat" value="{{ $line['vat_amount'] ?? '' }}"></td>
                                            <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][amount]" class="tich-input line-amount" value="{{ $line['amount'] ?? '' }}"></td>
                                            <td><input type="text" name="lines[{{ $i }}][class]" class="tich-input" value="{{ $line['class'] ?? '' }}"></td>
                                            <td><button type="button" class="tich-btn tich-btn-ghost tich-btn--sm line-remove" aria-label="Remove line">&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="tich-btn tich-btn-ghost tich-btn--sm tich-mt-2" id="add-line">+ Add line</button>

                        <div class="tich-flex tich-flex--wrap tich-gap-2 tich-flex--middle tich-mt-4">
                            <div>
                                <label class="tich-caption" for="supplier_vat_code">SUPPLIER VAT CODE</label>
                                <input type="text" id="supplier_vat_code" name="supplier_vat_code" class="tich-input" value="{{ old('supplier_vat_code') }}">
                            </div>
                            <div>
                                <label class="tich-caption" for="exchange_rate">EXCHANGE RATE (1 {{ $chartOfAccount->currency ?? 'KES' }} =)</label>
                                <input type="number" step="0.000001" min="0" id="exchange_rate" name="exchange_rate" class="tich-input" value="{{ old('exchange_rate', '1') }}" style="width: 110px;">
                            </div>
                            <div style="flex: 1;"></div>
                            <div><span class="tich-caption">SUBTOTAL</span> <strong id="expenses-total">KES 0.00</strong></div>
                            <div><span class="tich-caption">TOTAL VAT</span> <strong id="vat-total">KES 0.00</strong></div>
                        </div>

                        <footer class="tich-modal__footer" style="margin-top: 1rem;">
                            <button type="button" class="tich-btn tich-btn-ghost tich-btn--sm" id="clear-form-btn">Clear</button>
                            <div style="flex: 1;"></div>
                            <button type="button" class="tich-btn tich-btn-secondary tich-btn--sm" data-close-modal="expense-modal">Cancel</button>
                            <button type="submit" class="tich-btn tich-btn-primary tich-btn--sm">Save &amp; Close</button>
                            <button type="button" class="tich-btn tich-btn-primary tich-btn--sm" id="save-new-btn">Save &amp; New</button>
                        </footer>
                    </form>
                </div>
            </div>
        @endif
    @endcan
@endsection

@section('scripts')
    @parent
    @include('admin.partials.tich-modal-assets')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('expense-form');

            if (!form) {
                return;
            }

            const linesBody = form.querySelector('#expense-lines tbody');
            const addBtn = document.getElementById('add-line');

            function money(amount) {
                return 'KES ' + amount.toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function recalc() {
                let expenses = 0;
                let vat = 0;

                linesBody.querySelectorAll('.line-amount').forEach(function (input) {
                    expenses += parseFloat(input.value) || 0;
                });

                linesBody.querySelectorAll('.line-vat').forEach(function (input) {
                    vat += parseFloat(input.value) || 0;
                });

                document.getElementById('expenses-total').textContent = money(expenses);
                document.getElementById('vat-total').textContent = money(vat);
            }

            addBtn.addEventListener('click', function () {
                const index = linesBody.querySelectorAll('tr.expense-line').length;
                const row = document.createElement('tr');
                row.className = 'expense-line';
                row.innerHTML =
                    '<td><input type="text" name="lines[' + index + '][memo]" class="tich-input"></td>'
                    + '<td><input type="number" step="0.01" min="0" name="lines[' + index + '][vat_amount]" class="tich-input line-vat"></td>'
                    + '<td><input type="number" step="0.01" min="0" name="lines[' + index + '][amount]" class="tich-input line-amount"></td>'
                    + '<td><input type="text" name="lines[' + index + '][class]" class="tich-input"></td>'
                    + '<td><button type="button" class="tich-btn tich-btn-ghost tich-btn--sm line-remove" aria-label="Remove line">&times;</button></td>';
                linesBody.appendChild(row);
                row.querySelector('.line-amount').focus();
            });

            linesBody.addEventListener('click', function (event) {
                const button = event.target.closest('.line-remove');

                if (!button) {
                    return;
                }

                const rows = linesBody.querySelectorAll('tr.expense-line');

                if (rows.length <= 1) {
                    rows[0].querySelectorAll('input').forEach(function (input) {
                        input.value = '';
                    });
                } else {
                    button.closest('tr').remove();
                }

                recalc();
            });

            linesBody.addEventListener('input', recalc);

            const bankSelect = document.getElementById('bank_account_code');
            const balanceEl = document.getElementById('bank-ending-balance');
            const directionSelect = document.getElementById('direction');
            const effectDr = document.getElementById('effect-dr');
            const effectCr = document.getElementById('effect-cr');
            const accountCode = @json($chartOfAccount->account_code);
            const debitNormal = @json($chartOfAccount->isDebitNormal());

            function updateBankBalance() {
                const option = bankSelect.options[bankSelect.selectedIndex];
                const balance = parseFloat(option ? option.getAttribute('data-balance') : '0') || 0;
                balanceEl.textContent = money(Math.abs(balance)) + (balance >= 0 ? ' Dr' : ' Cr');
            }

            function updatePostingEffect() {
                const paidOut = debitNormal === (directionSelect.value !== 'decrease');
                const bankCode = bankSelect.value || 'bank';
                effectDr.textContent = paidOut ? accountCode : bankCode;
                effectCr.textContent = paidOut ? bankCode : accountCode;
            }

            bankSelect.addEventListener('change', function () {
                updateBankBalance();
                updatePostingEffect();
            });
            directionSelect.addEventListener('change', updatePostingEffect);
            updateBankBalance();
            updatePostingEffect();

            document.getElementById('save-new-btn').addEventListener('click', function () {
                document.getElementById('after_save').value = 'new';
                form.submit();
            });

            document.getElementById('clear-form-btn').addEventListener('click', function () {
                form.reset();
                const rows = linesBody.querySelectorAll('tr.expense-line');
                rows.forEach(function (row, index) {
                    if (index > 0) {
                        row.remove();
                    }
                });
                rows[0].querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                });
                recalc();
                updateBankBalance();
            });

            @if (session('open_expense_modal') || $errors->isNotEmpty())
                if (window.tichOpenModal) {
                    window.tichOpenModal('expense-modal');
                }
            @endif
        });
    </script>
@endsection