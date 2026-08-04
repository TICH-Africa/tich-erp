@extends('layouts.hr')

@section('title', 'Upload Policy')

@section('hr-content')
    <div class="tich-mb-8">
        <a href="{{ route('hr.policies.index') }}" class="tich-btn tich-btn-ghost">&larr; Back to policies</a>
    </div>

    <article class="tich-card">
        <h1 class="tich-h1 tich-mb-6">Upload HR Policy</h1>

        <form method="POST" action="{{ route('hr.policies.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="title" class="tich-label">Policy Title *</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required class="tich-input">
                </div>
                <div>
                    <label for="category" class="tich-label">Category *</label>
                    <select id="category" name="category" required class="tich-input">
                        <option value="">Select category</option>
                        <option value="general" {{ old('category') == 'general' ? 'selected' : '' }}>General</option>
                        <option value="leave" {{ old('category') == 'leave' ? 'selected' : '' }}>Leave</option>
                        <option value="conduct" {{ old('category') == 'conduct' ? 'selected' : '' }}>Conduct</option>
                        <option value="benefits" {{ old('category') == 'benefits' ? 'selected' : '' }}>Benefits</option>
                        <option value="safety" {{ old('category') == 'safety' ? 'selected' : '' }}>Safety</option>
                        <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="tich-grid--span-2">
                    <label for="description" class="tich-label">Description</label>
                    <textarea id="description" name="description" rows="3" class="tich-input">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label for="file" class="tich-label">Policy Document *</label>
                    <input type="file" id="file" name="file" required class="tich-input">
                    <p class="tich-caption tich-mt-1">Max file size: 10MB. PDF, DOC, DOCX, images accepted.</p>
                </div>
                <div>
                    <label for="effective_date" class="tich-label">Effective Date</label>
                    <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date') }}" class="tich-input">
                </div>
                <div>
                    <label for="expiry_date" class="tich-label">Expiry Date</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}" class="tich-input">
                </div>
                <div>
                    <label for="tags" class="tich-label">Tags</label>
                    <input type="text" id="tags" name="tags" value="{{ old('tags') }}" placeholder="e.g. leave, annual, 2024" class="tich-input">
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Upload Policy</button>
                <a href="{{ route('hr.policies.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
