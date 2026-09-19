@extends('layouts.hr')

@section('title', 'New feedback')

@section('hr-content')
    <x-page-toolbar title="New feedback" meta="Employee Relations">
        <x-slot:actions>
            <a href="{{ route('hr.employee-relations.feedback.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('hr.employee-relations.feedback.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">FDB · Employee Relations</div>
                    <div class="uf-amount-bar__sum">New feedback</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Feedback Details</div>
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
                            <label for="feedback_type">Feedback type</label>
                            <input type="text" id="feedback_type" name="feedback_type" value="{{ old('feedback_type') }}" placeholder="e.g. suggestion, complaint, compliment">
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description <span class="uf-req">*</span></label>
                        <textarea id="description" name="description" rows="5" required class="{{ $errors->has('description') ? 'is-invalid' : '' }}" placeholder="Describe the feedback...">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uf-field">
                        <label for="response">Response</label>
                        <textarea id="response" name="response" rows="3" placeholder="Response to the feedback...">{{ old('response') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create feedback</button>
                        <a href="{{ route('hr.employee-relations.feedback.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
