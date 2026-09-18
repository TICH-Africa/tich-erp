@extends('layouts.employee')

@section('title', 'New grievance')

@section('employee-content')
    <x-page-toolbar title="New grievance" :meta="$staff->employee_number . ' · ' . ($staff->job_title ?? 'Staff')">
        <x-slot:actions>
            <a href="{{ route('employee.relations.grievances.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('employee.relations.grievances.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">GRV · {{ $staff->employee_number }}</div>
                    <div class="uf-amount-bar__sum">New grievance</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Grievance Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="grievance_type">Grievance type</label>
                            <input type="text" id="grievance_type" name="grievance_type" value="{{ old('grievance_type') }}" placeholder="e.g. workplace, compensation, management">
                        </div>
                        <div class="uf-field">
                            <label for="incident_date">Incident date</label>
                            <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date') }}">
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description <span class="uf-req">*</span></label>
                        <textarea id="description" name="description" rows="5" required class="{{ $errors->has('description') ? 'is-invalid' : '' }}" placeholder="Describe your grievance...">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uf-field">
                        <label for="resolution_notes">Suggested resolution</label>
                        <textarea id="resolution_notes" name="resolution_notes" rows="3" placeholder="How would you like this resolved...">{{ old('resolution_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Submit grievance</button>
                        <a href="{{ route('employee.relations.grievances.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
