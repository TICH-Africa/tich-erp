@extends('layouts.hr')

@section('title', 'New grievance')

@section('hr-content')
    <x-page-toolbar title="New grievance" meta="Employee Relations">
        <x-slot:actions>
            <a href="{{ route('hr.employee-relations.grievances.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.employee-relations.grievances.store') }}" class="tich-form-stack">
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
                    <label for="grievance_type" class="tich-label">Grievance type</label>
                    <input type="text" id="grievance_type" name="grievance_type" value="{{ old('grievance_type') }}" class="tich-input" placeholder="e.g. workplace, compensation, management">
                </div>
                <div>
                    <label for="incident_date" class="tich-label">Incident date</label>
                    <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date') }}" class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label for="description" class="tich-label">Description *</label>
                    <textarea id="description" name="description" rows="5" required class="tich-input" placeholder="Describe the grievance...">{{ old('description') }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="resolution_notes" class="tich-label">Resolution notes</label>
                    <textarea id="resolution_notes" name="resolution_notes" rows="3" class="tich-input" placeholder="How was this resolved...">{{ old('resolution_notes') }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Create grievance</button>
                <a href="{{ route('hr.employee-relations.grievances.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
