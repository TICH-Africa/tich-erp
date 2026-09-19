@extends('layouts.hr')

@section('title', 'Edit Staff')

@section('hr-content')
    <x-page-toolbar title="Edit Staff Member" />

    <form method="POST" action="{{ route('hr.staff.update', $staff) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('partials.staff-profile-photo-upload', [
            'staff' => $staff,
            'photoHelp' => 'Upload or replace this employee’s profile photo. Changes apply immediately (no approval needed).',
        ])

        <article class="tich-card">
            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="title" class="tich-label">Title</label>
                    <select id="title" name="title" class="tich-input">
                        <option value="">Select title</option>
                        <option value="Mr." {{ old('title', $staff->title) == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                        <option value="Ms." {{ old('title', $staff->title) == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                        <option value="Mrs." {{ old('title', $staff->title) == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                        <option value="Dr." {{ old('title', $staff->title) == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                        <option value="Prof." {{ old('title', $staff->title) == 'Prof.' ? 'selected' : '' }}>Prof.</option>
                    </select>
                </div>
                <div>
                    <label for="first_name" class="tich-label">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $staff->first_name) }}" required class="tich-input">
                </div>
                <div>
                    <label for="middle_name" class="tich-label">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name', $staff->middle_name) }}" class="tich-input">
                </div>
                <div>
                    <label for="surname" class="tich-label">Surname *</label>
                    <input type="text" id="surname" name="surname" value="{{ old('surname', $staff->surname) }}" required class="tich-input">
                </div>
                <div>
                    <label for="date_of_birth" class="tich-label">Date of Birth *</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $staff->date_of_birth) }}" required class="tich-input">
                </div>
                <div>
                    <label for="gender" class="tich-label">Gender *</label>
                    <select id="gender" name="gender" required class="tich-input">
                        <option value="">Select gender</option>
                        <option value="Male" {{ old('gender', $staff->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender', $staff->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div>
                    <label for="marital_status" class="tich-label">Marital Status</label>
                    <select id="marital_status" name="marital_status" class="tich-input">
                        <option value="">Select</option>
                        <option value="Single" {{ old('marital_status', $staff->marital_status) == 'Single' ? 'selected' : '' }}>Single</option>
                        <option value="Married" {{ old('marital_status', $staff->marital_status) == 'Married' ? 'selected' : '' }}>Married</option>
                        <option value="Divorced" {{ old('marital_status', $staff->marital_status) == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                        <option value="Widowed" {{ old('marital_status', $staff->marital_status) == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                    </select>
                </div>
                <div>
                    <label for="national_id_number" class="tich-label">National ID Number</label>
                    <input type="text" id="national_id_number" name="national_id_number" value="{{ old('national_id_number', $staff->national_id_number) }}" class="tich-input">
                </div>
                <div>
                    <label for="primary_email" class="tich-label">Primary email *</label>
                    <input type="email" id="primary_email" name="primary_email" value="{{ old('primary_email', $staff->primary_email) }}" required class="tich-input">
                </div>
                <div>
                    <label class="tich-label">Organisation email</label>
                    <p class="tich-input" style="background:var(--tich-surface-muted,#f8fafc);">{{ $staff->organisation_email ?: '-' }}</p>
                    <p class="tich-caption tich-mt-1">Assigned by ICT. Contact ICT to issue or change an @tich.africa address.</p>
                </div>
                <div>
                    <label for="phone_number" class="tich-label">Phone Number *</label>
                    <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $staff->phone_number) }}" required class="tich-input">
                </div>
                <div>
                    <label for="department_id" class="tich-label">Department</label>
                    <select id="department_id" name="department_id" class="tich-input">
                        <option value="">Unassigned - assign via Users &amp; access or HR later</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id', $staff->department_id) == $department->id ? 'selected' : '' }}>
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
                            <option value="{{ $campus->id }}" {{ old('campus_id', $staff->campus_id) == $campus->id ? 'selected' : '' }}>
                                {{ $campus->campus_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="job_title" class="tich-label">Job Title *</label>
                    <input type="text" id="job_title" name="job_title" value="{{ old('job_title', $staff->job_title) }}" required class="tich-input">
                </div>
                @include('hr.staff.partials.employment-category-select', ['selected' => old('employment_category', $staff->employment_category)])
                @include('hr.staff.partials.payroll-scheme-select', ['selected' => old('payroll_scheme', $staff->payroll_scheme ?: $staff->resolvedPayrollScheme())])
                <div>
                    <label for="employment_start_date" class="tich-label">Start Date *</label>
                    <input type="date" id="employment_start_date" name="employment_start_date" value="{{ old('employment_start_date', $staff->employment_start_date) }}" required class="tich-input">
                </div>
                <div>
                    <label for="gross_monthly_salary" class="tich-label">Consolidated Gross Pay *</label>
                    <input type="number" step="0.01" id="gross_monthly_salary" name="gross_monthly_salary" value="{{ old('gross_monthly_salary', $staff->gross_monthly_salary) }}" required class="tich-input">
                </div>
            </div>
        </article>

        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3">Statutory details</h2>
            <p class="tich-caption tich-mt-2">KRA, NSSF, SHA, HELB, pension, and bank details used for payroll. Changes apply immediately.</p>

            <div class="tich-grid tich-grid--2 tich-mt-4 tich-mb-6">
                <div>
                    <label for="kra_pin" class="tich-label">KRA PIN</label>
                    <input type="text" id="kra_pin" name="kra_pin" value="{{ old('kra_pin', $staff->kra_pin) }}" class="tich-input @error('kra_pin') tich-input--error @enderror" maxlength="50" autocomplete="off">
                    @error('kra_pin')
                        <p class="tich-form-error tich-mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="nssf_number" class="tich-label">NSSF number</label>
                    <input type="text" id="nssf_number" name="nssf_number" value="{{ old('nssf_number', $staff->nssf_number) }}" class="tich-input @error('nssf_number') tich-input--error @enderror" maxlength="50" autocomplete="off">
                    @error('nssf_number')
                        <p class="tich-form-error tich-mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="sha_number" class="tich-label">SHA number</label>
                    <input type="text" id="sha_number" name="sha_number" value="{{ old('sha_number', $staff->sha_number) }}" class="tich-input @error('sha_number') tich-input--error @enderror" maxlength="50" autocomplete="off">
                    @error('sha_number')
                        <p class="tich-form-error tich-mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="helb_number" class="tich-label">HELB number</label>
                    <input type="text" id="helb_number" name="helb_number" value="{{ old('helb_number', $staff->helb_number) }}" class="tich-input @error('helb_number') tich-input--error @enderror" maxlength="50" autocomplete="off">
                    @error('helb_number')
                        <p class="tich-form-error tich-mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="pension_scheme_id" class="tich-label">Pension scheme</label>
                    <select id="pension_scheme_id" name="pension_scheme_id" class="tich-input @error('pension_scheme_id') tich-input--error @enderror">
                        <option value="">None</option>
                        @foreach ($pensionSchemes as $scheme)
                            <option value="{{ $scheme->id }}" {{ (string) old('pension_scheme_id', $staff->pension_scheme_id) === (string) $scheme->id ? 'selected' : '' }}>
                                {{ $scheme->scheme_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('pension_scheme_id')
                        <p class="tich-form-error tich-mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <h3 class="tich-h3">Bank account</h3>
            <p class="tich-caption tich-mt-2">Primary account for salary payment.</p>
            @php
                $bank = $staff->bankAccount;
            @endphp
            <div class="tich-grid tich-grid--2 tich-mt-4">
                <div>
                    <label for="bank_name" class="tich-label">Bank name</label>
                    <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $bank?->bank_name) }}" class="tich-input @error('bank_name') tich-input--error @enderror" maxlength="200">
                    @error('bank_name')
                        <p class="tich-form-error tich-mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="bank_branch" class="tich-label">Branch</label>
                    <input type="text" id="bank_branch" name="bank_branch" value="{{ old('bank_branch', $bank?->bank_branch) }}" class="tich-input @error('bank_branch') tich-input--error @enderror" maxlength="200">
                    @error('bank_branch')
                        <p class="tich-form-error tich-mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="bank_code" class="tich-label">Bank code</label>
                    <input type="text" id="bank_code" name="bank_code" value="{{ old('bank_code', $bank?->bank_code) }}" class="tich-input @error('bank_code') tich-input--error @enderror" maxlength="20" autocomplete="off">
                    @error('bank_code')
                        <p class="tich-form-error tich-mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="account_name" class="tich-label">Account name</label>
                    <input type="text" id="account_name" name="account_name" value="{{ old('account_name', $bank?->account_name) }}" class="tich-input @error('account_name') tich-input--error @enderror" maxlength="300">
                    @error('account_name')
                        <p class="tich-form-error tich-mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="account_number" class="tich-label">Account number</label>
                    <input type="text" id="account_number" name="account_number" value="{{ old('account_number', $bank?->account_number) }}" class="tich-input @error('account_number') tich-input--error @enderror" maxlength="50" autocomplete="off">
                    @error('account_number')
                        <p class="tich-form-error tich-mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Update Staff Member</button>
                <a href="{{ route('hr.staff.show', $staff) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </article>
    </form>
@endsection

@section('scripts')
    @parent
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <x-asset.script path="js/tich-employee-profile-photo.js" />
@endsection
