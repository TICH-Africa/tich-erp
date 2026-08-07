@extends('layouts.hr')

@section('title', 'Edit grievance #' . $grievance->id)

@section('hr-content')
    <x-page-toolbar title="Edit grievance" :meta="'#'.$grievance->id . ' · Employee Relations'">
        <x-slot:actions>
            <a href="{{ route('hr.employee-relations.grievances.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.employee-relations.grievances.update', $grievance) }}" class="tich-form-stack">
            @csrf
            @method('PUT')
            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label class="tich-label">Employee</label>
                    <input type="text" value="{{ $grievance->staff->fullName() }} ({{ $grievance->staff->employee_number }})" disabled class="tich-input">
                </div>
                <div>
                    <label for="assigned_to" class="tich-label">Assign to</label>
                    <select id="assigned_to" name="assigned_to" class="tich-input">
                        <option value="">Unassigned</option>
                        @foreach ($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ old('assigned_to', $grievance->assigned_to) == $staff->id ? 'selected' : '' }}>
                                {{ $staff->fullName() }} ({{ $staff->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="grievance_type" class="tich-label">Grievance type</label>
                    <input type="text" id="grievance_type" name="grievance_type" value="{{ old('grievance_type', $grievance->grievance_type) }}" class="tich-input" placeholder="e.g. workplace, compensation, management">
                </div>
                <div>
                    <label for="incident_date" class="tich-label">Incident date</label>
                    <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date', $grievance->incident_date?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label for="description" class="tich-label">Description</label>
                    <textarea id="description" name="description" rows="5" class="tich-input" placeholder="Describe the grievance...">{{ old('description', $grievance->description) }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="resolution_notes" class="tich-label">Resolution notes</label>
                    <textarea id="resolution_notes" name="resolution_notes" rows="3" class="tich-input" placeholder="How was this resolved...">{{ old('resolution_notes', $grievance->resolution_notes) }}</textarea>
                </div>
                <div>
                    <label for="status" class="tich-label">Status *</label>
                    <select id="status" name="status" required class="tich-input">
                        @foreach (['open', 'under_review', 'resolved', 'closed'] as $status)
                            <option value="{{ $status }}" {{ old('status', $grievance->status) === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="resolved_at" class="tich-label">Resolved date</label>
                    <input type="date" id="resolved_at" name="resolved_at" value="{{ old('resolved_at', $grievance->resolved_at?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label for="hr_comments" class="tich-label">HR comments</label>
                    <textarea id="hr_comments" name="hr_comments" rows="3" class="tich-input" placeholder="HR comments...">{{ old('hr_comments', $grievance->hr_comments) }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Update grievance</button>
                <a href="{{ route('hr.employee-relations.grievances.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
