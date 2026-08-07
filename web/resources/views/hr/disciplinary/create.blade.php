@extends('layouts.hr')

@section('title', 'New disciplinary case')

@section('hr-content')
    <x-page-toolbar title="New disciplinary case" meta="Employee Relations">
        <x-slot:actions>
            <a href="{{ route('hr.employee-relations.disciplinary.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.employee-relations.disciplinary.store') }}" class="tich-form-stack">
            @csrf
            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="staff_id" class="tich-label">Employee *</label>
                    <select id="staff_id" name="staff_id" required class="tich-input">
                        <option value="">Select employee</option>
                        @foreach ($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ old('staff_id') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->fullName() }} ({{ $staff->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="assigned_to" class="tich-label">Assign to</label>
                    <select id="assigned_to" name="assigned_to" class="tich-input">
                        <option value="">Unassigned</option>
                        @foreach ($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ old('assigned_to') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->fullName() }} ({{ $staff->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="incident_date" class="tich-label">Incident date *</label>
                    <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date') }}" required class="tich-input">
                </div>
                <div>
                    <label for="hearing_date" class="tich-label">Hearing date</label>
                    <input type="date" id="hearing_date" name="hearing_date" value="{{ old('hearing_date') }}" class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label for="incident_description" class="tich-label">Incident description *</label>
                    <textarea id="incident_description" name="incident_description" rows="4" required class="tich-input" placeholder="Describe the incident...">{{ old('incident_description') }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="investigation_notes" class="tich-label">Investigation notes</label>
                    <textarea id="investigation_notes" name="investigation_notes" rows="4" class="tich-input" placeholder="Investigation findings...">{{ old('investigation_notes') }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="witness_information" class="tich-label">Witness information</label>
                    <textarea id="witness_information" name="witness_information" rows="3" class="tich-input" placeholder="Witness names, contacts, statements...">{{ old('witness_information') }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="committee_members" class="tich-label">Committee members</label>
                    <textarea id="committee_members" name="committee_members" rows="2" class="tich-input" placeholder="Names of committee members...">{{ old('committee_members') }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Create case</button>
                <a href="{{ route('hr.employee-relations.disciplinary.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
