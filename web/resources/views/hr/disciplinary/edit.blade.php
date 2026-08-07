@extends('layouts.hr')

@section('title', 'Edit disciplinary case ' . $case->case_number)

@section('hr-content')
    <x-page-toolbar title="Edit disciplinary case" :meta="$case->case_number . ' · Employee Relations'">
        <x-slot:actions>
            <a href="{{ route('hr.employee-relations.disciplinary.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.employee-relations.disciplinary.update', $case) }}" class="tich-form-stack">
            @csrf
            @method('PUT')
            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label class="tich-label">Employee</label>
                    <input type="text" value="{{ $case->staff->fullName() }} ({{ $case->staff->employee_number }})" disabled class="tich-input">
                </div>
                <div>
                    <label for="assigned_to" class="tich-label">Assign to</label>
                    <select id="assigned_to" name="assigned_to" class="tich-input">
                        <option value="">Unassigned</option>
                        @foreach ($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ old('assigned_to', $case->assigned_to) == $staff->id ? 'selected' : '' }}>
                                {{ $staff->fullName() }} ({{ $staff->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="incident_date" class="tich-label">Incident date *</label>
                    <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date', $case->incident_date->format('Y-m-d')) }}" required class="tich-input">
                </div>
                <div>
                    <label for="hearing_date" class="tich-label">Hearing date</label>
                    <input type="date" id="hearing_date" name="hearing_date" value="{{ old('hearing_date', $case->hearing_date?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label for="incident_description" class="tich-label">Incident description *</label>
                    <textarea id="incident_description" name="incident_description" rows="4" required class="tich-input" placeholder="Describe the incident...">{{ old('incident_description', $case->incident_description) }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="investigation_notes" class="tich-label">Investigation notes</label>
                    <textarea id="investigation_notes" name="investigation_notes" rows="4" class="tich-input" placeholder="Investigation findings...">{{ old('investigation_notes', $case->investigation_notes) }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="witness_information" class="tich-label">Witness information</label>
                    <textarea id="witness_information" name="witness_information" rows="3" class="tich-input" placeholder="Witness names, contacts, statements...">{{ old('witness_information', $case->witness_information) }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="committee_members" class="tich-label">Committee members</label>
                    <textarea id="committee_members" name="committee_members" rows="2" class="tich-input" placeholder="Names of committee members...">{{ old('committee_members', $case->committee_members) }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="decision" class="tich-label">Decision</label>
                    <textarea id="decision" name="decision" rows="3" class="tich-input" placeholder="Decision details...">{{ old('decision', $case->decision) }}</textarea>
                </div>
                <div>
                    <label for="action_type" class="tich-label">Action type</label>
                    <select id="action_type" name="action_type" class="tich-input">
                        <option value="">None</option>
                        @foreach (['warning', 'suspension', 'termination', 'appeal', 'other'] as $type)
                            <option value="{{ $type }}" {{ old('action_type', $case->action_type) === $type ? 'selected' : '' }}>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="action_details" class="tich-label">Action details</label>
                    <textarea id="action_details" name="action_details" rows="3" class="tich-input" placeholder="Action details...">{{ old('action_details', $case->action_details) }}</textarea>
                </div>
                <div>
                    <label for="action_start_date" class="tich-label">Action start date</label>
                    <input type="date" id="action_start_date" name="action_start_date" value="{{ old('action_start_date', $case->action_start_date?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div>
                    <label for="action_end_date" class="tich-label">Action end date</label>
                    <input type="date" id="action_end_date" name="action_end_date" value="{{ old('action_end_date', $case->action_end_date?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div>
                    <label for="status" class="tich-label">Status *</label>
                    <select id="status" name="status" required class="tich-input">
                        @foreach (['open', 'under_investigation', 'hearing_scheduled', 'decided', 'appealed', 'closed'] as $status)
                            <option value="{{ $status }}" {{ old('status', $case->status) === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-grid--span-2">
                    <label for="hr_comments" class="tich-label">HR comments</label>
                    <textarea id="hr_comments" name="hr_comments" rows="3" class="tich-input" placeholder="HR comments...">{{ old('hr_comments', $case->hr_comments) }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Update case</button>
                <a href="{{ route('hr.employee-relations.disciplinary.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
