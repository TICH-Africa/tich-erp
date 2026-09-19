@extends('layouts.finance')

@section('title', 'Create budget')

@section('finance-content')
    <x-page-toolbar title="Create budget" meta="Set up a new budget for a department">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.budgeting.store') }}" data-uf="ready" id="finance-budget-form">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">BGT · Create budget</div>
                    <div class="uf-amount-bar__sum">{{ $department->dept_name }}</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Budget Details</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label>Department</label>
                        <input type="text" value="{{ $department->dept_name }} ({{ $department->dept_code }})" disabled>
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="budget_name">Budget name <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="budget_name"
                                name="budget_name"
                                placeholder="e.g. FY2026 operations budget"
                                value="{{ old('budget_name') }}"
                                required
                                maxlength="300"
                                class="{{ $errors->has('budget_name') ? 'is-invalid' : '' }}"
                            >
                            @error('budget_name')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="budget_type">Budget type</label>
                            <select
                                id="budget_type"
                                name="budget_type"
                                class="{{ $errors->has('budget_type') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Optional</option>
                                @foreach (['annual', 'quarterly', 'monthly', 'weekly'] as $type)
                                    <option value="{{ $type }}" @selected(old('budget_type') === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                            @error('budget_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="fiscal_year">Fiscal year <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                id="fiscal_year"
                                name="fiscal_year"
                                value="{{ old('fiscal_year', date('Y')) }}"
                                required
                                class="{{ $errors->has('fiscal_year') ? 'is-invalid' : '' }}"
                            >
                            @error('fiscal_year')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="budget_code">Budget code <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="budget_code"
                                name="budget_code"
                                placeholder="e.g. BGT-FIN-ACAD"
                                value="{{ old('budget_code') }}"
                                required
                                class="{{ $errors->has('budget_code') ? 'is-invalid' : '' }}"
                            >
                            @error('budget_code')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="period_start">Period start <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="period_start"
                                name="period_start"
                                placeholder="dd/mm/yyyy"
                                value="{{ old('period_start') }}"
                                required
                                class="{{ $errors->has('period_start') ? 'is-invalid' : '' }}"
                            >
                            @error('period_start')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="period_end">Period end <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="period_end"
                                name="period_end"
                                placeholder="dd/mm/yyyy"
                                value="{{ old('period_end') }}"
                                required
                                class="{{ $errors->has('period_end') ? 'is-invalid' : '' }}"
                            >
                            @error('period_end')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="notes">Notes</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            placeholder="Optional notes..."
                            class="{{ $errors->has('notes') ? 'is-invalid' : '' }}"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Line Items</div>
                <div class="uf-section-body">
                    <div class="tich-flex-wrap" style="justify-content: space-between; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <p class="uf-hint" style="margin:0;">Row total = quantity × price per item</p>
                        <button type="button" class="uf-btn uf-btn-secondary" id="finance-budget-add-line">+ Add line</button>
                    </div>
                    <div class="tich-table-wrap">
                        <table class="tich-admin-table" id="finance-budget-lines-table">
                            <thead>
                                <tr>
                                    <th style="min-width:9rem;">Item</th>
                                    <th style="min-width:6rem;">Quantity</th>
                                    <th style="min-width:12rem;">Description</th>
                                    <th style="min-width:8rem;">Price per item</th>
                                    <th style="min-width:7rem;">Unit of measure</th>
                                    <th style="min-width:8rem;">Total</th>
                                    <th style="width:3rem;"></th>
                                </tr>
                            </thead>
                            <tbody id="finance-budget-lines-body">
                                <tr class="dept-budget-line">
                                    <td><input type="text" name="lines[0][item]" class="tich-input" required maxlength="255" placeholder="Item name"></td>
                                    <td><input type="number" name="lines[0][quantity]" class="tich-input js-line-qty" value="1" min="0.0001" step="any" required></td>
                                    <td><input type="text" name="lines[0][description]" class="tich-input" maxlength="2000" placeholder="Optional"></td>
                                    <td><input type="number" name="lines[0][unit_price]" class="tich-input js-line-price" min="0" step="0.01" required placeholder="0.00"></td>
                                    <td><input type="text" name="lines[0][unit_of_measure]" class="tich-input" maxlength="50" placeholder="e.g. pcs"></td>
                                    <td><input type="text" class="tich-input js-line-total" value="0.00" readonly tabindex="-1" aria-label="Line total"></td>
                                    <td><button type="button" class="tich-btn tich-btn-ghost js-remove-line" title="Remove line" aria-label="Remove line">&times;</button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align:right; font-weight:600;">Grand total (KES)</td>
                                    <td><strong id="finance-budget-grand-total">0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create budget</button>
                        <a href="{{ route('finance.budgeting.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <script>
    (function () {
        var body = document.getElementById('finance-budget-lines-body');
        var addBtn = document.getElementById('finance-budget-add-line');
        var grandTotalEl = document.getElementById('finance-budget-grand-total');
        if (!body || !addBtn || !grandTotalEl) return;

        function formatMoney(value) {
            return (Math.round((value + Number.EPSILON) * 100) / 100).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function rowTotal(row) {
            var qty = parseFloat(row.querySelector('.js-line-qty')?.value || '0');
            var price = parseFloat(row.querySelector('.js-line-price')?.value || '0');
            if (!isFinite(qty) || !isFinite(price)) return 0;
            return qty * price;
        }

        function refreshTotals() {
            var sum = 0;
            body.querySelectorAll('.dept-budget-line').forEach(function (row) {
                var total = rowTotal(row);
                sum += total;
                var totalInput = row.querySelector('.js-line-total');
                if (totalInput) totalInput.value = formatMoney(total);
            });
            grandTotalEl.textContent = formatMoney(sum);
        }

        function reindexRows() {
            body.querySelectorAll('.dept-budget-line').forEach(function (row, index) {
                row.querySelectorAll('input[name^="lines["]').forEach(function (input) {
                    input.name = input.name.replace(/lines\[\d+]/, 'lines[' + index + ']');
                });
            });
        }

        function bindRow(row) {
            row.querySelectorAll('.js-line-qty, .js-line-price').forEach(function (input) {
                input.addEventListener('input', refreshTotals);
                input.addEventListener('change', refreshTotals);
            });
            var removeBtn = row.querySelector('.js-remove-line');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    if (body.querySelectorAll('.dept-budget-line').length <= 1) {
                        row.querySelectorAll('input').forEach(function (input) {
                            if (input.classList.contains('js-line-total')) return;
                            if (input.classList.contains('js-line-qty')) {
                                input.value = '1';
                            } else {
                                input.value = '';
                            }
                        });
                        refreshTotals();
                        return;
                    }
                    row.remove();
                    reindexRows();
                    refreshTotals();
                });
            }
        }

        function addRow() {
            var index = body.querySelectorAll('.dept-budget-line').length;
            var row = document.createElement('tr');
            row.className = 'dept-budget-line';
            row.innerHTML =
                '<td><input type="text" name="lines[' + index + '][item]" class="tich-input" required maxlength="255" placeholder="Item name"></td>' +
                '<td><input type="number" name="lines[' + index + '][quantity]" class="tich-input js-line-qty" value="1" min="0.0001" step="any" required></td>' +
                '<td><input type="text" name="lines[' + index + '][description]" class="tich-input" maxlength="2000" placeholder="Optional"></td>' +
                '<td><input type="number" name="lines[' + index + '][unit_price]" class="tich-input js-line-price" min="0" step="0.01" required placeholder="0.00"></td>' +
                '<td><input type="text" name="lines[' + index + '][unit_of_measure]" class="tich-input" maxlength="50" placeholder="e.g. pcs"></td>' +
                '<td><input type="text" class="tich-input js-line-total" value="0.00" readonly tabindex="-1" aria-label="Line total"></td>' +
                '<td><button type="button" class="tich-btn tich-btn-ghost js-remove-line" title="Remove line" aria-label="Remove line">&times;</button></td>';
            body.appendChild(row);
            bindRow(row);
            refreshTotals();
            row.querySelector('input[name$="[item]"]')?.focus();
        }

        body.querySelectorAll('.dept-budget-line').forEach(bindRow);
        addBtn.addEventListener('click', addRow);
        refreshTotals();
    })();
    </script>
@endsection
