@extends('layouts.app')

@section('title', 'Apply for ' . $vacancy->job_title)
@section('meta_robots', 'noindex,nofollow')

@section('content')
<section class="tich-section tich-careers-page" id="apply-vacancy" aria-labelledby="vacancy-apply-heading">
    <div class="tich-container">
        <div class="tich-mb-8">
            <a href="{{ route('careers.show', $vacancy) }}" class="tich-btn tich-btn-ghost">&larr; Back to vacancy</a>
            <h1 id="vacancy-apply-heading" class="tich-h1 tich-mt-4">Apply for: {{ $vacancy->job_title }}</h1>
            <p class="tich-text tich-text--secondary tich-mt-2">
                {{ $vacancy->department->dept_name ?? 'General' }} &middot; {{ ucfirst($vacancy->employment_type) }}
            </p>
        </div>

        <div class="uf-form">
            <form method="POST" action="{{ route('vacancies.apply.store', $vacancy) }}" enctype="multipart/form-data" id="vacancy-application-form" data-uf="ready">
                @csrf

                <div class="uf-amount-bar">
                    <div>
                        <div class="uf-amount-bar__ref">Careers · Job application</div>
                        <div class="uf-amount-bar__sum">{{ $vacancy->job_title }}</div>
                    </div>
                    <span class="uf-badge">Apply</span>
                </div>

                @if ($errors->any())
                    <div class="tich-alert tich-alert--danger" style="margin: 1rem;">
                        <ul class="tich-text" style="margin: 0; padding-left: 1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="uf-form-section">
                    <div class="uf-section-head">Personal information</div>
                    <div class="uf-section-body">
                        <div class="uf-form-grid-2">
                            <div class="uf-field">
                                <label for="full_name">Full Name <span class="uf-req">*</span></label>
                                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required>
                            </div>
                            <div class="uf-field">
                                <label for="id_number">National ID Number <span class="uf-req">*</span></label>
                                <input type="text" id="id_number" name="id_number" value="{{ old('id_number') }}" required>
                            </div>
                            <div class="uf-field">
                                <label for="date_of_birth">Date of Birth <span class="uf-req">*</span></label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
                            </div>
                            <div class="uf-field">
                                <label for="gender">Gender <span class="uf-req">*</span></label>
                                <select id="gender" name="gender" required>
                                    <option value="">Select gender</option>
                                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                    <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="uf-field">
                                <label for="marital_status">Marital Status</label>
                                <select id="marital_status" name="marital_status">
                                    <option value="">Select</option>
                                    @foreach (['Single', 'Married', 'Divorced', 'Widowed', 'Separated'] as $status)
                                        <option value="{{ $status }}" {{ old('marital_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="uf-field">
                                <label for="email">Email Address <span class="uf-req">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                            </div>
                            <div class="uf-field">
                                <label for="phone_number">Phone Number <span class="uf-req">*</span></label>
                                <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" required>
                            </div>
                            <div class="uf-field">
                                <label for="postal_address">Postal Address</label>
                                <input type="text" id="postal_address" name="postal_address" value="{{ old('postal_address') }}">
                            </div>
                            <div class="uf-field">
                                <label for="physical_address">Physical Address</label>
                                <input type="text" id="physical_address" name="physical_address" value="{{ old('physical_address') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="uf-form-section">
                    <div class="uf-section-head">Education &amp; qualifications</div>
                    <div class="uf-section-body">
                        <div class="uf-form-grid-2">
                            <div class="uf-field" style="grid-column: 1 / -1;">
                                <label for="highest_qualification">Highest Qualification <span class="uf-req">*</span></label>
                                <select id="highest_qualification" name="highest_qualification" required>
                                    <option value="">Select qualification</option>
                                    @foreach (['KCSE' => 'KCSE', 'Diploma' => 'Diploma', 'Certificate' => 'Certificate', 'Bachelors' => 'Bachelors Degree', 'Masters' => 'Masters Degree', 'PhD' => 'PhD', 'Professional' => 'Professional Qualification', 'Other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('highest_qualification') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="uf-field" id="qualification-other-group" style="grid-column: 1 / -1;" hidden>
                                <label for="qualification_other">Specify qualification <span class="uf-req">*</span></label>
                                <input type="text" id="qualification_other" name="qualification_other" value="{{ old('qualification_other') }}" placeholder="e.g. Higher Diploma in Community Health">
                            </div>
                            <div class="uf-field">
                                <label for="institution">Institution <span class="uf-req">*</span></label>
                                <input type="text" id="institution" name="institution" value="{{ old('institution') }}" required>
                            </div>
                            <div class="uf-field">
                                <label for="year_completed">Year Completed <span class="uf-req">*</span></label>
                                <input type="number" id="year_completed" name="year_completed" value="{{ old('year_completed') }}" min="1950" max="{{ date('Y') + 1 }}" required>
                            </div>
                            <div class="uf-field">
                                <label for="grade">Grade/Class</label>
                                <input type="text" id="grade" name="grade" value="{{ old('grade') }}" placeholder="e.g., Second Class Upper">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="uf-form-section">
                    <div class="uf-section-head">Work experience</div>
                    <div class="uf-section-body">
                        <div class="uf-form-grid-2">
                            <div class="uf-field">
                                <label for="years_of_experience">Years of Experience <span class="uf-req">*</span></label>
                                <input type="number" id="years_of_experience" name="years_of_experience" value="{{ old('years_of_experience') }}" min="0" max="50" required>
                            </div>
                            <div class="uf-field">
                                <label for="current_organization">Current Organization</label>
                                <input type="text" id="current_organization" name="current_organization" value="{{ old('current_organization') }}">
                            </div>
                            <div class="uf-field" style="grid-column: 1 / -1;">
                                <label for="area_of_specialization">Area of Specialization</label>
                                <input type="text" id="area_of_specialization" name="area_of_specialization" value="{{ old('area_of_specialization') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="uf-form-section">
                    <div class="uf-section-head">Documents</div>
                    <div class="uf-section-body">
                        <div class="uf-form-grid-2">
                            <div class="uf-field">
                                <label for="cv">CV/Resume <span class="uf-req">*</span></label>
                                <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" required>
                                <span class="uf-hint">PDF, DOC, DOCX up to 10MB</span>
                            </div>
                            <div class="uf-field">
                                <label for="cover_letter">Cover Letter</label>
                                <input type="file" id="cover_letter" name="cover_letter" accept=".pdf,.doc,.docx">
                                <span class="uf-hint">PDF, DOC, DOCX up to 10MB</span>
                            </div>
                            <div class="uf-field" style="grid-column: 1 / -1;">
                                <label for="certificates">Certificates/Additional Documents</label>
                                <input type="file" id="certificates" name="certificates[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple>
                                <span class="uf-hint">You can upload multiple files. Max 5 files.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="uf-form-section">
                    <div class="uf-section-head">Additional information</div>
                    <div class="uf-section-body">
                        <div class="uf-form-grid-2">
                            <div class="uf-field" style="grid-column: 1 / -1;">
                                <label for="expected_salary">Expected Salary (KES)</label>
                                <input type="number" id="expected_salary" name="expected_salary" value="{{ old('expected_salary') }}" min="0" step="1000" placeholder="e.g. 75000">
                            </div>
                            <div class="uf-field">
                                <label for="notice_period">Notice Period</label>
                                <select id="notice_period" name="notice_period">
                                    <option value="">Select notice period</option>
                                    @foreach (['1 week', '2 weeks', '3 weeks', '4 weeks', 'Immediate'] as $period)
                                        <option value="{{ $period }}" {{ old('notice_period') == $period ? 'selected' : '' }}>{{ $period }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="tich-form-callout">
                            <label class="tich-checkbox">
                                <input type="checkbox" name="declaration" value="1" required {{ old('declaration') ? 'checked' : '' }}>
                                <span>I declare that the information provided is true and accurate. I understand that any false information may lead to disqualification. <span class="uf-req">*</span></span>
                            </label>
                        </div>

                        <div class="uf-form-actions">
                            <button type="submit" class="uf-btn uf-btn-primary">Submit Application</button>
                            <a href="{{ route('careers.show', $vacancy) }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        </div>
                    </div>
                </div>

                <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
            </form>
        </div>
    </div>
</section>

<script>
(function () {
    var qualificationSelect = document.getElementById('highest_qualification');
    var otherGroup = document.getElementById('qualification-other-group');
    var otherInput = document.getElementById('qualification_other');

    function toggleQualificationOther() {
        var isOther = qualificationSelect.value === 'Other';
        otherGroup.hidden = !isOther;
        otherInput.required = isOther;
        if (!isOther) {
            otherInput.value = '';
        }
    }

    qualificationSelect?.addEventListener('change', toggleQualificationOther);
    toggleQualificationOther();
})();
</script>
@endsection
