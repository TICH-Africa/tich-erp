@extends('layouts.hr')

@section('title', 'New Contract')

@section('hr-content')
    <x-page-toolbar title="Create New Contract" />

    <div class="uf-form">
        <form method="POST" action="{{ route('hr.contracts.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">CTR · Employment contract</div>
                    <div class="uf-amount-bar__sum">New contract</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Staff &amp; Role</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="staff_id">Staff Member <span class="uf-req">*</span></label>
                            <select id="staff_id" name="staff_id" required class="{{ $errors->has('staff_id') ? 'is-invalid' : '' }}">
                                <option value="">Select staff</option>
                                @foreach ($staff as $s)
                                    @php
                                        $optionContractType = match ($s->employment_category) {
                                            'consultant', 'independent_contractor' => 'consultancy',
                                            'permanent', 'contract', 'intern', 'visiting', 'casual', 'probation', 'consultancy' => $s->employment_category,
                                            default => '',
                                        };
                                    @endphp
                                    <option
                                        value="{{ $s->id }}"
                                        data-job-title="{{ $s->job_title }}"
                                        data-department-id="{{ $s->department_id }}"
                                        data-campus-id="{{ $s->campus_id }}"
                                        data-contract-type="{{ $optionContractType }}"
                                        data-gross-salary="{{ $s->gross_monthly_salary }}"
                                        data-start-date="{{ optional($s->employment_start_date)->format('Y-m-d') }}"
                                        data-line-manager-id="{{ $s->line_manager_id }}"
                                        @selected((string) old('staff_id', $selectedStaffId ?? request('staff_id')) === (string) $s->id)
                                    >
                                        {{ $s->fullName() }} ({{ $s->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('staff_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="contract_type">Contract Type <span class="uf-req">*</span></label>
                            <select id="contract_type" name="contract_type" required class="{{ $errors->has('contract_type') ? 'is-invalid' : '' }}">
                                <option value="">Select type</option>
                                <option value="permanent" @selected(old('contract_type', $defaultContractType ?? '') == 'permanent')>Permanent</option>
                                <option value="contract" @selected(old('contract_type', $defaultContractType ?? '') == 'contract')>Contract</option>
                                <option value="intern" @selected(old('contract_type', $defaultContractType ?? '') == 'intern')>Intern</option>
                                <option value="visiting" @selected(old('contract_type', $defaultContractType ?? '') == 'visiting')>Visiting</option>
                                <option value="casual" @selected(old('contract_type', $defaultContractType ?? '') == 'casual')>Casual</option>
                                <option value="probation" @selected(old('contract_type', $defaultContractType ?? '') == 'probation')>Probation</option>
                                <option value="consultancy" @selected(old('contract_type', $defaultContractType ?? '') == 'consultancy')>Consultancy</option>
                            </select>
                            @error('contract_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            <span class="uf-hint">Prefills from the staff employment category; you can edit it.</span>
                        </div>
                        <div class="uf-field">
                            <label for="job_title">Job Title <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="job_title"
                                name="job_title"
                                value="{{ old('job_title', $selectedStaff?->job_title ?? '') }}"
                                required
                                class="{{ $errors->has('job_title') ? 'is-invalid' : '' }}"
                            >
                            @error('job_title')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            <span class="uf-hint">Prefills from the staff record; you can edit it.</span>
                        </div>
                        <div class="uf-field">
                            <label for="department_id">Department <span class="uf-req">*</span></label>
                            <select id="department_id" name="department_id" required class="{{ $errors->has('department_id') ? 'is-invalid' : '' }}">
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id', $selectedStaff?->department_id) == $department->id)>
                                        {{ $department->dept_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="campus_id">Campus</label>
                            <select id="campus_id" name="campus_id">
                                <option value="">Select campus</option>
                                @foreach ($campuses as $campus)
                                    <option value="{{ $campus->id }}" @selected(old('campus_id', $selectedStaff?->campus_id) == $campus->id)>
                                        {{ $campus->campus_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="line_manager_id">Line manager</label>
                            <select id="line_manager_id" name="line_manager_id">
                                <option value="">Select line manager</option>
                                @foreach ($staff as $manager)
                                    <option value="{{ $manager->id }}" @selected(old('line_manager_id', $selectedStaff?->line_manager_id) == $manager->id)>
                                        {{ $manager->fullName() }} ({{ $manager->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Terms &amp; Dates</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="gross_salary">Gross Salary <span class="uf-req">*</span></label>
                            <input type="number" step="0.01" id="gross_salary" name="gross_salary" value="{{ old('gross_salary', $selectedStaff?->gross_monthly_salary) }}" required class="{{ $errors->has('gross_salary') ? 'is-invalid' : '' }}">
                            @error('gross_salary')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="start_date">Start Date <span class="uf-req">*</span></label>
                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date', optional($selectedStaff?->employment_start_date)->format('Y-m-d')) }}" required class="{{ $errors->has('start_date') ? 'is-invalid' : '' }}">
                            @error('start_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="duration">Duration in months</label>
                            <input type="number" id="duration" name="duration" value="{{ old('duration') }}" min="1" step="1" placeholder="e.g. 12">
                            <span class="uf-hint">Enter the number of months only.</span>
                        </div>
                        <div class="uf-field">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" readonly>
                            <span class="uf-hint">Auto-calculated from start date and duration (ends the day before the anniversary).</span>
                        </div>
                        <div class="uf-field">
                            <label for="probation_end_date">Probation End Date</label>
                            <input type="date" id="probation_end_date" name="probation_end_date" value="{{ old('probation_end_date') }}">
                        </div>
                        <div class="uf-field">
                            <label class="tich-checkbox">
                                <input type="checkbox" id="is_renewable" name="is_renewable" value="1" @checked(old('is_renewable'))>
                                <span>Renewable</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create Contract</button>
                        <a href="{{ route('hr.contracts.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
    <script>
        (function () {
            var startInput = document.getElementById('start_date');
            var durationInput = document.getElementById('duration');
            var endInput = document.getElementById('end_date');
            if (startInput && durationInput && endInput) {
                function calculateEnd() {
                    var start = startInput.value;
                    var duration = durationInput.value.trim();
                    if (!start || !duration) {
                        endInput.value = '';
                        return;
                    }

                    var months = parseInt(duration, 10);
                    if (!Number.isFinite(months) || months <= 0) {
                        endInput.value = '';
                        return;
                    }

                    var parts = start.split('-');
                    var date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                    date.setMonth(date.getMonth() + months);
                    date.setDate(date.getDate() - 1);
                    var yyyy = date.getFullYear();
                    var mm = String(date.getMonth() + 1).padStart(2, '0');
                    var dd = String(date.getDate()).padStart(2, '0');
                    endInput.value = yyyy + '-' + mm + '-' + dd;
                }

                startInput.addEventListener('change', calculateEnd);
                durationInput.addEventListener('input', calculateEnd);
            }
        })();

        (function () {
            var staffSelect = document.getElementById('staff_id');
            var jobTitleInput = document.getElementById('job_title');
            var departmentSelect = document.getElementById('department_id');
            var campusSelect = document.getElementById('campus_id');
            var contractTypeSelect = document.getElementById('contract_type');
            var grossSalaryInput = document.getElementById('gross_salary');
            var startDateInput = document.getElementById('start_date');
            var lineManagerSelect = document.getElementById('line_manager_id');
            if (!staffSelect || !jobTitleInput) return;

            var lastAutoJobTitle = jobTitleInput.value || '';
            var lastAutoDepartmentId = departmentSelect ? departmentSelect.value : '';
            var lastAutoCampusId = campusSelect ? campusSelect.value : '';
            var lastAutoContractType = contractTypeSelect ? contractTypeSelect.value : '';
            var lastAutoGrossSalary = grossSalaryInput ? String(grossSalaryInput.value || '') : '';
            var lastAutoStartDate = startDateInput ? (startDateInput.value || '') : '';
            var lastAutoLineManagerId = lineManagerSelect ? lineManagerSelect.value : '';

            function setSelectValue(select, value) {
                if (!select || value === null || value === undefined || value === '') return false;
                select.value = String(value);
                select.dispatchEvent(new Event('change', { bubbles: true }));
                return select.value === String(value);
            }

            function applyStaffDefaults(force) {
                var option = staffSelect.options[staffSelect.selectedIndex];
                if (!option || !option.value) return;

                var jobTitle = option.getAttribute('data-job-title') || '';
                var departmentId = option.getAttribute('data-department-id') || '';
                var campusId = option.getAttribute('data-campus-id') || '';
                var contractType = option.getAttribute('data-contract-type') || '';
                var grossSalary = option.getAttribute('data-gross-salary') || '';
                var startDate = option.getAttribute('data-start-date') || '';
                var lineManagerId = option.getAttribute('data-line-manager-id') || '';

                if (jobTitle && (force || !jobTitleInput.value || jobTitleInput.value === lastAutoJobTitle)) {
                    jobTitleInput.value = jobTitle;
                    lastAutoJobTitle = jobTitle;
                }

                if (departmentSelect && departmentId && (force || !departmentSelect.value || departmentSelect.value === lastAutoDepartmentId)) {
                    setSelectValue(departmentSelect, departmentId);
                    lastAutoDepartmentId = departmentId;
                }

                if (campusSelect && campusId && (force || !campusSelect.value || campusSelect.value === lastAutoCampusId)) {
                    setSelectValue(campusSelect, campusId);
                    lastAutoCampusId = campusId;
                }

                if (contractTypeSelect && contractType && (force || !contractTypeSelect.value || contractTypeSelect.value === lastAutoContractType)) {
                    setSelectValue(contractTypeSelect, contractType);
                    lastAutoContractType = contractType;
                }

                if (grossSalaryInput && grossSalary && (force || !grossSalaryInput.value || String(grossSalaryInput.value) === lastAutoGrossSalary)) {
                    grossSalaryInput.value = grossSalary;
                    lastAutoGrossSalary = String(grossSalary);
                }

                if (startDateInput && startDate && (force || !startDateInput.value || startDateInput.value === lastAutoStartDate)) {
                    startDateInput.value = startDate;
                    lastAutoStartDate = startDate;
                    startDateInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (lineManagerSelect && lineManagerId && (force || !lineManagerSelect.value || lineManagerSelect.value === lastAutoLineManagerId)) {
                    setSelectValue(lineManagerSelect, lineManagerId);
                    lastAutoLineManagerId = lineManagerId;
                }
            }

            staffSelect.addEventListener('change', function () {
                applyStaffDefaults(true);
            });

            var skipInitialAutofill = @json(session()->hasOldInput());
            if (staffSelect.value && !skipInitialAutofill) {
                applyStaffDefaults(false);
            }
        })();
    </script>
@endsection
