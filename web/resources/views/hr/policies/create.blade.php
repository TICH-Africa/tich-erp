@extends('layouts.hr')

@section('title', 'Upload Policy')

@section('hr-content')
    <x-page-toolbar title="Upload HR Policy" />

    <div class="uf-form">
        <form method="POST" action="{{ route('hr.policies.store') }}" enctype="multipart/form-data" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">POL · HR policy</div>
                    <div class="uf-amount-bar__sum">Upload policy</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Policy Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="title">Policy Title <span class="uf-req">*</span></label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}" required class="{{ $errors->has('title') ? 'is-invalid' : '' }}">
                            @error('title')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="category">Category <span class="uf-req">*</span></label>
                            <select id="category" name="category" required class="{{ $errors->has('category') ? 'is-invalid' : '' }}">
                                <option value="">Select category</option>
                                <option value="general" @selected(old('category') == 'general')>General</option>
                                <option value="leave" @selected(old('category') == 'leave')>Leave</option>
                                <option value="conduct" @selected(old('category') == 'conduct')>Conduct</option>
                                <option value="benefits" @selected(old('category') == 'benefits')>Benefits</option>
                                <option value="safety" @selected(old('category') == 'safety')>Safety</option>
                                <option value="other" @selected(old('category') == 'other')>Other</option>
                            </select>
                            @error('category')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="file">Policy Document <span class="uf-req">*</span></label>
                            <input type="file" id="file" name="file" required class="{{ $errors->has('file') ? 'is-invalid' : '' }}">
                            @error('file')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            <span class="uf-hint">Max file size: 10MB. PDF, DOC, DOCX, images accepted.</span>
                        </div>
                        <div class="uf-field">
                            <label for="effective_date">Effective Date</label>
                            <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date') }}">
                        </div>
                        <div class="uf-field">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}">
                        </div>
                        <div class="uf-field">
                            <label for="tags">Tags</label>
                            <input type="text" id="tags" name="tags" value="{{ old('tags') }}" placeholder="e.g. leave, annual, 2024">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Upload Policy</button>
                        <a href="{{ route('hr.policies.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
