@extends('layouts.app')

@section('title', 'Donate to TICH Students')
@section('meta_description', 'Express your interest in donating to TICH students. Our finance team will contact you to discuss donation options.')

@section('content')
    <header class="tich-course-page-header" style="background: linear-gradient(135deg, #166534 0%, #16a34a 100%);">
        <div class="tich-container">
            <h1 class="tich-course-page-header__title">Support Our Students</h1>
            <p class="tich-course-page-header__lead">
                Your generosity helps fund scholarships, emergency aid, and financial assistance programs for students in need.
            </p>
        </div>
    </header>

    <section class="tich-section tich-mt-8">
        <div class="tich-container">
            <article class="tich-card">
                <div class="tich-card__body">
                    <h3 class="tich-h3 tich-mb-4" style="color: #166534;">Donation Interest Form</h3>
                    <p class="tich-text tich-mb-6">
                        Fill out this form to express your interest in donating to TICH students. Our finance team will contact you to discuss donation options, payment methods, and provide any information you need.
                    </p>

                    <form method="POST" action="{{ route('donate.store') }}" class="tich-form-stack" id="donation-form">
                        @csrf

                        <div class="tich-form-group">
                            <label class="tich-label" for="donation_type">Type of Donation</label>
                            <select id="donation_type" name="donation_type" class="tich-input" required>
                                <option value="one_time">One-time Donation</option>
                                <option value="monthly">Monthly Recurring</option>
                                <option value="annual">Annual Pledge</option>
                                <option value="legacy">Legacy/Planned Giving</option>
                            </select>
                        </div>

                        <div class="tich-form-group">
                            <label class="tich-label" for="amount">Intended Amount (KES) <span class="tich-req">*</span></label>
                            <input type="number" id="amount" name="amount" class="tich-input" step="0.01" min="100" placeholder="Enter intended amount" required>
                        </div>

                        <div class="tich-form-group">
                            <label class="tich-label" for="designation">Designate to Fund (Optional)</label>
                            <select id="designation" name="designation" class="tich-input">
                                <option value="">General Fund (where needed most)</option>
                                <option value="scholarships">Scholarship Fund</option>
                                <option value="emergency_aid">Emergency Student Aid</option>
                                <option value="program_grants">Program-Specific Grants</option>
                                <option value="student_welfare">Student Welfare Fund</option>
                            </select>
                        </div>

                        <hr class="tich-my-4">

                        <div class="tich-form-group">
                            <label class="tich-label">Your Contact Information</label>
                            <div class="tich-form-grid-2">
                                <input type="text" name="donor_name" class="tich-input" placeholder="Full Name" required>
                                <input type="email" name="donor_email" class="tich-input" placeholder="Email Address" required>
                            </div>
                            <input type="tel" name="donor_phone" class="tich-input tich-mt-3" placeholder="Phone Number" required>
                            <textarea name="message" class="tich-input tich-mt-3" rows="3" placeholder="Any specific preferences, dedication, or questions for our finance team"></textarea>
                        </div>

                        <button type="submit" class="tich-btn tich-btn-success tich-btn-block" style="background: #16a34a; border-color: #16a34a; margin-top: 0.5rem;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                                <path d="M12 21V7m0 0l-4 4m4-4l4 4M5 21h14"/>
                                <path d="M21 15a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/>
                            </svg>
                            Submit Donation Inquiry
                        </button>

                        <p class="tich-caption tich-text--muted tich-mt-4" style="text-align: center;">
                            Our finance team will contact you within 2 business days to discuss payment options (M-Pesa, Bank Transfer, etc.) and answer any questions.
                        </p>
                    </form>
                </div>
            </article>
        </div>
    </section>
@endsection