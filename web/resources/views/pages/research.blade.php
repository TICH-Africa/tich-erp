@extends('layouts.app')

@section('title', 'Research')

@section('content')
    <section class="tich-section">
        <div class="tich-container">
            <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
                <div>
                    <h2 class="tich-h2">A Hub of Research Excellence</h2>
                    <p class="tich-text tich-mt-4">
                        From health and education to technology, environment, and social development, our multidisciplinary research teams work at the intersection of theory and practice-translating research into action and policy.
                    </p>
                    <p class="tich-text tich-mt-4">
                        Through partnership with local and global organization, we address real-world challenges by generating knowledge that empowers individuals and strengthens communities.
                    </p>
                    <p class="tich-text tich-mt-4">
                        We foster a vibrant culture of inquiry and innovation, where students, faculty, and partners collaborate to find sustainable solution to pressing societal issues.
                    </p>

                    <div class="tich-mt-6">
                        <h3 class="tich-h3">Our Research Agenda</h3>
                        <p class="tich-text tich-mt-4">
                            Our research agenda is anchored in evidence-based public and community health, with a growing focus on digital transformation in healthcare. We are driven by the need to bridge the gap between evidence and practice, working closely with communities, policymakers, and practitioners to ensure our research is relevant, impactful, and inclusive.
                        </p>
                        <p class="tich-text tich-mt-4">
                            We support an interdisciplinary approach that blends traditional health research with emerging digital innovations-generating solutions that respond to the evolving needs of the health sector in Africa and beyond.
                        </p>
                    </div>

                    <div class="tich-mt-6">
                        <h3 class="tich-h3">Our Research Areas</h3>
                        <ul class="tich-mt-4">
                            <li>Community Health Systems Strengthening</li>
                            <li>Maternal, Neonatal, and Child Health (MNCH)</li>
                            <li>Health Equity and Social Determinants of Health</li>
                            <li>Digital Health and Innovation</li>
                            <li>Health Policy and Implementation Science</li>
                            <li>Climate Change and Health</li>
                        </ul>
                        <p class="tich-caption tich-mt-4">We are currently actively looking for partnerships to pursue research in this area. These thematic areas guide our project development, funding applications, and partnerships.</p>
                    </div>
                </div>
                <div class="tich-card">
                    <h3 class="tich-h3">Be the Research Partner</h3>
                    <form class="tich-mt-4" onsubmit="event.preventDefault(); alert('Thank you for your interest. Our research team will contact you.');">
                        <div class="tich-grid tich-grid--2">
                            <div>
                                <label class="tich-label">First Name</label>
                                <input type="text" class="tich-input" required>
                            </div>
                            <div>
                                <label class="tich-label">Last Name</label>
                                <input type="text" class="tich-input" required>
                            </div>
                        </div>
                        <div class="tich-mt-4">
                            <label class="tich-label">Email</label>
                            <input type="email" class="tich-input" required>
                        </div>
                        <div class="tich-mt-4">
                            <label class="tich-label">Phone</label>
                            <input type="tel" class="tich-input">
                        </div>
                        <div class="tich-mt-4">
                            <label class="tich-label">Interested Research</label>
                            <select class="tich-input">
                                <option>Community Health Systems</option>
                                <option>MNCH</option>
                                <option>Health Equity</option>
                                <option>Digital Health</option>
                                <option>Health Policy</option>
                                <option>Climate Change and Health</option>
                            </select>
                        </div>
                        <div class="tich-mt-4">
                            <label class="tich-label">Overview</label>
                            <textarea class="tich-input" rows="4"></textarea>
                        </div>
                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Submit Partnership Inquiry</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
