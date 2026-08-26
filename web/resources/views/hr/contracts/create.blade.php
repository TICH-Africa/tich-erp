@extends('layouts.hr')

@section('title', 'New Contract')

@section('hr-content')
    <x-page-toolbar title="Create New Contract" />

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.contracts.store') }}">
            @csrf

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="staff_id" class="tich-label">Staff Member *</label>
                    <select id="staff_id" name="staff_id" required class="tich-input">
                        <option value="">Select staff</option>
                        @foreach ($staff as $s)
                            <option value="{{ $s->id }}" {{ old('staff_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->fullName() }} ({{ $s->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="contract_type" class="tich-label">Contract Type *</label>
                    <select id="contract_type" name="contract_type" required class="tich-input">
                        <option value="">Select type</option>
                        <option value="permanent" {{ old('contract_type') == 'permanent' ? 'selected' : '' }}>Permanent</option>
                        <option value="contract" {{ old('contract_type') == 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="intern" {{ old('contract_type') == 'intern' ? 'selected' : '' }}>Intern</option>
                        <option value="visiting" {{ old('contract_type') == 'visiting' ? 'selected' : '' }}>Visiting</option>
                        <option value="casual" {{ old('contract_type') == 'casual' ? 'selected' : '' }}>Casual</option>
                        <option value="probation" {{ old('contract_type') == 'probation' ? 'selected' : '' }}>Probation</option>
                        <option value="consultancy" {{ old('contract_type') == 'consultancy' ? 'selected' : '' }}>Consultancy</option>
                    </select>
                </div>
                <div>
                    <label for="job_title" class="tich-label">Job Title *</label>
                    <input type="text" id="job_title" name="job_title" value="{{ old('job_title') }}" required class="tich-input">
                </div>
                <div>
                    <label for="department_id" class="tich-label">Department *</label>
                    <select id="department_id" name="department_id" required class="tich-input">
                        <option value="">Select department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->dept_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="campus_id" class="tich-label">Campus</label>
                    <select id="campus_id" name="campus_id" class="tich-input">
                        <option value="">Select campus</option>
                        @foreach ($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
                                {{ $campus->campus_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="line_manager_id" class="tich-label">Line manager</label>
                    <select id="line_manager_id" name="line_manager_id" class="tich-input">
                        <option value="">Select line manager</option>
                        @foreach ($staff as $manager)
                            <option value="{{ $manager->id }}" {{ old('line_manager_id') == $manager->id ? 'selected' : '' }}>
                                {{ $manager->fullName() }} ({{ $manager->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="gross_salary" class="tich-label">Gross Salary *</label>
                    <input type="number" step="0.01" id="gross_salary" name="gross_salary" value="{{ old('gross_salary') }}" required class="tich-input">
                </div>
                <div>
                    <label for="start_date" class="tich-label">Start Date *</label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required class="tich-input">
                </div>
                <div>
                    <label for="duration" class="tich-label">Duration</label>
                    <input type="text" id="duration" name="duration" value="{{ old('duration') }}" class="tich-input" placeholder="e.g. 6 months, 1 year, 2y">
                    <p class="tich-caption tich-mt-1">Examples: 6 months, 1 year, 2y, 3m</p>
                </div>
                <div>
                    <label for="end_date" class="tich-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" class="tich-input" readonly>
                    <p class="tich-caption tich-mt-1">Auto-calculated from start date and duration.</p>
                </div>
                <div>
                    <label for="probation_end_date" class="tich-label">Probation End Date</label>
                    <input type="date" id="probation_end_date" name="probation_end_date" value="{{ old('probation_end_date') }}" class="tich-input">
                </div>
                <div class="tich-flex--center">
                    <label class="tich-checkbox">
                        <input type="checkbox" id="is_renewable" name="is_renewable" value="1" {{ old('is_renewable') ? 'checked' : '' }}>
                        <span>Renewable</span>
                    </label>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Create Contract</button>
                <a href="{{ route('hr.contracts.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
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
