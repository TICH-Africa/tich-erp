@extends('layouts.hr')

@section('title', 'Edit Staff')

@section('hr-content')
    <x-page-toolbar title="Edit Staff Member" />

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.staff.update', $staff) }}">
            @csrf
            @method('PUT')

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
                    <label for="organisation_email" class="tich-label">Organisation email</label>
                    <input type="email" id="organisation_email" name="organisation_email" value="{{ old('organisation_email', $staff->organisation_email) }}" class="tich-input" pattern=".+@tich\.africa$" title="If set, must use @tich.africa" placeholder="Optional — e.g. name@tich.africa">
                </div>
                <div>
                    <label for="phone_number" class="tich-label">Phone Number *</label>
                    <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $staff->phone_number) }}" required class="tich-input">
                </div>
                <div>
                    <label for="department_id" class="tich-label">Department</label>
                    <select id="department_id" name="department_id" class="tich-input">
                        <option value="">Unassigned — assign via Users &amp; access or HR later</option>
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

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Update Staff Member</button>
                <a href="{{ route('hr.staff.show', $staff) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
