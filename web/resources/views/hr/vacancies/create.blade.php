@extends('layouts.hr')

@section('title', 'New Vacancy')

@section('hr-content')
    <x-page-toolbar title="Post New Vacancy" />

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.vacancies.store') }}">
            @csrf

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="job_title" class="tich-label">Job Title *</label>
                    <input type="text" id="job_title" name="job_title" value="{{ old('job_title') }}" required class="tich-input">
                </div>
                <div>
                    <label for="department_id" class="tich-label">Department *</label>
                    <select id="department_id" name="department_id" required class="tich-input">
                        <option value="">Select department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->dept_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="employment_type" class="tich-label">Employment Type *</label>
                    <select id="employment_type" name="employment_type" required class="tich-input">
                        <option value="">Select type</option>
                        <option value="permanent" {{ old('employment_type') == 'permanent' ? 'selected' : '' }}>Permanent</option>
                        <option value="contract" {{ old('employment_type') == 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="intern" {{ old('employment_type') == 'intern' ? 'selected' : '' }}>Intern</option>
                        <option value="visiting" {{ old('employment_type') == 'visiting' ? 'selected' : '' }}>Visiting</option>
                        <option value="casual" {{ old('employment_type') == 'casual' ? 'selected' : '' }}>Casual</option>
                    </select>
                </div>
                <div>
                    <label for="slots_available" class="tich-label">Number of Positions *</label>
                    <input type="number" id="slots_available" name="slots_available" value="{{ old('slots_available', 1) }}" min="1" required class="tich-input">
                </div>
                <div>
                    <label for="min_qualification" class="tich-label">Minimum Qualification *</label>
                    <input type="text" id="min_qualification" name="min_qualification" value="{{ old('min_qualification') }}" required class="tich-input">
                </div>
                <div>
                    <label for="closing_date" class="tich-label">Closing Date *</label>
                    <input type="date" id="closing_date" name="closing_date" value="{{ old('closing_date') }}" required class="tich-input">
                </div>
                <div class="tich-flex--center">
                    <label class="tich-checkbox">
                        <input type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}>
                        <span>Publish immediately</span>
                    </label>
                </div>
                <div class="tich-flex--center">
                    <label class="tich-checkbox">
                        <input type="checkbox" id="closes_automatically" name="closes_automatically" value="1" {{ old('closes_automatically', true) ? 'checked' : '' }}>
                        <span>Close automatically when full</span>
                    </label>
                </div>
            </div>

            <div class="tich-mb-6">
                <label for="job_description" class="tich-label">Job Description *</label>
                <textarea id="job_description" name="job_description" rows="4" required class="tich-input">{{ old('job_description') }}</textarea>
            </div>

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="requirements" class="tich-label">Requirements *</label>
                    <textarea id="requirements" name="requirements" rows="4" required class="tich-input">{{ old('requirements') }}</textarea>
                </div>
                <div>
                    <label for="responsibilities" class="tich-label">Responsibilities *</label>
                    <textarea id="responsibilities" name="responsibilities" rows="4" required class="tich-input">{{ old('responsibilities') }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Post Vacancy</button>
                <a href="{{ route('hr.vacancies.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
