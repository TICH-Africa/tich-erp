@extends('layouts.hr')

@section('title', 'Edit feedback #' . $feedback->id)

@section('hr-content')
    <x-page-toolbar title="Edit feedback" :meta="'#'.$feedback->id . ' · Employee Relations'">
        <x-slot:actions>
            <a href="{{ route('hr.employee-relations.feedback.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.employee-relations.feedback.update', $feedback) }}" class="tich-form-stack">
            @csrf
            @method('PUT')
            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label class="tich-label">Employee</label>
                    <input type="text" value="{{ $feedback->staff->fullName() }} ({{ $feedback->staff->employee_number }})" disabled class="tich-input">
                </div>
                <div>
                    <label for="feedback_type" class="tich-label">Feedback type</label>
                    <input type="text" id="feedback_type" name="feedback_type" value="{{ old('feedback_type', $feedback->feedback_type) }}" class="tich-input" placeholder="e.g. suggestion, complaint, compliment">
                </div>
                <div class="tich-grid--span-2">
                    <label for="description" class="tich-label">Description</label>
                    <textarea id="description" name="description" rows="5" class="tich-input" placeholder="Describe the feedback...">{{ old('description', $feedback->description) }}</textarea>
                </div>
                <div>
                    <label for="status" class="tich-label">Status *</label>
                    <select id="status" name="status" required class="tich-input">
                        @foreach (['open', 'under_review', 'resolved', 'closed'] as $status)
                            <option value="{{ $status }}" {{ old('status', $feedback->status) === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="resolved_at" class="tich-label">Resolved date</label>
                    <input type="date" id="resolved_at" name="resolved_at" value="{{ old('resolved_at', $feedback->resolved_at?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label for="response" class="tich-label">Response</label>
                    <textarea id="response" name="response" rows="3" class="tich-input" placeholder="Response to the feedback...">{{ old('response', $feedback->response) }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="hr_comments" class="tich-label">HR comments</label>
                    <textarea id="hr_comments" name="hr_comments" rows="3" class="tich-input" placeholder="HR comments...">{{ old('hr_comments', $feedback->hr_comments) }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Update feedback</button>
                <a href="{{ route('hr.employee-relations.feedback.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
