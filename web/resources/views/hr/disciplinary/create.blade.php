@extends('layouts.hr')

@section('title', 'New disciplinary case')

@section('hr-content')
    <x-page-toolbar title="New disciplinary case" meta="Employee Relations">
        <x-slot:actions>
            <a href="{{ route('hr.employee-relations.disciplinary.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('hr.employee-relations.disciplinary.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">DSC · Employee Relations</div>
                    <div class="uf-amount-bar__sum">New disciplinary case</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Case Assignment</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="staff_id">Employee <span class="uf-req">*</span></label>
                            <select id="staff_id" name="staff_id" required class="{{ $errors->has('staff_id') ? 'is-invalid' : '' }}">
                                <option value="">Select employee</option>
                                @foreach ($staffList as $staff)
                                    <option value="{{ $staff->id }}" @selected(old('staff_id') == $staff->id)>
                                        {{ $staff->fullName() }} ({{ $staff->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('staff_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="assigned_to">Assign to</label>
                            <select id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach ($staffList as $staff)
                                    <option value="{{ $staff->id }}" @selected(old('assigned_to') == $staff->id)>
                                        {{ $staff->fullName() }} ({{ $staff->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="incident_date">Incident date <span class="uf-req">*</span></label>
                            <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date') }}" required class="{{ $errors->has('incident_date') ? 'is-invalid' : '' }}">
                            @error('incident_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="hearing_date">Hearing date</label>
                            <input type="date" id="hearing_date" name="hearing_date" value="{{ old('hearing_date') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Incident Details</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label for="incident_description">Incident description <span class="uf-req">*</span></label>
                        <textarea id="incident_description" name="incident_description" rows="4" required class="{{ $errors->has('incident_description') ? 'is-invalid' : '' }}" placeholder="Describe the incident...">{{ old('incident_description') }}</textarea>
                        @error('incident_description')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uf-field">
                        <label for="investigation_notes">Investigation notes</label>
                        <textarea id="investigation_notes" name="investigation_notes" rows="4" placeholder="Investigation findings...">{{ old('investigation_notes') }}</textarea>
                    </div>
                    <div class="uf-field">
                        <label for="witness_information">Witness information</label>
                        <textarea id="witness_information" name="witness_information" rows="3" placeholder="Witness names, contacts, statements...">{{ old('witness_information') }}</textarea>
                    </div>
                    <div class="uf-field">
                        <label for="committee_members">Committee members</label>
                        <textarea id="committee_members" name="committee_members" rows="2" placeholder="Names of committee members...">{{ old('committee_members') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create case</button>
                        <a href="{{ route('hr.employee-relations.disciplinary.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
