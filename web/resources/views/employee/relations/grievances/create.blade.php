@extends('layouts.employee')

@section('title', 'New grievance')

@section('employee-content')
    <x-page-toolbar title="New grievance" :meta="$staff->employee_number . ' · ' . ($staff->job_title ?? 'Staff')">
        <x-slot:actions>
            <a href="{{ route('employee.relations.grievances.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('employee.relations.grievances.store') }}" class="tich-form-stack">
            @csrf
            <div class="tich-grid tich-grid--2 tich-mb-6">
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
                    <textarea id="description" name="description" rows="5" required class="tich-input" placeholder="Describe your grievance...">{{ old('description') }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="resolution_notes" class="tich-label">Suggested resolution</label>
                    <textarea id="resolution_notes" name="resolution_notes" rows="3" class="tich-input" placeholder="How would you like this resolved...">{{ old('resolution_notes') }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Submit grievance</button>
                <a href="{{ route('employee.relations.grievances.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
