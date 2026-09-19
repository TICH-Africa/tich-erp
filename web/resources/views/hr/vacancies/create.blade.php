@extends('layouts.hr')

@section('title', 'New Vacancy')

@section('hr-content')
    <x-page-toolbar title="Post New Vacancy" />

    <div class="uf-form">
        <form method="POST" action="{{ route('hr.vacancies.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">VAC · Job posting</div>
                    <div class="uf-amount-bar__sum">Post vacancy</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Vacancy Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="job_title">Job Title <span class="uf-req">*</span></label>
                            <input type="text" id="job_title" name="job_title" value="{{ old('job_title') }}" required class="{{ $errors->has('job_title') ? 'is-invalid' : '' }}">
                            @error('job_title')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="department_id">Department <span class="uf-req">*</span></label>
                            <select id="department_id" name="department_id" required class="{{ $errors->has('department_id') ? 'is-invalid' : '' }}">
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                        {{ $department->dept_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="employment_type">Employment Type <span class="uf-req">*</span></label>
                            <select id="employment_type" name="employment_type" required class="{{ $errors->has('employment_type') ? 'is-invalid' : '' }}">
                                <option value="">Select type</option>
                                <option value="permanent" @selected(old('employment_type') == 'permanent')>Permanent</option>
                                <option value="contract" @selected(old('employment_type') == 'contract')>Contract</option>
                                <option value="intern" @selected(old('employment_type') == 'intern')>Intern</option>
                                <option value="visiting" @selected(old('employment_type') == 'visiting')>Visiting</option>
                                <option value="casual" @selected(old('employment_type') == 'casual')>Casual</option>
                            </select>
                            @error('employment_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="slots_available">Number of Positions <span class="uf-req">*</span></label>
                            <input type="number" id="slots_available" name="slots_available" value="{{ old('slots_available', 1) }}" min="1" required class="{{ $errors->has('slots_available') ? 'is-invalid' : '' }}">
                            @error('slots_available')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="min_qualification">Minimum Qualification <span class="uf-req">*</span></label>
                            <input type="text" id="min_qualification" name="min_qualification" value="{{ old('min_qualification') }}" required class="{{ $errors->has('min_qualification') ? 'is-invalid' : '' }}">
                            @error('min_qualification')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="closing_date">Closing Date <span class="uf-req">*</span></label>
                            <input type="date" id="closing_date" name="closing_date" value="{{ old('closing_date') }}" required class="{{ $errors->has('closing_date') ? 'is-invalid' : '' }}">
                            @error('closing_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label class="tich-checkbox">
                                <input type="checkbox" id="is_published" name="is_published" value="1" @checked(old('is_published'))>
                                <span>Publish immediately</span>
                            </label>
                        </div>
                        <div class="uf-field">
                            <label class="tich-checkbox">
                                <input type="checkbox" id="closes_automatically" name="closes_automatically" value="1" @checked(old('closes_automatically', true))>
                                <span>Close automatically when full</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Job Description</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label for="job_description">Job Description <span class="uf-req">*</span></label>
                        <textarea id="job_description" name="job_description" rows="4" required class="{{ $errors->has('job_description') ? 'is-invalid' : '' }}">{{ old('job_description') }}</textarea>
                        @error('job_description')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="requirements">Requirements <span class="uf-req">*</span></label>
                            <textarea id="requirements" name="requirements" rows="4" required class="{{ $errors->has('requirements') ? 'is-invalid' : '' }}">{{ old('requirements') }}</textarea>
                            @error('requirements')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="responsibilities">Responsibilities <span class="uf-req">*</span></label>
                            <textarea id="responsibilities" name="responsibilities" rows="4" required class="{{ $errors->has('responsibilities') ? 'is-invalid' : '' }}">{{ old('responsibilities') }}</textarea>
                            @error('responsibilities')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Post Vacancy</button>
                        <a href="{{ route('hr.vacancies.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
