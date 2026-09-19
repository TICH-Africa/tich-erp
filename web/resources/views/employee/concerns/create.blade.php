@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="Raise a concern" meta="Describe the issue - HR will receive and work on it">
        <x-slot:actions>
            <a href="{{ route('employee.concerns.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('employee.concerns.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">CON · Raise a concern</div>
                    <div class="uf-amount-bar__sum">Submit to HR</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Concern Details</div>
                <div class="uf-section-body">
                    <p class="uf-hint">
                        Be as specific as you can. Include dates and people involved where relevant.
                        HR treats all submissions confidentially within policy limits.
                    </p>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="concern_category">Category <span class="uf-req">*</span></label>
                            <select id="concern_category" name="concern_category" required class="{{ $errors->has('concern_category') ? 'is-invalid' : '' }}">
                                <option value="">Select category</option>
                                @foreach ($categories as $value => $label)
                                    <option value="{{ $value }}" @selected(old('concern_category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('concern_category')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="incident_date">Date of incident (if applicable)</label>
                            <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date') }}" max="{{ now()->toDateString() }}">
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="subject">Subject <span class="uf-req">*</span></label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="300" class="{{ $errors->has('subject') ? 'is-invalid' : '' }}" placeholder="Brief summary of the concern">
                        @error('subject')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uf-field">
                        <label for="description">Details <span class="uf-req">*</span></label>
                        <textarea id="description" name="description" rows="6" required class="{{ $errors->has('description') ? 'is-invalid' : '' }}" placeholder="Describe what happened, who was involved, and how it affects you...">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uf-field">
                        <label for="resolution_notes">What outcome are you seeking? (optional)</label>
                        <textarea id="resolution_notes" name="resolution_notes" rows="3" placeholder="What would help resolve this concern...">{{ old('resolution_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Submit to HR</button>
                        <a href="{{ route('employee.concerns.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
