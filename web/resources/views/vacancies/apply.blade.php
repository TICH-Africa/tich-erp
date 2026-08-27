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

        <article class="tich-card">
            <form method="POST" action="{{ route('vacancies.apply.store', $vacancy) }}" enctype="multipart/form-data" id="vacancy-application-form">
                @csrf

                @if ($errors->any())
                    <div class="tich-alert tich-alert--danger tich-mb-6">
                        <ul class="tich-text" style="margin: 0; padding-left: 1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <h2 class="tich-h2 tich-mb-4">Personal Information</h2>
                <div class="tich-grid tich-grid--2 tich-mb-6">
                    <div>
                        <label for="full_name" class="tich-label">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="id_number" class="tich-label">National ID Number *</label>
                        <input type="text" id="id_number" name="id_number" value="{{ old('id_number') }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="date_of_birth" class="tich-label">Date of Birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="gender" class="tich-label">Gender *</label>
                        <select id="gender" name="gender" required class="tich-input">
                            <option value="">Select gender</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="marital_status" class="tich-label">Marital Status</label>
                        <select id="marital_status" name="marital_status" class="tich-input">
                            <option value="">Select</option>
                            @foreach (['Single', 'Married', 'Divorced', 'Widowed', 'Separated'] as $status)
                                <option value="{{ $status }}" {{ old('marital_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="email" class="tich-label">Email Address *</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="phone_number" class="tich-label">Phone Number *</label>
                        <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="postal_address" class="tich-label">Postal Address</label>
                        <input type="text" id="postal_address" name="postal_address" value="{{ old('postal_address') }}" class="tich-input">
                    </div>
                    <div>
                        <label for="physical_address" class="tich-label">Physical Address</label>
                        <input type="text" id="physical_address" name="physical_address" value="{{ old('physical_address') }}" class="tich-input">
                    </div>
                </div>

                <h2 class="tich-h2 tich-mb-4">Education & Qualifications</h2>
                <div class="tich-grid tich-grid--2 tich-mb-6">
                    <div class="tich-grid--2-span-full">
                        <label for="highest_qualification" class="tich-label">Highest Qualification *</label>
                        <select id="highest_qualification" name="highest_qualification" required class="tich-input">
                            <option value="">Select qualification</option>
                            @foreach (['KCSE' => 'KCSE', 'Diploma' => 'Diploma', 'Certificate' => 'Certificate', 'Bachelors' => 'Bachelors Degree', 'Masters' => 'Masters Degree', 'PhD' => 'PhD', 'Professional' => 'Professional Qualification', 'Other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" {{ old('highest_qualification') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="qualification-other-group" class="tich-grid--2-span-full" hidden>
                        <label for="qualification_other" class="tich-label">Specify qualification *</label>
                        <input type="text" id="qualification_other" name="qualification_other" value="{{ old('qualification_other') }}" class="tich-input" placeholder="e.g. Higher Diploma in Community Health">
                    </div>
                    <div>
                        <label for="institution" class="tich-label">Institution *</label>
                        <input type="text" id="institution" name="institution" value="{{ old('institution') }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="year_completed" class="tich-label">Year Completed *</label>
                        <input type="number" id="year_completed" name="year_completed" value="{{ old('year_completed') }}" min="1950" max="{{ date('Y') + 1 }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="grade" class="tich-label">Grade/Class</label>
                        <input type="text" id="grade" name="grade" value="{{ old('grade') }}" placeholder="e.g., Second Class Upper" class="tich-input">
                    </div>
                </div>

                <h2 class="tich-h2 tich-mb-4">Work Experience</h2>
                <div class="tich-grid tich-grid--2 tich-mb-6">
                    <div>
                        <label for="years_of_experience" class="tich-label">Years of Experience *</label>
                        <input type="number" id="years_of_experience" name="years_of_experience" value="{{ old('years_of_experience') }}" min="0" max="50" required class="tich-input">
                    </div>
                    <div>
                        <label for="current_organization" class="tich-label">Current Organization</label>
                        <input type="text" id="current_organization" name="current_organization" value="{{ old('current_organization') }}" class="tich-input">
                    </div>
                    <div class="tich-grid--2-span-full">
                        <label for="area_of_specialization" class="tich-label">Area of Specialization</label>
                        <input type="text" id="area_of_specialization" name="area_of_specialization" value="{{ old('area_of_specialization') }}" class="tich-input">
                    </div>
                </div>

                <h2 class="tich-h2 tich-mb-4">Documents</h2>
                <div class="tich-grid tich-grid--2 tich-mb-6">
                    <div>
                        <label for="cv" class="tich-label">CV/Resume *</label>
                        <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" required class="tich-input">
                        <p class="tich-caption tich-mt-1">PDF, DOC, DOCX up to 10MB</p>
                    </div>
                    <div>
                        <label for="cover_letter" class="tich-label">Cover Letter</label>
                        <input type="file" id="cover_letter" name="cover_letter" accept=".pdf,.doc,.docx" class="tich-input">
                        <p class="tich-caption tich-mt-1">PDF, DOC, DOCX up to 10MB</p>
                    </div>
                    <div class="tich-grid--2-span-full">
                        <label for="certificates" class="tich-label">Certificates/Additional Documents</label>
                        <input type="file" id="certificates" name="certificates[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple class="tich-input">
                        <p class="tich-caption tich-mt-1">You can upload multiple files. Max 5 files.</p>
                    </div>
                </div>

                <h2 class="tich-h2 tich-mb-4">Additional Information</h2>
                <div class="tich-grid tich-grid--2 tich-mb-6">
                    <div class="tich-grid--2-span-full">
                        <label for="expected_salary" class="tich-label">Expected Salary (KES)</label>
                        <input type="number" id="expected_salary" name="expected_salary" value="{{ old('expected_salary') }}" min="0" step="1000" placeholder="e.g. 75000" class="tich-input">
                    </div>
                    <div>
                        <label for="notice_period" class="tich-label">Notice Period</label>
                        <select id="notice_period" name="notice_period" class="tich-input">
                            <option value="">Select notice period</option>
                            @foreach (['1 week', '2 weeks', '3 weeks', '4 weeks', 'Immediate'] as $period)
                                <option value="{{ $period }}" {{ old('notice_period') == $period ? 'selected' : '' }}>{{ $period }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="tich-form-callout tich-mb-6">
                    <label class="tich-checkbox">
                        <input type="checkbox" name="declaration" value="1" required {{ old('declaration') ? 'checked' : '' }}>
                        <span>I declare that the information provided is true and accurate. I understand that any false information may lead to disqualification. *</span>
                    </label>
                </div>

                <div class="tich-mt-6">
                    <button type="submit" class="tich-btn tich-btn-primary">Submit Application</button>
                    <a href="{{ route('careers.show', $vacancy) }}" class="tich-btn tich-btn-ghost">Cancel</a>
                </div>
            </form>
        </article>
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
