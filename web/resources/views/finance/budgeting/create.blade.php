@extends('layouts.finance')

@section('title', 'Create budget')

@section('finance-content')
    <x-page-toolbar title="Create budget" meta="Set up a new budget for a department">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.budgeting.store', $department) }}" class="tich-mt-6" id="finance-budget-form">
        @csrf
        <div class="tich-card tich-form-stack">
            <div class="tich-form-group">
                <label class="tich-label">Department</label>
                <input type="text" class="tich-input" value="{{ $department->dept_name }} ({{ $department->dept_code }})" disabled>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="budget_name">Budget name <span class="tich-text--danger">*</span></label>
                    <input type="text" id="budget_name" name="budget_name" class="tich-input" placeholder="e.g. FY2026 operations budget" value="{{ old('budget_name') }}" required maxlength="300">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="budget_type">Budget type</label>
                    <select id="budget_type" name="budget_type" class="tich-input">
                        <option value="">Optional</option>
                        @foreach (['annual', 'quarterly', 'monthly', 'weekly'] as $type)
                            <option value="{{ $type }}" @selected(old('budget_type') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="fiscal_year">Fiscal year <span class="tich-text--danger">*</span></label>
                    <input type="number" id="fiscal_year" name="fiscal_year" class="tich-input" value="{{ old('fiscal_year', date('Y')) }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="budget_code">Budget code <span class="tich-text--danger">*</span></label>
                    <input type="text" id="budget_code" name="budget_code" class="tich-input" placeholder="e.g. BGT-FIN-ACAD" value="{{ old('budget_code') }}" required>
                </div>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="period_start">Period start <span class="tich-text--danger">*</span></label>
                    <input type="text" id="period_start" name="period_start" class="tich-input" placeholder="dd/mm/yyyy" value="{{ old('period_start') }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="period_end">Period end <span class="tich-text--danger">*</span></label>
                    <input type="text" id="period_end" name="period_end" class="tich-input" placeholder="dd/mm/yyyy" value="{{ old('period_end') }}" required>
                </div>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" class="tich-input" rows="3" placeholder="Optional notes...">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="tich-card tich-table-panel tich-mt-6">
            <div class="tich-flex-wrap" style="justify-content: space-between; align-items: center; gap: 0.75rem;">
                <div>
                    <h2 class="tich-h3" style="margin:0;">Line items</h2>
                    <p class="tich-caption tich-mt-1" style="margin:0;">Row total = quantity × price per item</p>
                </div>
                <button type="button" class="tich-btn tich-btn-secondary" id="finance-budget-add-line">+ Add line</button>
            </div>
            <div class="tich-table-wrap tich-mt-4">
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

        <div class="tich-flex-wrap tich-mt-6" style="gap:0.75rem; justify-content:flex-end;">
            <a href="{{ route('finance.budgeting.index', $department) }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary">Create budget</button>
        </div>
    </form>

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
