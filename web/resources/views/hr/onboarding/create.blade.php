@extends('layouts.hr')

@section('title', 'New Onboarding')

@section('hr-content')
    <x-page-toolbar title="Create Onboarding Record" />

    <div class="uf-form">
        <form method="POST" action="{{ route('hr.onboarding.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">ONB · Onboarding</div>
                    <div class="uf-amount-bar__sum">New record</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Onboarding Details</div>
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
                            <label for="current_step">Current Step <span class="uf-req">*</span></label>
                            <select id="current_step" name="current_step" required class="{{ $errors->has('current_step') ? 'is-invalid' : '' }}">
                                <option value="">Select step</option>
                                <option value="biodata" @selected(old('current_step') == 'biodata')>Biodata</option>
                                <option value="employment_terms" @selected(old('current_step') == 'employment_terms')>Employment Terms</option>
                                <option value="banking" @selected(old('current_step') == 'banking')>Banking</option>
                                <option value="documents" @selected(old('current_step') == 'documents')>Documents</option>
                                <option value="contract" @selected(old('current_step') == 'contract')>Contract</option>
                                <option value="orientation" @selected(old('current_step') == 'orientation')>Orientation</option>
                                <option value="statutory" @selected(old('current_step') == 'statutory')>Statutory</option>
                                <option value="ess_account" @selected(old('current_step') == 'ess_account')>ESS Account</option>
                                <option value="completed" @selected(old('current_step') == 'completed')>Completed</option>
                            </select>
                            @error('current_step')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="status">Status <span class="uf-req">*</span></label>
                            <select id="status" name="status" required class="{{ $errors->has('status') ? 'is-invalid' : '' }}">
                                <option value="">Select status</option>
                                <option value="in_progress" @selected(old('status') == 'in_progress')>In Progress</option>
                                <option value="pending_hr_review" @selected(old('status') == 'pending_hr_review')>Pending HR Review</option>
                                <option value="approved" @selected(old('status') == 'approved')>Approved</option>
                                <option value="rejected" @selected(old('status') == 'rejected')>Rejected</option>
                                <option value="completed" @selected(old('status') == 'completed')>Completed</option>
                            </select>
                            @error('status')
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
                        <button type="submit" class="uf-btn uf-btn-primary">Create Onboarding Record</button>
                        <a href="{{ route('hr.onboarding.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
