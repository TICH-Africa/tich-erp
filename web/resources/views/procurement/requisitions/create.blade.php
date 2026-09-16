@extends('layouts.procurement')

@section('title', 'New Requisition')

@section('procurement-content')
    <x-page-toolbar title="New Requisition" meta="Search a budget, load its line items, then edit before saving the draft">
        <x-slot:actions>
            <a href="{{ route('procurement.requisitions.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    @php
        $oldLines = old('lines');
        if (! is_array($oldLines) || $oldLines === []) {
            $oldLines = [[
                'item' => old('requested_item', ''),
                'quantity' => old('quantity', '1'),
                'description' => '',
                'unit_price' => old('estimated_unit_cost', ''),
                'unit_of_measure' => '',
            ]];
        }
        $oldBudgetKey = old('budget_key', '');
    @endphp

    <form method="POST" action="{{ route('procurement.requisitions.store') }}" class="tich-mt-6" enctype="multipart/form-data" id="requisition-create-form">
        @csrf
        <input type="hidden" name="budget_code" id="budget_code" value="{{ old('budget_code') }}">
        <input type="hidden" name="requested_item" id="requested_item" value="{{ old('requested_item') }}">
        <input type="hidden" name="budget_line" id="budget_line" value="{{ old('budget_line') }}">
        <input type="hidden" name="estimated_unit_cost" id="estimated_unit_cost" value="{{ old('estimated_unit_cost') }}">
        <input type="hidden" name="quantity" id="quantity" value="{{ old('quantity') }}">
        <input type="hidden" name="estimated_cost" id="estimated_cost" value="{{ old('estimated_cost') }}">

        <div class="tich-card tich-form-stack">
            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="requesting_department_id">Requesting department <span class="tich-text--danger">*</span></label>
                    <select id="requesting_department_id" name="requesting_department_id" class="tich-input" required>
                        <option value="">Select department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('requesting_department_id') == $department->id)>{{ $department->dept_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="request_date">Request date <span class="tich-text--danger">*</span></label>
                    <input type="text" id="request_date" name="request_date" class="tich-input" placeholder="dd/mm/yyyy" value="{{ old('request_date', now()->format('d/m/Y')) }}" required>
                </div>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="budget_key">Budget <span class="tich-text--danger">*</span></label>
                <select
                    id="budget_key"
                    name="budget_key"
                    class="tich-input"
                    data-tich-search="true"
                    data-tich-search-placeholder="Search budget code, name, or department…"
                    required
                >
                    <option value="">Search and select a budget…</option>
                    @foreach ($budgetOptions as $option)
                        @php $optionKey = $option['source'].':'.$option['id']; @endphp
                        <option
                            value="{{ $optionKey }}"
                            data-source="{{ $option['source'] }}"
                            data-id="{{ $option['id'] }}"
                            data-code="{{ $option['code'] }}"
                            data-department-id="{{ $option['department_id'] }}"
                            @selected($oldBudgetKey === $optionKey || old('budget_code') === $option['code'])
                        >{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <p class="tich-caption tich-mt-1" id="budget-lines-hint" style="margin:0;">Selecting a budget loads its line items below. You can edit quantities, prices, and remove lines.</p>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="justification">Justification <span class="tich-text--danger">*</span></label>
                <textarea id="justification" name="justification" class="tich-input" rows="4" placeholder="Describe the purpose and business need for this requisition…" required>{{ old('justification') }}</textarea>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="attachments">Attachments</label>
                <input type="file" id="attachments" name="attachments[]" class="tich-input" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <p class="tich-caption tich-mt-1" style="margin:0;">Max 5 files, 5MB each. Accepted: PDF, DOC, DOCX, JPG, JPEG, PNG.</p>
            </div>
        </div>

        <div class="tich-card tich-table-panel tich-mt-6">
            <div class="tich-flex-wrap" style="justify-content: space-between; align-items: center; gap: 0.75rem;">
                <div>
                    <h2 class="tich-h3" style="margin:0;">Requisition line items</h2>
                    <p class="tich-caption tich-mt-1" style="margin:0;">Row total = quantity × unit price. Edit freely after loading from the budget.</p>
                </div>
                <button type="button" class="tich-btn tich-btn-secondary" id="requisition-add-line">+ Add line</button>
            </div>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table" id="requisition-lines-table">
                    <thead>
                        <tr>
                            <th style="min-width:9rem;">Item</th>
                            <th style="min-width:6rem;">Quantity</th>
                            <th style="min-width:12rem;">Description</th>
                            <th style="min-width:8rem;">Unit price</th>
                            <th style="min-width:7rem;">Unit</th>
                            <th style="min-width:8rem;">Total</th>
                            <th style="width:3rem;"></th>
                        </tr>
                    </thead>
                    <tbody id="requisition-lines-body">
                        @foreach ($oldLines as $index => $line)
                            <tr class="req-line">
                                <td><input type="text" name="lines[{{ $index }}][item]" class="tich-input" required maxlength="255" placeholder="Item name" value="{{ $line['item'] ?? '' }}"></td>
                                <td><input type="number" name="lines[{{ $index }}][quantity]" class="tich-input js-line-qty" value="{{ $line['quantity'] ?? '1' }}" min="0.0001" step="any" required></td>
                                <td><input type="text" name="lines[{{ $index }}][description]" class="tich-input" maxlength="2000" placeholder="Optional" value="{{ $line['description'] ?? '' }}"></td>
                                <td><input type="number" name="lines[{{ $index }}][unit_price]" class="tich-input js-line-price" min="0" step="0.01" required placeholder="0.00" value="{{ $line['unit_price'] ?? '' }}"></td>
                                <td><input type="text" name="lines[{{ $index }}][unit_of_measure]" class="tich-input" maxlength="50" placeholder="e.g. pcs" value="{{ $line['unit_of_measure'] ?? '' }}"></td>
                                <td><input type="text" class="tich-input js-line-total" value="0.00" readonly tabindex="-1" aria-label="Line total"></td>
                                <td><button type="button" class="tich-btn tich-btn-ghost js-remove-line" title="Remove line" aria-label="Remove line">&times;</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align:right; font-weight:600;">Grand total (KES)</td>
                            <td><strong id="requisition-grand-total">0.00</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Save requisition draft</button>
        </div>
    </form>
@endsection

@section('scripts')
    @parent
    <script>
    (function () {
        var body = document.getElementById('requisition-lines-body');
        var addBtn = document.getElementById('requisition-add-line');
        var grandTotalEl = document.getElementById('requisition-grand-total');
        var budgetSelect = document.getElementById('budget_key');
        var budgetCodeInput = document.getElementById('budget_code');
        var requestedItemInput = document.getElementById('requested_item');
        var budgetLineInput = document.getElementById('budget_line');
        var unitCostInput = document.getElementById('estimated_unit_cost');
        var quantityInput = document.getElementById('quantity');
        var estimatedCostInput = document.getElementById('estimated_cost');
        var departmentSelect = document.getElementById('requesting_department_id');
        var hintEl = document.getElementById('budget-lines-hint');
        var linesUrl = @json($budgetLinesUrl);
        var hasLoadedFromBudget = {{ old('budget_key') || old('budget_code') ? 'true' : 'false' }};

        if (!body || !addBtn || !grandTotalEl || !budgetSelect) return;

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

        function syncHiddenTotals() {
            var sum = 0;
            var qtySum = 0;
            var names = [];
            var firstPrice = null;

            body.querySelectorAll('.req-line').forEach(function (row, index) {
                var total = rowTotal(row);
                sum += total;
                var qty = parseFloat(row.querySelector('.js-line-qty')?.value || '0');
                if (isFinite(qty)) qtySum += qty;
                var item = (row.querySelector('input[name$="[item]"]')?.value || '').trim();
                if (item) names.push(item);
                if (index === 0) {
                    firstPrice = parseFloat(row.querySelector('.js-line-price')?.value || '0');
                }
                var totalInput = row.querySelector('.js-line-total');
                if (totalInput) totalInput.value = formatMoney(total);
            });

            grandTotalEl.textContent = formatMoney(sum);
            if (estimatedCostInput) estimatedCostInput.value = (Math.round((sum + Number.EPSILON) * 100) / 100).toFixed(2);
            if (quantityInput) quantityInput.value = qtySum > 0 ? String(qtySum) : '';
            if (unitCostInput) unitCostInput.value = isFinite(firstPrice) ? String(firstPrice) : '';
            if (budgetLineInput) budgetLineInput.value = names[0] || '';
            if (requestedItemInput) {
                if (names.length <= 1) {
                    requestedItemInput.value = names[0] || '';
                } else {
                    requestedItemInput.value = names.slice(0, 2).join(', ') + (names.length > 2 ? ' +' + (names.length - 2) + ' more' : '');
                }
            }
        }

        function reindexRows() {
            body.querySelectorAll('.req-line').forEach(function (row, index) {
                row.querySelectorAll('input[name^="lines["]').forEach(function (input) {
                    input.name = input.name.replace(/lines\[\d+]/, 'lines[' + index + ']');
                });
            });
        }

        function bindRow(row) {
            row.querySelectorAll('.js-line-qty, .js-line-price, input[name$="[item]"]').forEach(function (input) {
                input.addEventListener('input', syncHiddenTotals);
                input.addEventListener('change', syncHiddenTotals);
            });
            var removeBtn = row.querySelector('.js-remove-line');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    if (body.querySelectorAll('.req-line').length <= 1) {
                        row.querySelectorAll('input').forEach(function (input) {
                            if (input.classList.contains('js-line-total')) return;
                            if (input.classList.contains('js-line-qty')) {
                                input.value = '1';
                            } else {
                                input.value = '';
                            }
                        });
                        syncHiddenTotals();
                        return;
                    }
                    row.remove();
                    reindexRows();
                    syncHiddenTotals();
                });
            }
        }

        function addRow(data) {
            data = data || {};
            var index = body.querySelectorAll('.req-line').length;
            var row = document.createElement('tr');
            row.className = 'req-line';
            row.innerHTML =
                '<td><input type="text" name="lines[' + index + '][item]" class="tich-input" required maxlength="255" placeholder="Item name" value="' + escapeAttr(data.item || '') + '"></td>' +
                '<td><input type="number" name="lines[' + index + '][quantity]" class="tich-input js-line-qty" value="' + escapeAttr(data.quantity != null ? data.quantity : '1') + '" min="0.0001" step="any" required></td>' +
                '<td><input type="text" name="lines[' + index + '][description]" class="tich-input" maxlength="2000" placeholder="Optional" value="' + escapeAttr(data.description || '') + '"></td>' +
                '<td><input type="number" name="lines[' + index + '][unit_price]" class="tich-input js-line-price" min="0" step="0.01" required placeholder="0.00" value="' + escapeAttr(data.unit_price != null ? data.unit_price : '') + '"></td>' +
                '<td><input type="text" name="lines[' + index + '][unit_of_measure]" class="tich-input" maxlength="50" placeholder="e.g. pcs" value="' + escapeAttr(data.unit_of_measure || '') + '"></td>' +
                '<td><input type="text" class="tich-input js-line-total" value="0.00" readonly tabindex="-1" aria-label="Line total"></td>' +
                '<td><button type="button" class="tich-btn tich-btn-ghost js-remove-line" title="Remove line" aria-label="Remove line">&times;</button></td>';
            body.appendChild(row);
            bindRow(row);
            return row;
        }

        function escapeAttr(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function clearRows() {
            body.innerHTML = '';
        }

        function setDepartment(departmentId) {
            if (!departmentSelect || !departmentId) return;
            var value = String(departmentId);
            if (departmentSelect.value === value) return;
            departmentSelect.value = value;
            departmentSelect.dispatchEvent(new Event('change', { bubbles: true }));
            if (window.TichSelect) window.TichSelect.refresh();
        }

        function loadBudgetLines() {
            var option = budgetSelect.options[budgetSelect.selectedIndex];
            if (!option || !option.value) {
                if (budgetCodeInput) budgetCodeInput.value = '';
                return;
            }

            var source = option.getAttribute('data-source');
            var id = option.getAttribute('data-id');
            var code = option.getAttribute('data-code') || '';
            var departmentId = option.getAttribute('data-department-id');

            if (budgetCodeInput) budgetCodeInput.value = code;
            setDepartment(departmentId);

            if (!source || !id) return;

            if (hasLoadedFromBudget && body.querySelector('input[name$="[item]"]')?.value) {
                // Keep old() lines on first paint after validation errors.
                hasLoadedFromBudget = false;
                syncHiddenTotals();
                return;
            }

            if (hintEl) hintEl.textContent = 'Loading budget line items…';

            fetch(linesUrl + '?source=' + encodeURIComponent(source) + '&id=' + encodeURIComponent(id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Failed to load budget lines');
                    return response.json();
                })
                .then(function (data) {
                    clearRows();
                    var lines = Array.isArray(data.lines) ? data.lines : [];
                    if (lines.length === 0) {
                        addRow();
                        if (hintEl) {
                            hintEl.textContent = 'This budget has no saved line items. Add them manually below.';
                        }
                    } else {
                        lines.forEach(function (line) { addRow(line); });
                        if (hintEl) {
                            hintEl.textContent = 'Loaded ' + lines.length + ' line item' + (lines.length === 1 ? '' : 's') + ' from ' + (data.budget_code || 'budget') + '. Edit as needed.';
                        }
                    }
                    syncHiddenTotals();
                })
                .catch(function () {
                    if (body.querySelectorAll('.req-line').length === 0) addRow();
                    if (hintEl) hintEl.textContent = 'Could not load budget lines. You can still enter items manually.';
                    syncHiddenTotals();
                });
        }

        body.querySelectorAll('.req-line').forEach(bindRow);
        addBtn.addEventListener('click', function () {
            addRow();
            syncHiddenTotals();
            body.querySelector('.req-line:last-child input[name$="[item]"]')?.focus();
        });
        budgetSelect.addEventListener('change', function () {
            hasLoadedFromBudget = false;
            loadBudgetLines();
        });
        syncHiddenTotals();

        if (budgetSelect.value && !{{ old('lines') ? 'true' : 'false' }}) {
            loadBudgetLines();
        }
    })();
    </script>
@endsection
