@extends('layouts.hr')

@section('title', 'New Onboarding')

@section('hr-content')
    <div class="tich-mb-8">
        <a href="{{ route('hr.onboarding.index') }}" class="tich-btn tich-btn-ghost">&larr; Back to onboarding</a>
    </div>

    <article class="tich-card">
        <h1 class="tich-h1 tich-mb-6">Create Onboarding Record</h1>

        <form method="POST" action="{{ route('hr.onboarding.store') }}">
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
                    <label for="current_step" class="tich-label">Current Step *</label>
                    <select id="current_step" name="current_step" required class="tich-input">
                        <option value="">Select step</option>
                        <option value="biodata" {{ old('current_step') == 'biodata' ? 'selected' : '' }}>Biodata</option>
                        <option value="employment_terms" {{ old('current_step') == 'employment_terms' ? 'selected' : '' }}>Employment Terms</option>
                        <option value="banking" {{ old('current_step') == 'banking' ? 'selected' : '' }}>Banking</option>
                        <option value="documents" {{ old('current_step') == 'documents' ? 'selected' : '' }}>Documents</option>
                        <option value="contract" {{ old('current_step') == 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="orientation" {{ old('current_step') == 'orientation' ? 'selected' : '' }}>Orientation</option>
                        <option value="statutory" {{ old('current_step') == 'statutory' ? 'selected' : '' }}>Statutory</option>
                        <option value="ess_account" {{ old('current_step') == 'ess_account' ? 'selected' : '' }}>ESS Account</option>
                        <option value="completed" {{ old('current_step') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="tich-label">Status *</label>
                    <select id="status" name="status" required class="tich-input">
                        <option value="">Select status</option>
                        <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="pending_hr_review" {{ old('status') == 'pending_hr_review' ? 'selected' : '' }}>Pending HR Review</option>
                        <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Create Onboarding Record</button>
                <a href="{{ route('hr.onboarding.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
