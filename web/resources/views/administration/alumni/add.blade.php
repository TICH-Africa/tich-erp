@extends('layouts.administration')

@section('title', 'Add a graduate')

@section('administration-content')
    @php
        // A sheet line that matched nobody arrives here pre-filled, so the officer only adds the rest.
        $given = explode(' ', trim((string) ($prefill['full_name'] ?? '')), 2);
    @endphp

    <x-page-toolbar title="Add a graduate" meta="For the cohorts that were never entered in this system - the record is created already finished">
        <x-slot:actions>
            <a href="{{ route('administration.alumni.index') }}" class="tich-btn tich-btn-ghost">Back to the register</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('administration.alumni.store') }}" enctype="multipart/form-data" class="tich-card tich-mt-4 tich-form-stack">
        @csrf

        <div class="tich-grid tich-grid--3" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="registration_number">Student / admission no. <span class="tich-text--danger">*</span></label>
                <input id="registration_number" type="text" name="registration_number" class="tich-input" maxlength="50" required
                       value="{{ old('registration_number', $prefill['registration_number'] ?? '') }}" placeholder="e.g. AD/1023/19">
                @error('registration_number')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="first_name">First name <span class="tich-text--danger">*</span></label>
                <input id="first_name" type="text" name="first_name" class="tich-input" maxlength="100" required
                       value="{{ old('first_name', $given[0] ?? '') }}">
                @error('first_name')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="surname">Surname <span class="tich-text--danger">*</span></label>
                <input id="surname" type="text" name="surname" class="tich-input" maxlength="100" required
                       value="{{ old('surname', $given[1] ?? '') }}">
                @error('surname')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="tich-grid tich-grid--3" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="middle_name">Other name</label>
                <input id="middle_name" type="text" name="middle_name" class="tich-input" maxlength="100" value="{{ old('middle_name') }}">
                @error('middle_name')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="phone_number">Phone number</label>
                <input id="phone_number" type="tel" name="phone_number" class="tich-input" maxlength="30"
                       value="{{ old('phone_number') }}" placeholder="e.g. 0712345678">
                @error('phone_number')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="email">Email</label>
                <input id="email" type="email" name="email" class="tich-input" maxlength="255" value="{{ old('email') }}">
                @error('email')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="tich-grid tich-grid--2" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="program_id">Course they did <span class="tich-text--danger">*</span></label>
                <select id="program_id" name="program_id" class="tich-input" required>
                    <option value="">Select a course</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected((int) old('program_id') === $program->id)>
                            {{ $program->program_code ? $program->program_code.' - ' : '' }}{{ $program->program_name }}
                        </option>
                    @endforeach
                </select>
                @error('program_id')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="campus_id">Campus they were at <span class="tich-text--danger">*</span></label>
                <select id="campus_id" name="campus_id" class="tich-input" required>
                    <option value="">Select a campus</option>
                    @foreach ($campuses as $campus)
                        <option value="{{ $campus->id }}" @selected((int) old('campus_id') === $campus->id)>
                            {{ $campus->campus_name }}{{ $campus->campus_type === 'community_college' ? ' (community college)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('campus_id')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="tich-grid tich-grid--4" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="year_joined">Year they joined <span class="tich-text--danger">*</span></label>
                <input id="year_joined" type="number" name="year_joined" class="tich-input" min="1960" max="2100" required
                       value="{{ old('year_joined') }}" placeholder="e.g. 2017">
                @error('year_joined')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="graduation_year">Year they ended <span class="tich-text--danger">*</span></label>
                <input id="graduation_year" type="number" name="graduation_year" class="tich-input" min="1960" max="2100" required
                       value="{{ old('graduation_year', $prefill['graduation_year'] ?? '') }}" placeholder="e.g. 2019">
                @error('graduation_year')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="graduation_date">Graduation date</label>
                <input id="graduation_date" type="date" name="graduation_date" class="tich-input" value="{{ old('graduation_date') }}">
                @error('graduation_date')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="current_year">Year of study they ended on</label>
                <select id="current_year" name="current_year" class="tich-input">
                    @foreach ([1, 2, 3, 4] as $year)
                        <option value="{{ $year }}" @selected((int) old('current_year') === $year)>Year {{ $year }}</option>
                    @endforeach
                </select>
                @error('current_year')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="tich-grid tich-grid--3" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="graduation_award">Award or class</label>
                <input id="graduation_award" type="text" name="graduation_award" class="tich-input" maxlength="120"
                       value="{{ old('graduation_award') }}" placeholder="e.g. Second Class Upper">
                @error('graduation_award')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="gpa">GPA</label>
                <input id="gpa" type="number" step="0.01" min="0" max="4" name="gpa" class="tich-input"
                       value="{{ old('gpa') }}" placeholder="e.g. 3.45">
                @error('gpa')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="status">Register as</label>
                <select id="status" name="status" class="tich-input">
                    <option value="graduated" @selected(old('status', 'graduated') === 'graduated')>Graduated</option>
                    <option value="alumni" @selected(old('status') === 'alumni')>Alumni (finished earlier, before this system)</option>
                </select>
            </div>
        </div>

        <div class="tich-form-group">
            <label class="tich-label" for="photo">Profile photo</label>
            <input type="file" id="photo" name="photo" class="tich-input" accept=".jpg,.jpeg,.png,.webp">
            @error('photo')<p class="tich-caption tich-text--danger">{{ $message }}</p>@enderror
            <p class="tich-caption tich-mt-2">Optional, up to 2 MB. The alumni file shows it beside their names.</p>
        </div>

        <p class="tich-caption">
            They are saved as finished, so they stay off the current student lists, the fee reminders and the
            timetable, and appear only on the alumni register. Getting any of this wrong is reversible with
            Remove on the register.
        </p>

        <div class="tich-flex-wrap tich-mt-2" style="gap:0.6rem;">
            <button type="submit" class="tich-btn tich-btn-primary">Add to the alumni register</button>
            <a href="{{ route('administration.alumni.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
        </div>
    </form>
@endsection
