@extends('layouts.employee')

@section('title', 'New feedback')

@section('employee-content')
    <x-page-toolbar title="New feedback" :meta="$staff->employee_number . ' · ' . ($staff->job_title ?? 'Staff')">
        <x-slot:actions>
            <a href="{{ route('employee.relations.feedback.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('employee.relations.feedback.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">FDB · {{ $staff->employee_number }}</div>
                    <div class="uf-amount-bar__sum">New feedback</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Feedback Details</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label for="feedback_type">Feedback type</label>
                        <input type="text" id="feedback_type" name="feedback_type" value="{{ old('feedback_type') }}" placeholder="e.g. suggestion, complaint, compliment">
                    </div>
                    <div class="uf-field">
                        <label for="description">Description <span class="uf-req">*</span></label>
                        <textarea id="description" name="description" rows="5" required class="{{ $errors->has('description') ? 'is-invalid' : '' }}" placeholder="Describe your feedback...">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uf-field">
                        <label for="response">Suggested response</label>
                        <textarea id="response" name="response" rows="3" placeholder="What response would you expect...">{{ old('response') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Submit feedback</button>
                        <a href="{{ route('employee.relations.feedback.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
