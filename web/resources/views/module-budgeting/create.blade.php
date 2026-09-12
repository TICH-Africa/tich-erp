@extends($moduleContext['layout'])

@section('title', $department->dept_name.' · '.$pageTitle)

@section($moduleContext['content_section'])
    <x-page-toolbar
        :title="$pageTitle"
        meta="Budget routes to Administration/Finance; technical plan routes concurrently to M&E"
    >
        <x-slot:actions>
            <a href="{{ route($indexRoute) }}" class="tich-btn tich-btn-ghost">Back to budgeting</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (!($mePolicySigned ?? true) && ($mePolicy ?? null))
        <div class="tich-alert tich-alert--error tich-mt-4">
            <strong>M&amp;E policy sign-off required.</strong>
            HODs must digitally sign
            <em>{{ $mePolicy->title }}</em>
            ({{ $mePolicy->fiscal_year }}) before submitting annual budgets and departmental plans.
            <a href="{{ route($mePolicySignRoute ?? 'monitoring_evaluation.policy.sign') }}" class="tich-link">Sign the policy now</a>
        </div>
    @elseif ($mePolicy ?? null)
        <div class="tich-alert tich-alert--info tich-mt-4">
            Dual submission: budget → Admin then Finance; technical plan → M&amp;E. Baseline locks after M&amp;E and budget approvals.
        </div>
    @endif

    @if ($budgetRequest?->status === 'returned' && $budgetRequest->workflow_notes)
        <div class="tich-alert tich-alert--info tich-mt-4">
            <strong>Returned by Administration.</strong> Update the request and resubmit.
            <pre class="tich-pre tich-mt-2" style="white-space:pre-wrap; margin:0;">{{ $budgetRequest->workflow_notes }}</pre>
        </div>
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

    <form method="POST" action="{{ $formAction }}" class="tich-mt-6" id="dept-budget-form">
        @csrf
        @if ($budgetRequest)
            @method('PUT')
        @endif

        <div class="tich-card tich-form-stack">
            <div class="tich-form-group">
                <label class="tich-label">Department</label>
                <input type="text" class="tich-input" value="{{ $department->dept_name }} ({{ $department->dept_code }})" disabled>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="title">Title <span class="tich-text--danger">*</span></label>
                    <input type="text" id="title" name="title" class="tich-input" value="{{ old('title', $budgetRequest?->title) }}" required maxlength="300" placeholder="e.g. FY2026 operations budget">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="budget_type">Budget type</label>
                    <select id="budget_type" name="budget_type" class="tich-input">
                        <option value="">Optional</option>
                        @foreach (['annual', 'quarterly', 'monthly', 'weekly'] as $type)
                            <option value="{{ $type }}" @selected(old('budget_type', $budgetRequest?->budget_type) === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="planning_cycle_id">Planning cycle</label>
                <select id="planning_cycle_id" name="planning_cycle_id" class="tich-input">
                    <option value="">Optional</option>
                    @foreach ($cycles as $cycle)
                        <option value="{{ $cycle->id }}" @selected(old('planning_cycle_id', $budgetRequest?->planning_cycle_id) == $cycle->id)>{{ $cycle->cycle_code }} - {{ $cycle->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="justification">Justification</label>
                <textarea id="justification" name="justification" class="tich-input" rows="3" maxlength="3000" placeholder="Why this budget is needed">{{ old('justification', $budgetRequest?->justification) }}</textarea>
            </div>
        </div>

        <div class="tich-card tich-table-panel tich-mt-6">
            <div class="tich-flex-wrap" style="justify-content: space-between; align-items: center; gap: 0.75rem;">
                <div>
                    <h2 class="tich-h3" style="margin:0;">Line items</h2>
                    <p class="tich-caption tich-mt-1" style="margin:0;">Row total = quantity × price per item</p>
                </div>
                <button type="button" class="tich-btn tich-btn-secondary" id="dept-budget-add-line">+ Add line</button>
            </div>

            <div class="tich-table-wrap tich-mt-4">
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
                                <td>
                                    <input type="text" name="lines[{{ $index }}][item]" class="tich-input" value="{{ $line['item'] ?? '' }}" required maxlength="255" placeholder="Item name">
                                </td>
                                <td>
                                    <input type="number" name="lines[{{ $index }}][quantity]" class="tich-input js-line-qty" value="{{ $line['quantity'] ?? '1' }}" min="0.0001" step="any" required>
                                </td>
                                <td>
                                    <input type="text" name="lines[{{ $index }}][description]" class="tich-input" value="{{ $line['description'] ?? '' }}" maxlength="2000" placeholder="Optional">
                                </td>
                                <td>
                                    <input type="number" name="lines[{{ $index }}][unit_price]" class="tich-input js-line-price" value="{{ $line['unit_price'] ?? '' }}" min="0" step="0.01" required placeholder="0.00">
                                </td>
                                <td>
                                    <input type="text" name="lines[{{ $index }}][unit_of_measure]" class="tich-input" value="{{ $line['unit_of_measure'] ?? '' }}" maxlength="50" placeholder="e.g. pcs">
                                </td>
                                <td>
                                    <input type="text" class="tich-input js-line-total" value="0.00" readonly tabindex="-1" aria-label="Line total">
                                </td>
                                <td>
                                    <button type="button" class="tich-btn tich-btn-ghost js-remove-line" title="Remove line" aria-label="Remove line">&times;</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align:right; font-weight:600;">Grand total (KES)</td>
                            <td>
                                <strong id="dept-budget-grand-total">0.00</strong>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="tich-card tich-table-panel tich-mt-6">
            <div class="tich-flex-wrap" style="justify-content: space-between; align-items: center; gap: 0.75rem;">
                <div>
                    <h2 class="tich-h3" style="margin:0;">Technical plan (M&amp;E)</h2>
                    <p class="tich-caption tich-mt-1" style="margin:0;">Standard grid: Output · Activity · Costable item · Planned baseline. Routes to M&amp;E concurrently.</p>
                </div>
                <button type="button" class="tich-btn tich-btn-secondary" id="dept-plan-add-line">+ Add output</button>
            </div>

            <div class="tich-form-group tich-mt-4">
                <label class="tich-label" for="plan_summary">Plan summary</label>
                <textarea id="plan_summary" name="plan_summary" class="tich-input" rows="2" maxlength="5000" placeholder="Optional narrative for the annual departmental plan">{{ old('plan_summary') }}</textarea>
            </div>

            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table" id="dept-plan-lines-table">
                    <thead>
                        <tr>
                            <th style="min-width:12rem;">Output</th>
                            <th style="min-width:12rem;">Activity</th>
                            <th style="min-width:10rem;">Costable item</th>
                            <th style="min-width:7rem;">Planned</th>
                            <th style="min-width:6rem;">Unit</th>
                            <th style="width:3rem;"></th>
                        </tr>
                    </thead>
                    <tbody id="dept-plan-lines-body">
                        @foreach (($planOutputs ?? [['output'=>'','activity'=>'','costable_item'=>'','planned'=>'','planned_unit'=>'']]) as $pi => $pLine)
                            <tr class="dept-plan-line">
                                <td><input type="text" name="plan_outputs[{{ $pi }}][output]" class="tich-input" value="{{ $pLine['output'] ?? '' }}" required maxlength="2000" placeholder="Strategic target"></td>
                                <td><input type="text" name="plan_outputs[{{ $pi }}][activity]" class="tich-input" value="{{ $pLine['activity'] ?? '' }}" required maxlength="2000" placeholder="Implementation task"></td>
                                <td><input type="text" name="plan_outputs[{{ $pi }}][costable_item]" class="tich-input" value="{{ $pLine['costable_item'] ?? '' }}" maxlength="500" placeholder="Budget code / resource"></td>
                                <td><input type="number" name="plan_outputs[{{ $pi }}][planned]" class="tich-input" value="{{ $pLine['planned'] ?? '' }}" min="0" step="0.01" required placeholder="0"></td>
                                <td><input type="text" name="plan_outputs[{{ $pi }}][planned_unit]" class="tich-input" value="{{ $pLine['planned_unit'] ?? '' }}" maxlength="50" placeholder="e.g. students"></td>
                                <td><button type="button" class="tich-btn tich-btn-ghost js-remove-plan-line" title="Remove" aria-label="Remove">&times;</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tich-flex-wrap tich-mt-6" style="gap:0.75rem; justify-content:flex-end;">
            <a href="{{ route($indexRoute) }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary" @if(!($mePolicySigned ?? true)) disabled @endif>{{ $submitLabel }}</button>
        </div>
    </form>

    <script>
    (function () {
        var body = document.getElementById('dept-budget-lines-body');
        var addBtn = document.getElementById('dept-budget-add-line');
        var grandTotalEl = document.getElementById('dept-budget-grand-total');
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

        var planBody = document.getElementById('dept-plan-lines-body');
        var planAdd = document.getElementById('dept-plan-add-line');
        if (planBody && planAdd) {
            function reindexPlanRows() {
                planBody.querySelectorAll('.dept-plan-line').forEach(function (row, index) {
                    row.querySelectorAll('input[name^="plan_outputs["]').forEach(function (input) {
                        input.name = input.name.replace(/plan_outputs\[\d+]/, 'plan_outputs[' + index + ']');
                    });
                });
            }
            function bindPlanRow(row) {
                var removeBtn = row.querySelector('.js-remove-plan-line');
                if (!removeBtn) return;
                removeBtn.addEventListener('click', function () {
                    if (planBody.querySelectorAll('.dept-plan-line').length <= 1) {
                        row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                        return;
                    }
                    row.remove();
                    reindexPlanRows();
                });
            }
            planAdd.addEventListener('click', function () {
                var index = planBody.querySelectorAll('.dept-plan-line').length;
                var row = document.createElement('tr');
                row.className = 'dept-plan-line';
                row.innerHTML =
                    '<td><input type="text" name="plan_outputs[' + index + '][output]" class="tich-input" required maxlength="2000" placeholder="Strategic target"></td>' +
                    '<td><input type="text" name="plan_outputs[' + index + '][activity]" class="tich-input" required maxlength="2000" placeholder="Implementation task"></td>' +
                    '<td><input type="text" name="plan_outputs[' + index + '][costable_item]" class="tich-input" maxlength="500" placeholder="Budget code / resource"></td>' +
                    '<td><input type="number" name="plan_outputs[' + index + '][planned]" class="tich-input" min="0" step="0.01" required placeholder="0"></td>' +
                    '<td><input type="text" name="plan_outputs[' + index + '][planned_unit]" class="tich-input" maxlength="50" placeholder="e.g. students"></td>' +
                    '<td><button type="button" class="tich-btn tich-btn-ghost js-remove-plan-line" title="Remove" aria-label="Remove">&times;</button></td>';
                planBody.appendChild(row);
                bindPlanRow(row);
            });
            planBody.querySelectorAll('.dept-plan-line').forEach(bindPlanRow);
        }
    })();
    </script>
@endsection
