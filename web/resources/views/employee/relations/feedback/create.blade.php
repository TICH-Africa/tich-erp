@extends('layouts.employee')

@section('title', 'New feedback')

@section('employee-content')
    <x-page-toolbar title="New feedback" :meta="$staff->employee_number . ' · ' . ($staff->job_title ?? 'Staff')">
        <x-slot:actions>
            <a href="{{ route('employee.relations.feedback.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('employee.relations.feedback.store') }}" class="tich-form-stack">
            @csrf
            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="feedback_type" class="tich-label">Feedback type</label>
                    <input type="text" id="feedback_type" name="feedback_type" value="{{ old('feedback_type') }}" class="tich-input" placeholder="e.g. suggestion, complaint, compliment">
                </div>
                <div class="tich-grid--span-2">
                    <label for="description" class="tich-label">Description *</label>
                    <textarea id="description" name="description" rows="5" required class="tich-input" placeholder="Describe your feedback...">{{ old('description') }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="response" class="tich-label">Suggested response</label>
                    <textarea id="response" name="response" rows="3" class="tich-input" placeholder="What response would you expect...">{{ old('response') }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Submit feedback</button>
                <a href="{{ route('employee.relations.feedback.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
