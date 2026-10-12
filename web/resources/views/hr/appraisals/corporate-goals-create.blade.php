@extends('layouts.hr')

@section('title', 'Create corporate goals')

@section('hr-content')
    @php
        $oldScope = old('role_scope', 'all');
        $oldStaffIds = array_map('intval', old('scope_values', []));
        $oldGoals = old('goals', [['title' => '', 'description' => '', 'weight_hint' => '']]);
        if (! is_array($oldGoals) || count($oldGoals) < 1) {
            $oldGoals = [['title' => '', 'description' => '', 'weight_hint' => '']];
        }
    @endphp

    <x-page-toolbar title="Create cascading goals" meta="Set cycle and audience once, then add one or many goals in a single save">
        <x-slot:actions>
            <a href="{{ route('hr.appraisals.corporate-goals') }}" class="tich-btn tich-btn-ghost">Back to list</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--danger tich-mt-4">
            <ul class="tich-list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($cycles->isEmpty())
        <div class="tich-card tich-mt-6">
            <p class="tich-text">
                Create an
                <a href="{{ route('hr.appraisals.cycles') }}">appraisal cycle</a>
                before adding corporate goals.
            </p>
        </div>
    @else
        <div class="uf-form tich-corp-goal-create">
            <form method="POST" action="{{ route('hr.appraisals.corporate-goals.store') }}" id="corp-goal-form" data-uf="ready">
                @csrf

                <div class="uf-amount-bar">
                    <div>
                        <div class="uf-amount-bar__ref">CG · Cascading goals</div>
                        <div class="uf-amount-bar__sum">New goal batch</div>
                    </div>
                    <span class="uf-badge">Codes auto · CG-{{ now()->year }}-####</span>
                </div>

                <div class="uf-form-section">
                    <div class="uf-section-head">Audience &amp; cycle</div>
                    <div class="uf-section-body">
                        <div class="uf-form-grid-2">
                            <div class="uf-field">
                                <label for="corp-goal-cycle">Cycle <span class="uf-req">*</span></label>
                                <select id="corp-goal-cycle" name="cycle_id" required>
                                    <option value="" disabled @selected(! old('cycle_id', $cycleId))>Select cycle</option>
                                    @foreach ($cycles as $cycle)
                                        <option value="{{ $cycle->id }}" @selected((int) old('cycle_id', $cycleId) === $cycle->id)>{{ $cycle->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="uf-field">
                                <label for="corp-goal-scope">Assign to <span class="uf-req">*</span></label>
                                <select name="role_scope" id="corp-goal-scope" required>
                                    <option value="all" @selected($oldScope === 'all')>All employees</option>
                                    <option value="department" @selected($oldScope === 'department')>Specific departments</option>
                                    <option value="staff" @selected($oldScope === 'staff')>Individual employees</option>
                                </select>
                            </div>
                        </div>

                        <div class="tich-scope-panel" id="corp-goal-depts" @if($oldScope !== 'department') hidden @endif>
                            <div class="tich-scope-panel__head">
                                <label class="tich-scope-panel__label">Departments <span class="uf-req">*</span></label>
                                <span class="uf-hint" data-dept-picker-count>0 selected</span>
                            </div>
                            <div class="tich-staff-picker" data-dept-picker>
                                <input
                                    type="search"
                                    class="tich-staff-picker__search"
                                    placeholder="Search departments…"
                                    data-dept-picker-search
                                    autocomplete="off"
                                >
                                <div class="tich-staff-picker__list" role="listbox" aria-multiselectable="true">
                                    @forelse ($departments as $dept)
                                        <label class="tich-staff-picker__option" data-search="{{ mb_strtolower($dept->dept_name) }}">
                                            <input
                                                type="checkbox"
                                                name="scope_values[]"
                                                value="{{ $dept->id }}"
                                                @checked(in_array((int) $dept->id, $oldStaffIds, true))
                                                data-dept-picker-option
                                                @if($oldScope !== 'department') disabled @endif
                                            >
                                            <span class="tich-staff-picker__meta">
                                                <span class="tich-staff-picker__name">{{ $dept->dept_name }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="tich-caption tich-staff-picker__empty">No departments available.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="tich-scope-panel" id="corp-goal-staff" @if($oldScope !== 'staff') hidden @endif>
                            <div class="tich-scope-panel__head">
                                <label class="tich-scope-panel__label">Employees <span class="uf-req">*</span></label>
                                <span class="uf-hint" data-staff-picker-count>0 selected</span>
                            </div>
                            <div class="tich-staff-picker" data-staff-picker>
                                <input
                                    type="search"
                                    class="tich-staff-picker__search"
                                    placeholder="Search by name, employee no., job title, or role…"
                                    data-staff-picker-search
                                    autocomplete="off"
                                >
                                <div class="tich-staff-picker__list" data-staff-picker-list role="listbox" aria-multiselectable="true">
                                    @forelse ($staffOptions as $member)
                                        @php
                                            $roleNames = $rolesByStaffId[(int) $member->id]
                                                ?? $member->user?->roles?->pluck('role_name')->filter()->unique()->values()->all()
                                                ?? [];
                                            $rolesLabel = count($roleNames)
                                                ? implode(', ', $roleNames)
                                                : 'No roles assigned';
                                            $label = $member->fullName()
                                                .' ('.$member->employee_number.')'
                                                .' · '.($member->job_title ?: 'Staff');
                                            $search = mb_strtolower($label.' '.$rolesLabel);
                                        @endphp
                                        <label class="tich-staff-picker__option" data-search="{{ $search }}">
                                            <input
                                                type="checkbox"
                                                name="scope_values[]"
                                                value="{{ $member->id }}"
                                                @checked(in_array((int) $member->id, $oldStaffIds, true))
                                                data-staff-picker-option
                                                @if($oldScope !== 'staff') disabled @endif
                                            >
                                            <span class="tich-staff-picker__meta">
                                                <span class="tich-staff-picker__name">{{ $label }}</span>
                                                <span class="tich-staff-picker__roles">Roles: {{ $rolesLabel }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="tich-caption tich-staff-picker__empty">No staff available.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="uf-field uf-field--check tich-corp-goal-active">
                            <input
                                type="checkbox"
                                id="corp-goal-active"
                                name="is_active"
                                value="1"
                                class="tich-checkbox"
                                @checked(old('is_active', '1') === '1' || old('is_active') === true || old('is_active') === 1 || ! old())
                            >
                            <label for="corp-goal-active">Active (visible for employees to accept)</label>
                        </div>
                    </div>
                </div>

                <div class="uf-form-section">
                    <div class="uf-section-head">Goals to create</div>
                    <div class="uf-section-body">
                        <div class="tich-corp-goal-toolbar">
                            <p class="uf-hint" style="margin:0;">Each row is saved as its own cascading goal. Employees can accept them on their appraisal.</p>
                            <button type="button" class="uf-btn uf-btn-secondary" data-add-goal-row>+ Add another goal</button>
                        </div>

                        <div id="corp-goal-rows" class="tich-corp-goal-rows">
                            @foreach ($oldGoals as $index => $row)
                                <div class="tich-corp-goal-row" data-goal-row>
                                    <div class="tich-corp-goal-row__head">
                                        <strong data-goal-row-label>Goal {{ $index + 1 }}</strong>
                                        <button type="button" class="uf-btn uf-btn-secondary tich-corp-goal-row__remove" data-remove-goal-row @if($index === 0 && count($oldGoals) === 1) hidden @endif>Remove</button>
                                    </div>
                                    <div class="tich-corp-goal-row__fields">
                                        <div class="uf-field tich-corp-goal-row__title">
                                            <label>Title <span class="uf-req">*</span></label>
                                            <input type="text" name="goals[{{ $index }}][title]" required value="{{ $row['title'] ?? '' }}" placeholder="e.g. Improve on-time project delivery">
                                        </div>
                                        <div class="uf-field tich-corp-goal-row__weight">
                                            <label>Weight hint %</label>
                                            <input type="number" step="0.01" min="0" max="100" name="goals[{{ $index }}][weight_hint]" value="{{ $row['weight_hint'] ?? '' }}" placeholder="10">
                                        </div>
                                        <div class="uf-field tich-corp-goal-row__desc">
                                            <label>Description / KPI link</label>
                                            <textarea name="goals[{{ $index }}][description]" rows="2" placeholder="Optional detail or KPI reference">{{ $row['description'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="uf-form-section">
                    <div class="uf-section-head">Submit</div>
                    <div class="uf-section-body">
                        <div class="uf-form-actions">
                            <button type="submit" class="uf-btn uf-btn-primary">Save goal(s)</button>
                            <a href="{{ route('hr.appraisals.corporate-goals') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        </div>
                    </div>
                </div>

                <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
            </form>
        </div>

        <template id="corp-goal-row-template">
            <div class="tich-corp-goal-row" data-goal-row>
                <div class="tich-corp-goal-row__head">
                    <strong data-goal-row-label>Goal</strong>
                    <button type="button" class="uf-btn uf-btn-secondary tich-corp-goal-row__remove" data-remove-goal-row>Remove</button>
                </div>
                <div class="tich-corp-goal-row__fields">
                    <div class="uf-field tich-corp-goal-row__title">
                        <label>Title <span class="uf-req">*</span></label>
                        <input type="text" name="goals[__INDEX__][title]" required value="" placeholder="e.g. Improve on-time project delivery">
                    </div>
                    <div class="uf-field tich-corp-goal-row__weight">
                        <label>Weight hint %</label>
                        <input type="number" step="0.01" min="0" max="100" name="goals[__INDEX__][weight_hint]" value="" placeholder="10">
                    </div>
                    <div class="uf-field tich-corp-goal-row__desc">
                        <label>Description / KPI link</label>
                        <textarea name="goals[__INDEX__][description]" rows="2" placeholder="Optional detail or KPI reference"></textarea>
                    </div>
                </div>
            </div>
        </template>

        <script>
            (function () {
                var scope = document.getElementById('corp-goal-scope');
                var depts = document.getElementById('corp-goal-depts');
                var staff = document.getElementById('corp-goal-staff');
                var form = document.getElementById('corp-goal-form');
                var rowsWrap = document.getElementById('corp-goal-rows');
                var rowTemplate = document.getElementById('corp-goal-row-template');
                var addBtn = document.querySelector('[data-add-goal-row]');
                if (!scope || !form || !rowsWrap || !rowTemplate) return;

                var deptPicker = form.querySelector('[data-dept-picker]');
                var staffPicker = form.querySelector('[data-staff-picker]');
                var deptCountEl = form.querySelector('[data-dept-picker-count]');
                var staffCountEl = form.querySelector('[data-staff-picker-count]');

                function enforceRequired(selector) {
                    var boxes = form.querySelectorAll(selector + ':not(:disabled)');
                    var anyChecked = Array.prototype.some.call(boxes, function (cb) { return cb.checked; });
                    boxes.forEach(function (cb, index) {
                        cb.required = index === 0 && !anyChecked;
                    });
                }

                function updateDeptCount() {
                    var n = form.querySelectorAll('[data-dept-picker-option]:checked').length;
                    if (deptCountEl) deptCountEl.textContent = n + ' selected';
                    if (scope.value === 'department') enforceRequired('[data-dept-picker-option]');
                }

                function updateStaffCount() {
                    var n = form.querySelectorAll('[data-staff-picker-option]:checked').length;
                    if (staffCountEl) staffCountEl.textContent = n + ' selected';
                    if (scope.value === 'staff') enforceRequired('[data-staff-picker-option]');
                }

                function syncScope() {
                    var isDept = scope.value === 'department';
                    var isStaff = scope.value === 'staff';

                    if (depts) depts.hidden = !isDept;
                    if (staff) staff.hidden = !isStaff;

                    form.querySelectorAll('[data-dept-picker-option]').forEach(function (cb) {
                        cb.disabled = !isDept;
                        if (!isDept) cb.required = false;
                    });

                    form.querySelectorAll('[data-staff-picker-option]').forEach(function (cb) {
                        cb.disabled = !isStaff;
                        if (!isStaff) cb.required = false;
                    });

                    updateDeptCount();
                    updateStaffCount();
                }

                function bindPicker(picker, searchAttr, optionAttr, onChange) {
                    if (!picker) return;
                    var search = picker.querySelector('[' + searchAttr + ']');
                    var options = picker.querySelectorAll('.tich-staff-picker__option');

                    if (search) {
                        search.addEventListener('input', function () {
                            var q = (search.value || '').trim().toLowerCase();
                            options.forEach(function (opt) {
                                var hay = opt.getAttribute('data-search') || '';
                                opt.hidden = q !== '' && hay.indexOf(q) === -1;
                            });
                        });
                    }

                    picker.addEventListener('change', function (event) {
                        if (event.target && event.target.matches('[' + optionAttr + ']')) {
                            onChange();
                        }
                    });
                }

                function renumberRows() {
                    var rows = rowsWrap.querySelectorAll('[data-goal-row]');
                    rows.forEach(function (row, index) {
                        var label = row.querySelector('[data-goal-row-label]');
                        if (label) label.textContent = 'Goal ' + (index + 1);
                        row.querySelectorAll('[name]').forEach(function (input) {
                            input.name = input.name.replace(/goals\[\d+]/, 'goals[' + index + ']');
                        });
                        var removeBtn = row.querySelector('[data-remove-goal-row]');
                        if (removeBtn) removeBtn.hidden = rows.length === 1;
                    });
                }

                function addRow() {
                    var index = rowsWrap.querySelectorAll('[data-goal-row]').length;
                    var html = rowTemplate.innerHTML.replace(/__INDEX__/g, String(index));
                    var holder = document.createElement('div');
                    holder.innerHTML = html.trim();
                    rowsWrap.appendChild(holder.firstElementChild);
                    renumberRows();
                }

                if (addBtn) addBtn.addEventListener('click', addRow);

                rowsWrap.addEventListener('click', function (event) {
                    var btn = event.target.closest('[data-remove-goal-row]');
                    if (!btn) return;
                    var row = btn.closest('[data-goal-row]');
                    if (!row) return;
                    if (rowsWrap.querySelectorAll('[data-goal-row]').length <= 1) return;
                    row.remove();
                    renumberRows();
                });

                bindPicker(deptPicker, 'data-dept-picker-search', 'data-dept-picker-option', updateDeptCount);
                bindPicker(staffPicker, 'data-staff-picker-search', 'data-staff-picker-option', updateStaffCount);

                scope.addEventListener('change', syncScope);
                syncScope();
                renumberRows();

                form.addEventListener('submit', function (event) {
                    if (scope.value === 'department') {
                        var dn = form.querySelectorAll('[data-dept-picker-option]:checked').length;
                        if (dn < 1) {
                            event.preventDefault();
                            enforceRequired('[data-dept-picker-option]');
                            alert('Select at least one department for these goals.');
                            return;
                        }
                    }
                    if (scope.value === 'staff') {
                        var sn = form.querySelectorAll('[data-staff-picker-option]:checked').length;
                        if (sn < 1) {
                            event.preventDefault();
                            enforceRequired('[data-staff-picker-option]');
                            alert('Select at least one employee for these goals.');
                        }
                    }
                });
            })();
        </script>
    @endif
@endsection
