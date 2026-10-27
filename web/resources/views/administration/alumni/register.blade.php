@extends('layouts.administration')

@section('title', 'Graduation details')

@section('administration-content')
    <x-page-toolbar title="Graduation details" meta="{{ $student->displayName() }} · {{ $student->registration_number ?: 'no admission number' }}">
        <x-slot:actions>
            <a href="{{ route('administration.alumni.create') }}" class="tich-btn tich-btn-ghost">Find another student</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-4">
        <div class="tich-grid tich-grid--3" style="gap:1rem;">
            <div>
                <p class="tich-caption">Programme</p>
                <p><strong>{{ $student->program?->program_name ?? '-' }}</strong></p>
            </div>
            <div>
                <p class="tich-caption">Cohort intake</p>
                <p><strong>{{ $student->cohort_intake ?: '-' }}</strong></p>
            </div>
            <div>
                <p class="tich-caption">Status on file</p>
                <p><strong>{{ ucfirst((string) $student->enrollment_status) }}</strong></p>
            </div>
        </div>
    </div>

    @if ($student->graduation_year || $student->graduation_date)
        <p class="tich-caption tich-mt-4">
            Already registered as finishing {{ $student->graduationLabel() }}
            @if ($student->graduation_award) · {{ $student->graduation_award }} @endif
            — saving here replaces what is on the register.
        </p>
    @endif

    <form method="POST" action="{{ route('administration.alumni.update', $student) }}" class="tich-card tich-mt-4 tich-form-stack">
        @csrf
        @method('PUT')

        <div class="tich-grid tich-grid--3" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="graduation_year">Graduation year <span class="tich-text--danger">*</span></label>
                <input id="graduation_year" type="number" name="graduation_year" class="tich-input" min="1980" max="2100"
                       value="{{ old('graduation_year', $student->graduation_year) }}" placeholder="e.g. 2023">
                @error('graduation_year')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="graduation_date">Graduation date</label>
                <input id="graduation_date" type="date" name="graduation_date" class="tich-input"
                       value="{{ old('graduation_date', $student->graduation_date?->format('Y-m-d')) }}">
                @error('graduation_date')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="graduation_award">Award or class</label>
                <input id="graduation_award" type="text" name="graduation_award" class="tich-input" maxlength="120"
                       value="{{ old('graduation_award', $student->graduation_award) }}" placeholder="e.g. Second Class Upper">
                @error('graduation_award')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
        </div>

        <p class="tich-caption">The year is what the list filters on; the date is optional if you only have the year from the old records.</p>

        <div class="tich-grid tich-grid--2" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="gpa">GPA on file</label>
                <input id="gpa" type="number" step="0.01" min="0" max="4" name="gpa" class="tich-input"
                       value="{{ old('gpa', $student->academicRecords()->whereNotNull('gpa')->orderByDesc('id')->value('gpa')) }}" placeholder="e.g. 3.45">
                @error('gpa')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
                <p class="tich-caption tich-mt-2">Saves against their last study record so Academics sees the same result on their file.</p>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="status">Register as</label>
                <select id="status" name="status" class="tich-input">
                    <option value="graduated" @selected(old('status', $student->enrollment_status) === 'graduated')>Graduated</option>
                    <option value="alumni" @selected(old('status', $student->enrollment_status) === 'alumni')>Alumni (finished earlier, before this system)</option>
                </select>
            </div>
        </div>

        <div class="tich-flex-wrap tich-mt-4" style="gap:0.6rem;">
            <button type="submit" class="tich-btn tich-btn-primary">Save to the register</button>
            <a href="{{ route('administration.alumni.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
        </div>
    </form>
@endsection
