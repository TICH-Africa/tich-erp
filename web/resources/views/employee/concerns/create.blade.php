@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="Raise a concern" meta="Describe the issue — HR will receive and work on it">
        <x-slot:actions>
            <a href="{{ route('employee.concerns.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card tich-mt-6">
        <p class="tich-text tich-text--secondary">
            Be as specific as you can. Include dates and people involved where relevant.
            HR treats all submissions confidentially within policy limits.
        </p>

        <form method="POST" action="{{ route('employee.concerns.store') }}" class="tich-form-stack tich-mt-6">
            @csrf
            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div>
                    <label for="concern_category" class="tich-label">Category *</label>
                    <select id="concern_category" name="concern_category" required class="tich-input @error('concern_category') tich-input--error @enderror">
                        <option value="">Select category</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('concern_category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('concern_category')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="incident_date" class="tich-label">Date of incident (if applicable)</label>
                    <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date') }}" max="{{ now()->toDateString() }}" class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label for="subject" class="tich-label">Subject *</label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="300" class="tich-input @error('subject') tich-input--error @enderror" placeholder="Brief summary of the concern">
                    @error('subject')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="tich-grid--span-2">
                    <label for="description" class="tich-label">Details *</label>
                    <textarea id="description" name="description" rows="6" required class="tich-input @error('description') tich-input--error @enderror" placeholder="Describe what happened, who was involved, and how it affects you...">{{ old('description') }}</textarea>
                    @error('description')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="tich-grid--span-2">
                    <label for="resolution_notes" class="tich-label">What outcome are you seeking? (optional)</label>
                    <textarea id="resolution_notes" name="resolution_notes" rows="3" class="tich-input" placeholder="What would help resolve this concern...">{{ old('resolution_notes') }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Submit to HR</button>
                <a href="{{ route('employee.concerns.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
