@extends($moduleContext['layout'])

@section('title', $department->dept_name.' · '.$pageTitle)

@section($moduleContext['content_section'])
    <x-page-toolbar
        :title="$pageTitle"
        meta="Annual budgets use Q1–Q4 income and expenditure lines. Submits to Administration/Finance."
    >
        <x-slot:actions>
            <a href="{{ route($indexRoute) }}" class="tich-btn tich-btn-ghost">Back to budgeting</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (!($financePolicySigned ?? true) && ($financePolicy ?? null))
        <div class="tich-alert tich-alert--error tich-mt-4">
            <strong>Financial policy sign-off required.</strong>
            HODs must digitally sign
            <em>{{ $financePolicy->title }}</em>
            ({{ $financePolicy->fiscal_year }}) before submitting annual budgets.
            <a href="{{ route($financePolicySignRoute ?? 'finance.financial-policies.sign') }}" class="tich-link">Sign the policy now</a>
        </div>
    @elseif ($financePolicy ?? null)
        <div class="tich-alert tich-alert--info tich-mt-4">
            Budget → Administration then Finance. Technical plans are submitted separately under Technical plan.
        </div>
    @endif

    @if ($budgetRequest?->status === 'returned' && $budgetRequest->workflow_notes)
        <div class="tich-alert tich-alert--info tich-mt-4">
            <strong>Returned by Administration.</strong> Update the request and resubmit.
            <pre class="tich-pre tich-mt-2" style="white-space:pre-wrap; margin:0;">{{ $budgetRequest->workflow_notes }}</pre>
        </div>
    @endif

    @php
        $selectedType = old('budget_type', $budgetRequest?->budget_type ?: 'annual');
        $selectedCycleId = old('planning_cycle_id', $budgetRequest?->planning_cycle_id);
        $selectedFy = '';
        foreach ($cycles as $cycle) {
            if ((string) $cycle->id === (string) $selectedCycleId) {
                $selectedFy = $cycle->fiscal_year ?? $cycle->cycle_code ?? '';
                break;
            }
        }
    @endphp

    <div class="uf-form">
        <form method="POST" action="{{ $formAction }}" data-uf="ready" id="dept-budget-form" data-tich-autosave="1">
            @csrf
            @if ($budgetRequest)
                @method('PUT')
            @endif

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">BGT · {{ $department->dept_code }}</div>
                    <div class="uf-amount-bar__sum">{{ $department->dept_name }}</div>
                </div>
                <span class="uf-badge">{{ $budgetRequest ? 'Edit' : 'Draft' }}</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Budget Request</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label>Department</label>
                        <input type="text" value="{{ $department->dept_name }} ({{ $department->dept_code }})" disabled>
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="title">Title <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="{{ old('title', $budgetRequest?->title) }}"
                                required
                                maxlength="300"
                                placeholder="e.g. FY2026 operations budget"
                                class="{{ $errors->has('title') ? 'is-invalid' : '' }}"
                            >
                            @error('title')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="budget_type">Budget type <span class="uf-req">*</span></label>
                            <select
                                id="budget_type"
                                name="budget_type"
                                class="{{ $errors->has('budget_type') ? 'is-invalid' : '' }}"
                            >
                                @foreach (['annual', 'quarterly', 'monthly', 'weekly'] as $type)
                                    <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                            <p class="uf-hint">Annual uses Q1–Q4 income and expenditure. Other types keep a single line list for now.</p>
                            @error('budget_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="planning_cycle_id">Planning cycle <span class="uf-req">*</span></label>
                            <select
                                id="planning_cycle_id"
                                name="planning_cycle_id"
                                required
                                class="{{ $errors->has('planning_cycle_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="" data-fy="">Select planning cycle</option>
                                @foreach ($cycles as $cycle)
                                    <option
                                        value="{{ $cycle->id }}"
                                        data-fy="{{ $cycle->fiscal_year ?? $cycle->cycle_code }}"
                                        @selected((string) $selectedCycleId === (string) $cycle->id)
                                    >{{ $cycle->cycle_code }} — {{ $cycle->title }}</option>
                                @endforeach
                            </select>
                            @error('planning_cycle_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="fiscal_year_display">Fiscal year</label>
                            <input
                                type="text"
                                id="fiscal_year_display"
                                value="{{ $selectedFy }}"
                                readonly
                                tabindex="-1"
                                placeholder="From planning cycle"
                            >
                            <p class="uf-hint">Read-only — taken from the selected planning cycle.</p>
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="justification">Justification</label>
                        <textarea
                            id="justification"
                            name="justification"
                            rows="3"
                            maxlength="3000"
                            placeholder="Why this budget is needed"
                            class="{{ $errors->has('justification') ? 'is-invalid' : '' }}"
                        >{{ old('justification', $budgetRequest?->justification) }}</textarea>
                        @error('justification')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div id="annual-quarters-panel" @if($selectedType !== 'annual') hidden @endif>
                @foreach ($quarters as $qKey => $qLabel)
                    @php
                        $qBlock = $quarterData[$qKey] ?? ['income' => [['source'=>'','amount'=>'']], 'expenditure' => [['item'=>'','quantity'=>'1','description'=>'','unit_price'=>'','unit_of_measure'=>'']]];
                        $qIncome = $qBlock['income'] ?? [['source'=>'','amount'=>'']];
                        $qExp = $qBlock['expenditure'] ?? [['item'=>'','quantity'=>'1','description'=>'','unit_price'=>'','unit_of_measure'=>'']];
                    @endphp
                    <div class="uf-form-section js-quarter-block" data-quarter="{{ $qKey }}">
                        <div class="uf-section-head">{{ $qLabel }}</div>
                        <div class="uf-section-body">
                            <div class="tich-flex-wrap" style="justify-content:space-between;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                                <strong>Income</strong>
                                <button type="button" class="uf-btn uf-btn-secondary js-add-income" data-quarter="{{ $qKey }}">+ Add income source</button>
                            </div>
                            <div class="tich-table-wrap">
                                <table class="tich-admin-table">
                                    <thead>
                                        <tr>
                                            <th style="min-width:14rem;">Income source</th>
                                            <th style="min-width:8rem;">Amount (KES)</th>
                                            <th style="width:3rem;"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="js-income-body" data-quarter="{{ $qKey }}">
                                        @foreach ($qIncome as $ii => $iRow)
                                            <tr class="js-income-row">
                                                <td>
                                                    <input type="text" name="quarters[{{ $qKey }}][income][{{ $ii }}][source]" class="tich-input" value="{{ $iRow['source'] ?? '' }}" maxlength="255" placeholder="e.g. Tuition fees">
                                                </td>
                                                <td>
                                                    <input type="number" name="quarters[{{ $qKey }}][income][{{ $ii }}][amount]" class="tich-input js-income-amount" value="{{ $iRow['amount'] ?? '' }}" min="0" step="0.01" placeholder="0.00">
                                                </td>
                                                <td>
                                                    <button type="button" class="tich-btn tich-btn-ghost js-remove-income" title="Remove" aria-label="Remove">&times;</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td style="text-align:right;font-weight:600;">Quarter income</td>
                                            <td><strong class="js-q-income-total">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="tich-flex-wrap" style="justify-content:space-between;align-items:center;gap:0.75rem;margin:1.25rem 0 0.75rem;">
                                <strong>Expenditure</strong>
                                <button type="button" class="uf-btn uf-btn-secondary js-add-exp" data-quarter="{{ $qKey }}">+ Add expenditure line</button>
                            </div>
                            <div class="tich-table-wrap">
                                <table class="tich-admin-table">
                                    <thead>
                                        <tr>
                                            <th style="min-width:9rem;">Item</th>
                                            <th style="min-width:6rem;">Quantity</th>
                                            <th style="min-width:10rem;">Description</th>
                                            <th style="min-width:8rem;">Price per item</th>
                                            <th style="min-width:7rem;">Unit</th>
                                            <th style="min-width:8rem;">Total</th>
                                            <th style="width:3rem;"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="js-exp-body" data-quarter="{{ $qKey }}">
                                        @foreach ($qExp as $ei => $eRow)
                                            <tr class="js-exp-row">
                                                <td><input type="text" name="quarters[{{ $qKey }}][expenditure][{{ $ei }}][item]" class="tich-input" value="{{ $eRow['item'] ?? '' }}" maxlength="255" placeholder="Item name"></td>
                                                <td><input type="number" name="quarters[{{ $qKey }}][expenditure][{{ $ei }}][quantity]" class="tich-input js-line-qty" value="{{ $eRow['quantity'] ?? '1' }}" min="0" step="any"></td>
                                                <td><input type="text" name="quarters[{{ $qKey }}][expenditure][{{ $ei }}][description]" class="tich-input" value="{{ $eRow['description'] ?? '' }}" maxlength="2000" placeholder="Optional"></td>
                                                <td><input type="number" name="quarters[{{ $qKey }}][expenditure][{{ $ei }}][unit_price]" class="tich-input js-line-price" value="{{ $eRow['unit_price'] ?? '' }}" min="0" step="0.01" placeholder="0.00"></td>
                                                <td><input type="text" name="quarters[{{ $qKey }}][expenditure][{{ $ei }}][unit_of_measure]" class="tich-input" value="{{ $eRow['unit_of_measure'] ?? '' }}" maxlength="50" placeholder="e.g. pcs"></td>
                                                <td><input type="text" class="tich-input js-line-total" value="0.00" readonly tabindex="-1" aria-label="Line total"></td>
                                                <td><button type="button" class="tich-btn tich-btn-ghost js-remove-exp" title="Remove" aria-label="Remove">&times;</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" style="text-align:right;font-weight:600;">Quarter expenditure</td>
                                            <td><strong class="js-q-exp-total">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="uf-form-section">
                    <div class="uf-section-head">Annual totals</div>
                    <div class="uf-section-body">
                        <div class="uf-form-grid-2">
                            <div class="uf-field">
                                <label>Total income (KES)</label>
                                <input type="text" id="annual-income-grand" value="0.00" readonly tabindex="-1">
                            </div>
                            <div class="uf-field">
                                <label>Total expenditure (KES)</label>
                                <input type="text" id="annual-exp-grand" value="0.00" readonly tabindex="-1">
                            </div>
                        </div>
                        @error('budget')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div id="flat-lines-panel" class="uf-form-section" @if($selectedType === 'annual') hidden @endif>
                <div class="uf-section-head">Line Items</div>
                <div class="uf-section-body">
                    <div class="tich-flex-wrap" style="justify-content: space-between; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <p class="uf-hint" style="margin:0;">Row total = quantity × price per item</p>
                        <button type="button" class="uf-btn uf-btn-secondary" id="dept-budget-add-line">+ Add line</button>
                    </div>
                    <div class="tich-table-wrap">
                        <table class="tich-admin-table" id="dept-budget-lines-table">
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
                            <tbody id="dept-budget-lines-body">
                                @foreach ($lines as $index => $line)
                                    <tr class="dept-budget-line">
                                        <td><input type="text" name="lines[{{ $index }}][item]" class="tich-input" value="{{ $line['item'] ?? '' }}" maxlength="255" placeholder="Item name"></td>
                                        <td><input type="number" name="lines[{{ $index }}][quantity]" class="tich-input js-line-qty" value="{{ $line['quantity'] ?? '1' }}" min="0.0001" step="any"></td>
                                        <td><input type="text" name="lines[{{ $index }}][description]" class="tich-input" value="{{ $line['description'] ?? '' }}" maxlength="2000" placeholder="Optional"></td>
                                        <td><input type="number" name="lines[{{ $index }}][unit_price]" class="tich-input js-line-price" value="{{ $line['unit_price'] ?? '' }}" min="0" step="0.01" placeholder="0.00"></td>
                                        <td><input type="text" name="lines[{{ $index }}][unit_of_measure]" class="tich-input" value="{{ $line['unit_of_measure'] ?? '' }}" maxlength="50" placeholder="e.g. pcs"></td>
                                        <td><input type="text" class="tich-input js-line-total" value="0.00" readonly tabindex="-1" aria-label="Line total"></td>
                                        <td><button type="button" class="tich-btn tich-btn-ghost js-remove-line" title="Remove line" aria-label="Remove line">&times;</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align:right; font-weight:600;">Grand total (KES)</td>
                                    <td><strong id="dept-budget-grand-total">0.00</strong></td>
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
                        <a href="{{ route($indexRoute) }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        <button type="submit" class="uf-btn uf-btn-primary" @if(!($financePolicySigned ?? true)) disabled @endif>{{ $submitLabel }}</button>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <script>
    (function () {
        function formatMoney(value) {
            return (Math.round((value + Number.EPSILON) * 100) / 100).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        var cycleSelect = document.getElementById('planning_cycle_id');
        var fyDisplay = document.getElementById('fiscal_year_display');
        function syncFy() {
            if (!cycleSelect || !fyDisplay) return;
            var opt = cycleSelect.options[cycleSelect.selectedIndex];
            fyDisplay.value = opt ? (opt.getAttribute('data-fy') || '') : '';
        }
        if (cycleSelect) {
            cycleSelect.addEventListener('change', syncFy);
            syncFy();
        }

        var typeSelect = document.getElementById('budget_type');
        var annualPanel = document.getElementById('annual-quarters-panel');
        var flatPanel = document.getElementById('flat-lines-panel');
        function syncTypePanels() {
            var isAnnual = !typeSelect || typeSelect.value === 'annual';
            if (annualPanel) annualPanel.hidden = !isAnnual;
            if (flatPanel) flatPanel.hidden = isAnnual;
        }
        if (typeSelect) {
            typeSelect.addEventListener('change', syncTypePanels);
            syncTypePanels();
        }

        function refreshAnnualTotals() {
            var incomeGrand = 0;
            var expGrand = 0;
            document.querySelectorAll('.js-quarter-block').forEach(function (block) {
                var incomeSum = 0;
                block.querySelectorAll('.js-income-amount').forEach(function (input) {
                    var v = parseFloat(input.value || '0');
                    if (isFinite(v)) incomeSum += v;
                });
                var incomeTotalEl = block.querySelector('.js-q-income-total');
                if (incomeTotalEl) incomeTotalEl.textContent = formatMoney(incomeSum);
                incomeGrand += incomeSum;

                var expSum = 0;
                block.querySelectorAll('.js-exp-row').forEach(function (row) {
                    var qty = parseFloat(row.querySelector('.js-line-qty')?.value || '0');
                    var price = parseFloat(row.querySelector('.js-line-price')?.value || '0');
                    var total = (isFinite(qty) && isFinite(price)) ? qty * price : 0;
                    expSum += total;
                    var totalInput = row.querySelector('.js-line-total');
                    if (totalInput) totalInput.value = formatMoney(total);
                });
                var expTotalEl = block.querySelector('.js-q-exp-total');
                if (expTotalEl) expTotalEl.textContent = formatMoney(expSum);
                expGrand += expSum;
            });
            var ig = document.getElementById('annual-income-grand');
            var eg = document.getElementById('annual-exp-grand');
            if (ig) ig.value = formatMoney(incomeGrand);
            if (eg) eg.value = formatMoney(expGrand);
        }

        function reindexIncome(body, qKey) {
            body.querySelectorAll('.js-income-row').forEach(function (row, index) {
                row.querySelectorAll('input[name*="[income]"]').forEach(function (input) {
                    input.name = input.name.replace(
                        /quarters\[[^\]]+\]\[income\]\[\d+\]/,
                        'quarters[' + qKey + '][income][' + index + ']'
                    );
                });
            });
        }

        function reindexExp(body, qKey) {
            body.querySelectorAll('.js-exp-row').forEach(function (row, index) {
                row.querySelectorAll('input[name*="[expenditure]"]').forEach(function (input) {
                    input.name = input.name.replace(
                        /quarters\[[^\]]+\]\[expenditure\]\[\d+\]/,
                        'quarters[' + qKey + '][expenditure][' + index + ']'
                    );
                });
            });
        }

        function bindIncomeRow(row, body, qKey) {
            row.querySelectorAll('.js-income-amount').forEach(function (input) {
                input.addEventListener('input', refreshAnnualTotals);
                input.addEventListener('change', refreshAnnualTotals);
            });
            var removeBtn = row.querySelector('.js-remove-income');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    if (body.querySelectorAll('.js-income-row').length <= 1) {
                        row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                        refreshAnnualTotals();
                        return;
                    }
                    row.remove();
                    reindexIncome(body, qKey);
                    refreshAnnualTotals();
                });
            }
        }

        function bindExpRow(row, body, qKey) {
            row.querySelectorAll('.js-line-qty, .js-line-price').forEach(function (input) {
                input.addEventListener('input', refreshAnnualTotals);
                input.addEventListener('change', refreshAnnualTotals);
            });
            var removeBtn = row.querySelector('.js-remove-exp');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    if (body.querySelectorAll('.js-exp-row').length <= 1) {
                        row.querySelectorAll('input').forEach(function (input) {
                            if (input.classList.contains('js-line-total')) return;
                            if (input.classList.contains('js-line-qty')) input.value = '1';
                            else input.value = '';
                        });
                        refreshAnnualTotals();
                        return;
                    }
                    row.remove();
                    reindexExp(body, qKey);
                    refreshAnnualTotals();
                });
            }
        }

        document.querySelectorAll('.js-quarter-block').forEach(function (block) {
            var qKey = block.getAttribute('data-quarter');
            var incomeBody = block.querySelector('.js-income-body');
            var expBody = block.querySelector('.js-exp-body');
            var addIncome = block.querySelector('.js-add-income');
            var addExp = block.querySelector('.js-add-exp');

            if (incomeBody) {
                incomeBody.querySelectorAll('.js-income-row').forEach(function (row) {
                    bindIncomeRow(row, incomeBody, qKey);
                });
            }
            if (expBody) {
                expBody.querySelectorAll('.js-exp-row').forEach(function (row) {
                    bindExpRow(row, expBody, qKey);
                });
            }
            if (addIncome && incomeBody) {
                addIncome.addEventListener('click', function () {
                    var index = incomeBody.querySelectorAll('.js-income-row').length;
                    var row = document.createElement('tr');
                    row.className = 'js-income-row';
                    row.innerHTML =
                        '<td><input type="text" name="quarters[' + qKey + '][income][' + index + '][source]" class="tich-input" maxlength="255" placeholder="e.g. Tuition fees"></td>' +
                        '<td><input type="number" name="quarters[' + qKey + '][income][' + index + '][amount]" class="tich-input js-income-amount" min="0" step="0.01" placeholder="0.00"></td>' +
                        '<td><button type="button" class="tich-btn tich-btn-ghost js-remove-income" title="Remove" aria-label="Remove">&times;</button></td>';
                    incomeBody.appendChild(row);
                    bindIncomeRow(row, incomeBody, qKey);
                    refreshAnnualTotals();
                });
            }
            if (addExp && expBody) {
                addExp.addEventListener('click', function () {
                    var index = expBody.querySelectorAll('.js-exp-row').length;
                    var row = document.createElement('tr');
                    row.className = 'js-exp-row';
                    row.innerHTML =
                        '<td><input type="text" name="quarters[' + qKey + '][expenditure][' + index + '][item]" class="tich-input" maxlength="255" placeholder="Item name"></td>' +
                        '<td><input type="number" name="quarters[' + qKey + '][expenditure][' + index + '][quantity]" class="tich-input js-line-qty" value="1" min="0" step="any"></td>' +
                        '<td><input type="text" name="quarters[' + qKey + '][expenditure][' + index + '][description]" class="tich-input" maxlength="2000" placeholder="Optional"></td>' +
                        '<td><input type="number" name="quarters[' + qKey + '][expenditure][' + index + '][unit_price]" class="tich-input js-line-price" min="0" step="0.01" placeholder="0.00"></td>' +
                        '<td><input type="text" name="quarters[' + qKey + '][expenditure][' + index + '][unit_of_measure]" class="tich-input" maxlength="50" placeholder="e.g. pcs"></td>' +
                        '<td><input type="text" class="tich-input js-line-total" value="0.00" readonly tabindex="-1" aria-label="Line total"></td>' +
                        '<td><button type="button" class="tich-btn tich-btn-ghost js-remove-exp" title="Remove" aria-label="Remove">&times;</button></td>';
                    expBody.appendChild(row);
                    bindExpRow(row, expBody, qKey);
                    refreshAnnualTotals();
                });
            }
        });
        refreshAnnualTotals();

        var body = document.getElementById('dept-budget-lines-body');
        var addBtn = document.getElementById('dept-budget-add-line');
        var grandTotalEl = document.getElementById('dept-budget-grand-total');
        if (body && addBtn && grandTotalEl) {
            function rowTotal(row) {
                var qty = parseFloat(row.querySelector('.js-line-qty')?.value || '0');
                var price = parseFloat(row.querySelector('.js-line-price')?.value || '0');
                if (!isFinite(qty) || !isFinite(price)) return 0;
                return qty * price;
            }
            function refreshFlatTotals() {
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
                    input.addEventListener('input', refreshFlatTotals);
                    input.addEventListener('change', refreshFlatTotals);
                });
                var removeBtn = row.querySelector('.js-remove-line');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        if (body.querySelectorAll('.dept-budget-line').length <= 1) {
                            row.querySelectorAll('input').forEach(function (input) {
                                if (input.classList.contains('js-line-total')) return;
                                if (input.classList.contains('js-line-qty')) input.value = '1';
                                else input.value = '';
                            });
                            refreshFlatTotals();
                            return;
                        }
                        row.remove();
                        reindexRows();
                        refreshFlatTotals();
                    });
                }
            }
            addBtn.addEventListener('click', function () {
                var index = body.querySelectorAll('.dept-budget-line').length;
                var row = document.createElement('tr');
                row.className = 'dept-budget-line';
                row.innerHTML =
                    '<td><input type="text" name="lines[' + index + '][item]" class="tich-input" maxlength="255" placeholder="Item name"></td>' +
                    '<td><input type="number" name="lines[' + index + '][quantity]" class="tich-input js-line-qty" value="1" min="0.0001" step="any"></td>' +
                    '<td><input type="text" name="lines[' + index + '][description]" class="tich-input" maxlength="2000" placeholder="Optional"></td>' +
                    '<td><input type="number" name="lines[' + index + '][unit_price]" class="tich-input js-line-price" min="0" step="0.01" placeholder="0.00"></td>' +
                    '<td><input type="text" name="lines[' + index + '][unit_of_measure]" class="tich-input" maxlength="50" placeholder="e.g. pcs"></td>' +
                    '<td><input type="text" class="tich-input js-line-total" value="0.00" readonly tabindex="-1" aria-label="Line total"></td>' +
                    '<td><button type="button" class="tich-btn tich-btn-ghost js-remove-line" title="Remove line" aria-label="Remove line">&times;</button></td>';
                body.appendChild(row);
                bindRow(row);
                refreshFlatTotals();
            });
            body.querySelectorAll('.dept-budget-line').forEach(bindRow);
            refreshFlatTotals();
        }
    })();
    </script>
@endsection
