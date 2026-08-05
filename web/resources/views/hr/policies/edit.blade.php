@extends('layouts.hr')

@section('title', 'Edit Policy')

@section('hr-content')
    <x-page-toolbar title="Edit HR Policy" />

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.policies.update', $policy) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="title" class="tich-label">Policy Title *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $policy->title) }}" required class="tich-input">
                </div>
                <div>
                    <label for="category" class="tich-label">Category *</label>
                    <select id="category" name="category" required class="tich-input">
                        <option value="">Select category</option>
                        <option value="general" {{ old('category', $policy->category) == 'general' ? 'selected' : '' }}>General</option>
                        <option value="leave" {{ old('category', $policy->category) == 'leave' ? 'selected' : '' }}>Leave</option>
                        <option value="conduct" {{ old('category', $policy->category) == 'conduct' ? 'selected' : '' }}>Conduct</option>
                        <option value="benefits" {{ old('category', $policy->category) == 'benefits' ? 'selected' : '' }}>Benefits</option>
                        <option value="safety" {{ old('category', $policy->category) == 'safety' ? 'selected' : '' }}>Safety</option>
                        <option value="other" {{ old('category', $policy->category) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="tich-grid--span-2">
                    <label for="description" class="tich-label">Description</label>
                    <textarea id="description" name="description" rows="3" class="tich-input">{{ old('description', $policy->description) }}</textarea>
                </div>
                <div>
                    <label for="file" class="tich-label">Replace Document</label>
                    <input type="file" id="file" name="file" class="tich-input">
                    <p class="tich-caption tich-mt-1">Leave empty to keep current file. Max: 10MB.</p>
                    @if ($policy->file_path)
                        <p class="tich-caption tich-mt-1">Current: {{ $policy->original_filename }}</p>
                    @endif
                </div>
                <div>
                    <label for="effective_date" class="tich-label">Effective Date</label>
                    <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date', $policy->effective_date?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div>
                    <label for="expiry_date" class="tich-label">Expiry Date</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', $policy->expiry_date?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div>
                    <label for="tags" class="tich-label">Tags</label>
                    <input type="text" id="tags" name="tags" value="{{ old('tags', $policy->tags) }}" placeholder="e.g. leave, annual, 2024" class="tich-input">
                </div>
                <div class="tich-flex--center">
                    <label class="tich-checkbox">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $policy->is_active) ? 'checked' : '' }}>
                        <span>Active</span>
                    </label>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Update Policy</button>
                <a href="{{ route('hr.policies.show', $policy) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
