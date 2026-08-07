@extends('layouts.hr')

@section('title', 'New feedback')

@section('hr-content')
    <x-page-toolbar title="New feedback" meta="Employee Relations">
        <x-slot:actions>
            <a href="{{ route('hr.employee-relations.feedback.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.employee-relations.feedback.store') }}" class="tich-form-stack">
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
                    <label for="feedback_type" class="tich-label">Feedback type</label>
                    <input type="text" id="feedback_type" name="feedback_type" value="{{ old('feedback_type') }}" class="tich-input" placeholder="e.g. suggestion, complaint, compliment">
                </div>
                <div class="tich-grid--span-2">
                    <label for="description" class="tich-label">Description *</label>
                    <textarea id="description" name="description" rows="5" required class="tich-input" placeholder="Describe the feedback...">{{ old('description') }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="response" class="tich-label">Response</label>
                    <textarea id="response" name="response" rows="3" class="tich-input" placeholder="Response to the feedback...">{{ old('response') }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Create feedback</button>
                <a href="{{ route('hr.employee-relations.feedback.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
