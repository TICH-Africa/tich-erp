@extends('layouts.app')

@section('title', 'Sponsor a Student at TICH')
@section('meta_description', 'Express your interest in sponsoring a student at TICH. Our team will contact you to discuss sponsorship options and matching with a student.')

@section('content')
    <header class="tich-course-page-header" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);">
        <div class="tich-container">
            <h1 class="tich-course-page-header__title">Sponsor a Student</h1>
            <p class="tich-course-page-header__lead">
                Express your interest in sponsoring a student's education journey at TICH. 
                Our team will contact you to discuss options and matching with a student.
            </p>
        </div>
    </header>

    <section class="tich-section tich-mt-8">
        <div class="tich-container">
            <article class="tich-card">
                <div class="tich-card__body">
                    <h3 class="tich-h3 tich-mb-4" style="color: #1e40af;">Sponsorship Interest Form</h3>
                    <p class="tich-text tich-mb-6">
                        Fill out this form to express your interest in sponsoring a student at TICH. 
                        Our team will contact you to discuss sponsorship options, matching with a student, and next steps.
                    </p>

                    <form method="POST" action="{{ route('sponsor.store') }}" class="tich-form-stack" id="sponsorship-form">
                        @csrf

                        <div class="tich-form-group">
                            <label class="tich-label" for="sponsor_type">Sponsorship Type</label>
                            <select id="sponsor_type" name="sponsor_type" class="tich-input" required>
                                <option value="full">Full Sponsorship</option>
                                <option value="partial">Partial Sponsorship</option>
                                <option value="co_sponsor">Co-Sponsorship (Shared)</option>
                                <option value="named_scholarship">Named Scholarship Fund</option>
                            </select>
                        </div>

                        <div class="tich-form-group">
                            <label class="tich-label" for="duration">Preferred Duration</label>
                            <select id="duration" name="duration" class="tich-input">
                                <option value="1">1 Year</option>
                                <option value="2">2 Years</option>
                                <option value="3" selected>Full Program (3 Years)</option>
                                <option value="ongoing">Ongoing / Annual Renewal</option>
                            </select>
                        </div>

                        <div class="tich-form-group">
                            <label class="tich-label" for="preferred_field">Preferred Field of Study (Optional)</label>
                            <select id="preferred_field" name="preferred_field" class="tich-input">
                                <option value="">Any field / Most needed</option>
                                <option value="health_sciences">Health Sciences / Community Health</option>
                                <option value="development_studies">Development Studies</option>
                                <option value="technology">Technology / ICT</option>
                                <option value="business">Business / Finance</option>
                            </select>
                        </div>

                        <hr class="tich-my-4">

                        <div class="tich-form-group">
                            <label class="tich-label">Your Information</label>
                            <div class="tich-form-grid-2">
                                <input type="text" name="sponsor_name" class="tich-input" placeholder="Full Name / Organization" required>
                                <input type="email" name="sponsor_email" class="tich-input" placeholder="Email Address" required>
                            </div>
                            <input type="tel" name="sponsor_phone" class="tich-input tich-mt-3" placeholder="Phone Number" required>
                            <textarea name="sponsor_message" class="tich-input tich-mt-3" rows="3" placeholder="Any specific preferences or message for our team"></textarea>
                        </div>

                        <button type="submit" class="tich-btn tich-btn-primary tich-btn-block" style="background: #1e40af; border-color: #1e40af; margin-top: 0.5rem;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                            Submit Sponsorship Inquiry
                        </button>

                        <p class="tich-caption tich-text--muted tich-mt-4" style="text-align: center;">
                            Our team will contact you within 2 business days to discuss matching with a student and next steps.
                        </p>
                    </form>
                </div>
            </article>
        </div>
    </section>
@endsection