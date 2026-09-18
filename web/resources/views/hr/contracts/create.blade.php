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
                                    <option value="{{ $s->id }}" @selected(old('staff_id') == $s->id)>
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
                                <option value="permanent" @selected(old('contract_type') == 'permanent')>Permanent</option>
                                <option value="contract" @selected(old('contract_type') == 'contract')>Contract</option>
                                <option value="intern" @selected(old('contract_type') == 'intern')>Intern</option>
                                <option value="visiting" @selected(old('contract_type') == 'visiting')>Visiting</option>
                                <option value="casual" @selected(old('contract_type') == 'casual')>Casual</option>
                                <option value="probation" @selected(old('contract_type') == 'probation')>Probation</option>
                                <option value="consultancy" @selected(old('contract_type') == 'consultancy')>Consultancy</option>
                            </select>
                            @error('contract_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="job_title">Job Title <span class="uf-req">*</span></label>
                            <input type="text" id="job_title" name="job_title" value="{{ old('job_title') }}" required class="{{ $errors->has('job_title') ? 'is-invalid' : '' }}">
                            @error('job_title')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="department_id">Department <span class="uf-req">*</span></label>
                            <select id="department_id" name="department_id" required class="{{ $errors->has('department_id') ? 'is-invalid' : '' }}">
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
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
                                    <option value="{{ $campus->id }}" @selected(old('campus_id') == $campus->id)>
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
                                    <option value="{{ $manager->id }}" @selected(old('line_manager_id') == $manager->id)>
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
                            <input type="number" step="0.01" id="gross_salary" name="gross_salary" value="{{ old('gross_salary') }}" required class="{{ $errors->has('gross_salary') ? 'is-invalid' : '' }}">
                            @error('gross_salary')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="start_date">Start Date <span class="uf-req">*</span></label>
                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required class="{{ $errors->has('start_date') ? 'is-invalid' : '' }}">
                            @error('start_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="duration">Duration</label>
                            <input type="text" id="duration" name="duration" value="{{ old('duration') }}" placeholder="e.g. 6 months, 1 year, 2y">
                            <span class="uf-hint">Examples: 6 months, 1 year, 2y, 3m</span>
                        </div>
                        <div class="uf-field">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" readonly>
                            <span class="uf-hint">Auto-calculated from start date and duration.</span>
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
            if (!startInput || !durationInput || !endInput) return;

            function calculateEnd() {
                var start = startInput.value;
                var duration = durationInput.value.trim();
                if (!start || !duration) {
                    endInput.value = '';
                    return;
                }

                var lower = duration.toLowerCase();
                var months = 0;
                var yearMatch = lower.match(/(\d+)\s*y/);
                var monthMatch = lower.match(/(\d+)\s*m/);

                if (yearMatch) months += parseInt(yearMatch[1], 10) * 12;
                if (monthMatch) months += parseInt(monthMatch[1], 10);
                if (!yearMatch && !monthMatch && /^\d+$/.test(lower)) months = parseInt(lower, 10);

                if (months <= 0) {
                    endInput.value = '';
                    return;
                }

                var date = new Date(start);
                date.setMonth(date.getMonth() + months);
                var yyyy = date.getFullYear();
                var mm = String(date.getMonth() + 1).padStart(2, '0');
                var dd = String(date.getDate()).padStart(2, '0');
                endInput.value = yyyy + '-' + mm + '-' + dd;
            }

            startInput.addEventListener('change', calculateEnd);
            durationInput.addEventListener('input', calculateEnd);
        })();
    </script>
@endsection
