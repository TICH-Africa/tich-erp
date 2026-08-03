@extends('layouts.app')

@section('title', 'Apply for ' . $vacancy->job_title)

@section('content')
<section class="tich-section" id="apply-vacancy">
    <div class="tich-container">
        <div class="tich-mb-8">
            <a href="{{ route('careers.show', $vacancy) }}" class="tich-btn tich-btn-ghost">&larr; Back to vacancy</a>
            <h1 class="tich-h1 tich-mt-4">Apply for: {{ $vacancy->job_title }}</h1>
            <p class="tich-text tich-text--secondary tich-mt-2">
                {{ $vacancy->department->dept_name ?? 'General' }} &middot; {{ ucfirst($vacancy->employment_type) }}
            </p>
        </div>

        <article class="tich-card">
            <form method="POST" action="{{ route('vacancies.apply.store', $vacancy) }}" enctype="multipart/form-data">
                @csrf

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
                            <option value="Single" {{ old('marital_status') == 'Single' ? 'selected' : '' }}>Single</option>
                            <option value="Married" {{ old('marital_status') == 'Married' ? 'selected' : '' }}>Married</option>
                            <option value="Divorced" {{ old('marital_status') == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                            <option value="Widowed" {{ old('marital_status') == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                            <option value="Separated" {{ old('marital_status') == 'Separated' ? 'selected' : '' }}>Separated</option>
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
                    <div>
                        <label for="highest_qualification" class="tich-label">Highest Qualification *</label>
                        <select id="highest_qualification" name="highest_qualification" required class="tich-input">
                            <option value="">Select qualification</option>
                            <option value="KCSE" {{ old('highest_qualification') == 'KCSE' ? 'selected' : '' }}>KCSE</option>
                            <option value="Diploma" {{ old('highest_qualification') == 'Diploma' ? 'selected' : '' }}>Diploma</option>
                            <option value="Certificate" {{ old('highest_qualification') == 'Certificate' ? 'selected' : '' }}>Certificate</option>
                            <option value="Bachelors" {{ old('highest_qualification') == 'Bachelors' ? 'selected' : '' }}>Bachelors Degree</option>
                            <option value="Masters" {{ old('highest_qualification') == 'Masters' ? 'selected' : '' }}>Masters Degree</option>
                            <option value="PhD" {{ old('highest_qualification') == 'PhD' ? 'selected' : '' }}>PhD</option>
                            <option value="Professional" {{ old('highest_qualification') == 'Professional' ? 'selected' : '' }}>Professional Qualification</option>
                            <option value="Other" {{ old('highest_qualification') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
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

                <h2 class="tich-h2 tich-mb-4">Referees</h2>
                <div class="tich-grid tich-grid--2 tich-mb-6">
                    <div class="tich-card" style="background: #f9fafb;">
                        <h3 class="tich-h3 tich-mb-4">Referee 1</h3>
                        <div class="tich-mb-4">
                            <label for="referee1_name" class="tich-label">Full Name *</label>
                            <input type="text" id="referee1_name" name="referee1_name" value="{{ old('referee1_name') }}" required class="tich-input">
                        </div>
                        <div class="tich-mb-4">
                            <label for="referee1_title" class="tich-label">Title/Position *</label>
                            <input type="text" id="referee1_title" name="referee1_title" value="{{ old('referee1_title') }}" required class="tich-input">
                        </div>
                        <div class="tich-mb-4">
                            <label for="referee1_organization" class="tich-label">Organization *</label>
                            <input type="text" id="referee1_organization" name="referee1_organization" value="{{ old('referee1_organization') }}" required class="tich-input">
                        </div>
                        <div class="tich-mb-4">
                            <label for="referee1_contact" class="tich-label">Contact (Phone/Email) *</label>
                            <input type="text" id="referee1_contact" name="referee1_contact" value="{{ old('referee1_contact') }}" required class="tich-input">
                        </div>
                    </div>
                    <div class="tich-card" style="background: #f9fafb;">
                        <h3 class="tich-h3 tich-mb-4">Referee 2</h3>
                        <div class="tich-mb-4">
                            <label for="referee2_name" class="tich-label">Full Name *</label>
                            <input type="text" id="referee2_name" name="referee2_name" value="{{ old('referee2_name') }}" required class="tich-input">
                        </div>
                        <div class="tich-mb-4">
                            <label for="referee2_title" class="tich-label">Title/Position *</label>
                            <input type="text" id="referee2_title" name="referee2_title" value="{{ old('referee2_title') }}" required class="tich-input">
                        </div>
                        <div class="tich-mb-4">
                            <label for="referee2_organization" class="tich-label">Organization *</label>
                            <input type="text" id="referee2_organization" name="referee2_organization" value="{{ old('referee2_organization') }}" required class="tich-input">
                        </div>
                        <div class="tich-mb-4">
                            <label for="referee2_contact" class="tich-label">Contact (Phone/Email) *</label>
                            <input type="text" id="referee2_contact" name="referee2_contact" value="{{ old('referee2_contact') }}" required class="tich-input">
                        </div>
                    </div>
                </div>

                <h2 class="tich-h2 tich-mb-4">Additional Information</h2>
                <div class="tich-grid tich-grid--2 tich-mb-6">
                    <div>
                        <label for="expected_salary" class="tich-label">Expected Salary</label>
                        <input type="text" id="expected_salary" name="expected_salary" value="{{ old('expected_salary') }}" placeholder="e.g., KES 50,000" class="tich-input">
                    </div>
                    <div>
                        <label for="notice_period" class="tich-label">Notice Period (weeks)</label>
                        <select id="notice_period" name="notice_period" class="tich-input">
                            <option value="">Select notice period</option>
                            <option value="1 week" {{ old('notice_period') == '1 week' ? 'selected' : '' }}>1 week</option>
                            <option value="2 weeks" {{ old('notice_period') == '2 weeks' ? 'selected' : '' }}>2 weeks</option>
                            <option value="3 weeks" {{ old('notice_period') == '3 weeks' ? 'selected' : '' }}>3 weeks</option>
                            <option value="4 weeks" {{ old('notice_period') == '4 weeks' ? 'selected' : '' }}>4 weeks</option>
                            <option value="5 weeks" {{ old('notice_period') == '5 weeks' ? 'selected' : '' }}>5 weeks</option>
                            <option value="6 weeks" {{ old('notice_period') == '6 weeks' ? 'selected' : '' }}>6 weeks</option>
                            <option value="8 weeks" {{ old('notice_period') == '8 weeks' ? 'selected' : '' }}>8 weeks</option>
                            <option value="12 weeks" {{ old('notice_period') == '12 weeks' ? 'selected' : '' }}>12 weeks</option>
                            <option value="Immediate" {{ old('notice_period') == 'Immediate' ? 'selected' : '' }}>Immediate</option>
                        </select>
                    </div>
                </div>

                <div class="tich-card tich-mb-6" style="background: #fef3c7;">
                    <label class="tich-checkbox">
                        <input type="checkbox" name="declaration" required {{ old('declaration') ? 'checked' : '' }}>
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
@endsection
