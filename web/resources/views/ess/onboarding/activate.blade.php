@extends('layouts.auth')

@section('title', 'Complete your onboarding')
@section('headline', 'Welcome to TICH.')
@section('subheadline', 'Complete your profile and set up your account to access the employee portal.')

@section('content')
    <div class="tich-mb-8">
        <h2 class="tich-h2">Complete your onboarding</h2>
        <p class="tich-text tich-mt-2">
            You have been offered a position at <strong>TICH</strong>.
            Complete the form below to activate your employee account and set up your profile.
        </p>
    </div>

    <div class="tich-card tich-mb-6" style="padding: 1rem 1.25rem;">
        <p class="tich-caption">Employee Number</p>
        <p class="tich-text" style="font-weight: 600;">{{ $staff->employee_number }}</p>
        <p class="tich-caption tich-mt-3">Job Title</p>
        <p class="tich-text">{{ $staff->job_title }}</p>
        <p class="tich-caption tich-mt-3">Department</p>
        <p class="tich-text">{{ $staff->department->dept_name ?? '-' }}</p>
        <p class="tich-caption tich-mt-3">Email</p>
        <p class="tich-text">{{ $staff->primary_email ?? $staff->organisation_email }}</p>
    </div>

    <form method="POST" action="{{ route('ess.onboarding.activate.store', $staff->onboarding_token) }}" data-client-context>
        @csrf
        @include('partials.client-context-fields')

        <h3 class="tich-h3 tich-mb-4">Account Setup</h3>
        <p class="tich-text tich-mb-4">You will sign in using <strong>{{ $staff->primary_email ?? $staff->organisation_email }}</strong>.</p>
        <div class="tich-grid tich-grid--2 tich-mb-6">
            <div>
                <label for="password" class="tich-label">Password *</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="tich-input @error('password') tich-input--error @enderror"
                >
                @error('password')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="tich-label">Confirm Password *</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    class="tich-input"
                >
            </div>
        </div>

        <h3 class="tich-h3 tich-mb-4">Personal Information</h3>
        <div class="tich-grid tich-grid--2 tich-mb-6">
            <div>
                <label for="first_name" class="tich-label">First Name *</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $staff->first_name) }}" required class="tich-input @error('first_name') tich-input--error @enderror">
                @error('first_name')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="surname" class="tich-label">Surname *</label>
                <input type="text" id="surname" name="surname" value="{{ old('surname', $staff->surname) }}" required class="tich-input @error('surname') tich-input--error @enderror">
                @error('surname')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="middle_name" class="tich-label">Middle Name</label>
                <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name', $staff->middle_name) }}" class="tich-input @error('middle_name') tich-input--error @enderror">
                @error('middle_name')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="date_of_birth" class="tich-label">Date of Birth *</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $staff->date_of_birth?->format('Y-m-d')) }}" required class="tich-input @error('date_of_birth') tich-input--error @enderror">
                @error('date_of_birth')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="gender" class="tich-label">Gender *</label>
                <select id="gender" name="gender" required class="tich-input @error('gender') tich-input--error @enderror">
                    <option value="">Select gender</option>
                    <option value="Male" {{ old('gender', $staff->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ old('gender', $staff->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                    <option value="Other" {{ old('gender', $staff->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('gender')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="marital_status" class="tich-label">Marital Status</label>
                <select id="marital_status" name="marital_status" class="tich-input @error('marital_status') tich-input--error @enderror">
                    <option value="">Select status</option>
                    <option value="Single" {{ old('marital_status', $staff->marital_status) == 'Single' ? 'selected' : '' }}>Single</option>
                    <option value="Married" {{ old('marital_status', $staff->marital_status) == 'Married' ? 'selected' : '' }}>Married</option>
                    <option value="Divorced" {{ old('marital_status', $staff->marital_status) == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                    <option value="Widowed" {{ old('marital_status', $staff->marital_status) == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                    <option value="Separated" {{ old('marital_status', $staff->marital_status) == 'Separated' ? 'selected' : '' }}>Separated</option>
                </select>
                @error('marital_status')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="national_id_number" class="tich-label">National ID / Passport</label>
                <input type="text" id="national_id_number" name="national_id_number" value="{{ old('national_id_number', $staff->national_id_number) }}" class="tich-input @error('national_id_number') tich-input--error @enderror">
                @error('national_id_number')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="phone_number" class="tich-label">Phone Number *</label>
                <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $staff->phone_number) }}" required class="tich-input @error('phone_number') tich-input--error @enderror">
                @error('phone_number')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="postal_address" class="tich-label">Postal Address</label>
                <input type="text" id="postal_address" name="postal_address" value="{{ old('postal_address', $staff->postal_address) }}" class="tich-input @error('postal_address') tich-input--error @enderror">
                @error('postal_address')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="physical_address" class="tich-label">Physical Address</label>
                <input type="text" id="physical_address" name="physical_address" value="{{ old('physical_address', $staff->physical_address) }}" class="tich-input @error('physical_address') tich-input--error @enderror">
                @error('physical_address')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <h3 class="tich-h3 tich-mb-4">Emergency Contact</h3>
        <div class="tich-grid tich-grid--2 tich-mb-6">
            <div>
                <label for="emergency_contact_name" class="tich-label">Emergency Contact Name *</label>
                <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $staff->emergency_contact_name) }}" required class="tich-input @error('emergency_contact_name') tich-input--error @enderror">
                @error('emergency_contact_name')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="emergency_contact_phone" class="tich-label">Emergency Contact Phone *</label>
                <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone) }}" required class="tich-input @error('emergency_contact_phone') tich-input--error @enderror">
                @error('emergency_contact_phone')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="emergency_contact_relationship" class="tich-label">Relationship *</label>
                <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $staff->emergency_contact_relationship) }}" required class="tich-input @error('emergency_contact_relationship') tich-input--error @enderror">
                @error('emergency_contact_relationship')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <h3 class="tich-h3 tich-mb-4">Statutory Information</h3>
        <div class="tich-grid tich-grid--2 tich-mb-6">
            <div>
                <label for="kra_pin" class="tich-label">KRA PIN</label>
                <input type="text" id="kra_pin" name="kra_pin" value="{{ old('kra_pin', $staff->kra_pin) }}" class="tich-input @error('kra_pin') tich-input--error @enderror">
                @error('kra_pin')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="nssf_number" class="tich-label">NSSF Number</label>
                <input type="text" id="nssf_number" name="nssf_number" value="{{ old('nssf_number', $staff->nssf_number) }}" class="tich-input @error('nssf_number') tich-input--error @enderror">
                @error('nssf_number')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="sha_number" class="tich-label">SHA Number</label>
                <input type="text" id="sha_number" name="sha_number" value="{{ old('sha_number', $staff->sha_number) }}" class="tich-input @error('sha_number') tich-input--error @enderror">
                @error('sha_number')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="helb_number" class="tich-label">HELB Number</label>
                <input type="text" id="helb_number" name="helb_number" value="{{ old('helb_number', $staff->helb_number) }}" class="tich-input @error('helb_number') tich-input--error @enderror">
                @error('helb_number')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Complete Onboarding</button>
            <button type="button" onclick="document.getElementById('draft-form').submit()" class="tich-btn tich-btn-ghost tich-ml-4">Save Draft</button>
        </div>
    </form>

    <form id="draft-form" method="POST" action="{{ route('ess.onboarding.draft', $staff->onboarding_token) }}" style="display: none;">
        @csrf
        <input type="hidden" name="first_name" value="{{ old('first_name', $staff->first_name) }}">
        <input type="hidden" name="surname" value="{{ old('surname', $staff->surname) }}">
        <input type="hidden" name="middle_name" value="{{ old('middle_name', $staff->middle_name) }}">
        <input type="hidden" name="date_of_birth" value="{{ old('date_of_birth', $staff->date_of_birth?->format('Y-m-d')) }}">
        <input type="hidden" name="gender" value="{{ old('gender', $staff->gender) }}">
        <input type="hidden" name="marital_status" value="{{ old('marital_status', $staff->marital_status) }}">
        <input type="hidden" name="national_id_number" value="{{ old('national_id_number', $staff->national_id_number) }}">
        <input type="hidden" name="phone_number" value="{{ old('phone_number', $staff->phone_number) }}">
        <input type="hidden" name="postal_address" value="{{ old('postal_address', $staff->postal_address) }}">
        <input type="hidden" name="physical_address" value="{{ old('physical_address', $staff->physical_address) }}">
        <input type="hidden" name="emergency_contact_name" value="{{ old('emergency_contact_name', $staff->emergency_contact_name) }}">
        <input type="hidden" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone) }}">
        <input type="hidden" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $staff->emergency_contact_relationship) }}">
        <input type="hidden" name="kra_pin" value="{{ old('kra_pin', $staff->kra_pin) }}">
        <input type="hidden" name="nssf_number" value="{{ old('nssf_number', $staff->nssf_number) }}">
        <input type="hidden" name="sha_number" value="{{ old('sha_number', $staff->sha_number) }}">
        <input type="hidden" name="helb_number" value="{{ old('helb_number', $staff->helb_number) }}">
    </form>
@endsection
