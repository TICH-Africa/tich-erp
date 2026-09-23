@extends('layouts.hr')

@section('title', 'Add Staff')

@section('hr-content')
    <x-page-toolbar title="Add New Staff Member" />

    <div class="uf-form">
        <form method="POST" action="{{ route('hr.staff.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">STF · New employee</div>
                    <div class="uf-amount-bar__sum">Add staff member</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Personal Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="title">Title</label>
                            <select id="title" name="title">
                                <option value="">Select title</option>
                                <option value="Mr." @selected(old('title') == 'Mr.')>Mr.</option>
                                <option value="Ms." @selected(old('title') == 'Ms.')>Ms.</option>
                                <option value="Mrs." @selected(old('title') == 'Mrs.')>Mrs.</option>
                                <option value="Dr." @selected(old('title') == 'Dr.')>Dr.</option>
                                <option value="Prof." @selected(old('title') == 'Prof.')>Prof.</option>
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="first_name">First Name <span class="uf-req">*</span></label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required class="{{ $errors->has('first_name') ? 'is-invalid' : '' }}">
                            @error('first_name')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
                        </div>
                        <div class="uf-field">
                            <label for="surname">Surname <span class="uf-req">*</span></label>
                            <input type="text" id="surname" name="surname" value="{{ old('surname') }}" required class="{{ $errors->has('surname') ? 'is-invalid' : '' }}">
                            @error('surname')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="date_of_birth">Date of Birth <span class="uf-req">*</span></label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required class="{{ $errors->has('date_of_birth') ? 'is-invalid' : '' }}">
                            @error('date_of_birth')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="gender">Gender <span class="uf-req">*</span></label>
                            <select id="gender" name="gender" required class="{{ $errors->has('gender') ? 'is-invalid' : '' }}">
                                <option value="">Select gender</option>
                                <option value="Male" @selected(old('gender') == 'Male')>Male</option>
                                <option value="Female" @selected(old('gender') == 'Female')>Female</option>
                            </select>
                            @error('gender')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="marital_status">Marital Status</label>
                            <select id="marital_status" name="marital_status">
                                <option value="">Select</option>
                                <option value="Single" @selected(old('marital_status') == 'Single')>Single</option>
                                <option value="Married" @selected(old('marital_status') == 'Married')>Married</option>
                                <option value="Divorced" @selected(old('marital_status') == 'Divorced')>Divorced</option>
                                <option value="Widowed" @selected(old('marital_status') == 'Widowed')>Widowed</option>
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="national_id_number">National ID Number</label>
                            <input type="text" id="national_id_number" name="national_id_number" value="{{ old('national_id_number') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Contact &amp; Assignment</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="primary_email">Primary email <span class="uf-req">*</span></label>
                            <input
                                type="email"
                                id="primary_email"
                                name="primary_email"
                                value="{{ old('primary_email') }}"
                                required
                                class="{{ $errors->has('primary_email') ? 'is-invalid' : '' }}"
                                placeholder="Personal email address"
                                autocomplete="email"
                                data-email-check-url="{{ route('hr.staff.check-email') }}"
                            >
                            @error('primary_email')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            <span class="uf-hint" id="primary_email_hint">Organisation email (@tich.africa) is assigned separately by ICT when issued.</span>
                            <span class="uf-error" id="primary_email_exists" hidden role="alert"></span>
                        </div>
                        <div class="uf-field">
                            <label for="phone_number">Phone Number <span class="uf-req">*</span></label>
                            <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" required class="{{ $errors->has('phone_number') ? 'is-invalid' : '' }}">
                            @error('phone_number')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="department_id">Department</label>
                            <select id="department_id" name="department_id">
                                <option value="">Unassigned - assign via Users &amp; access or HR later</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                        {{ $department->dept_name }}
                                    </option>
                                @endforeach
                            </select>
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
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Employment &amp; Payroll</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="job_title">Job Title <span class="uf-req">*</span></label>
                            <input type="text" id="job_title" name="job_title" value="{{ old('job_title') }}" required class="{{ $errors->has('job_title') ? 'is-invalid' : '' }}">
                            @error('job_title')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            @include('hr.staff.partials.employment-category-select', ['selected' => old('employment_category')])
                        </div>
                        <div class="uf-field">
                            @include('hr.staff.partials.payroll-scheme-select', ['selected' => old('payroll_scheme', 'employee')])
                        </div>
                        <div class="uf-field">
                            <label for="employment_start_date">Start Date <span class="uf-req">*</span></label>
                            <input type="date" id="employment_start_date" name="employment_start_date" value="{{ old('employment_start_date') }}" required class="{{ $errors->has('employment_start_date') ? 'is-invalid' : '' }}">
                            @error('employment_start_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="gross_monthly_salary">Consolidated Gross Pay <span class="uf-req">*</span></label>
                            <input type="number" step="0.01" id="gross_monthly_salary" name="gross_monthly_salary" value="{{ old('gross_monthly_salary') }}" required class="{{ $errors->has('gross_monthly_salary') ? 'is-invalid' : '' }}">
                            @error('gross_monthly_salary')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary" id="staff-create-submit">Create Staff Member</button>
                        <a href="{{ route('hr.staff.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <script>
        (function () {
            const input = document.getElementById('primary_email');
            const hint = document.getElementById('primary_email_hint');
            const alertEl = document.getElementById('primary_email_exists');
            const submitBtn = document.getElementById('staff-create-submit');
            const form = input ? input.closest('form') : null;
            if (!input || !form || !alertEl || !submitBtn) return;

            const checkUrl = input.dataset.emailCheckUrl;
            let timer = null;
            let blocked = false;
            let lastChecked = '';

            function setBlocked(message) {
                blocked = true;
                input.classList.add('is-invalid');
                alertEl.hidden = false;
                alertEl.textContent = message;
                if (hint) hint.hidden = true;
                submitBtn.disabled = true;
                submitBtn.setAttribute('aria-disabled', 'true');
            }

            function clearBlocked() {
                blocked = false;
                input.classList.remove('is-invalid');
                alertEl.hidden = true;
                alertEl.textContent = '';
                if (hint) hint.hidden = false;
                submitBtn.disabled = false;
                submitBtn.removeAttribute('aria-disabled');
            }

            function checkEmail() {
                const email = (input.value || '').trim().toLowerCase();
                if (!email || email.indexOf('@') === -1) {
                    clearBlocked();
                    lastChecked = '';
                    return;
                }
                if (email === lastChecked) return;
                lastChecked = email;

                fetch(checkUrl + '?email=' + encodeURIComponent(email), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if ((input.value || '').trim().toLowerCase() !== email) return;
                        if (data.exists) {
                            setBlocked(data.message || 'This email is already in use.');
                        } else {
                            clearBlocked();
                        }
                    })
                    .catch(function () { /* ignore network blips; server validates on submit */ });
            }

            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(checkEmail, 350);
            });
            input.addEventListener('blur', checkEmail);

            form.addEventListener('submit', function (event) {
                if (blocked) {
                    event.preventDefault();
                    alertEl.focus?.();
                    return false;
                }
            });

            if (input.value) {
                checkEmail();
            }
        })();
    </script>
@endsection
