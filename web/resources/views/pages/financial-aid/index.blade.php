@extends('layouts.app')

@section('title', 'Financial Aid & Sponsorship')
@section('meta_description', 'Explore scholarships, grants, and financial aid opportunities at TICH. We believe financial circumstances should not be a barrier to quality education.')

@section('content')
    <header class="tich-course-page-header" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);">
        <div class="tich-container">
            <h1 class="tich-course-page-header__title">Financial Aid & Sponsorship</h1>
            <p class="tich-course-page-header__lead">
                We believe that financial circumstances should not be a barrier to quality education. 
                Explore our available sponsorship opportunities and financial aid programs designed to support your academic journey.
            </p>
        </div>
    </header>

    <section class="tich-section tich-mt-8">
        <div class="tich-container">
            <div class="tich-grid tich-grid--3 tich-mb-8" style="gap: 1.5rem;">
                <article class="tich-card tich-card--feature">
                    <div class="tich-card__icon" style="background: #dbeafe; color: #1e40af;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <h3 class="tich-h4 tich-mt-4">Scholarships</h3>
                    <p class="tich-text tich-text--muted tich-mt-2">Merit-based awards for academic excellence and outstanding achievements.</p>
                </article>

                <article class="tich-card tich-card--feature">
                    <div class="tich-card__icon" style="background: #dcfce7; color: #166534;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <h3 class="tich-h4 tich-mt-4">Grants</h3>
                    <p class="tich-text tich-text--muted tich-mt-2">Need-based financial support that doesn't require repayment.</p>
                </article>

                <article class="tich-card tich-card--feature">
                    <div class="tich-card__icon" style="background: #fef3c7; color: #92400e;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                    </div>
                    <h3 class="tich-h4 tich-mt-4">Work-Study</h3>
                    <p class="tich-text tich-text--muted tich-mt-2">Earn while you learn through campus employment opportunities.</p>
                </article>
            </div>

            <div class="tich-section tich-mt-8">
                <h2 id="opportunities-heading" class="tich-h2" style="margin-bottom: 1.5rem;">Available Opportunities</h2>

                @if ($opportunities->isNotEmpty())
                    <div class="tich-card">
                        <div class="tich-card__body">
                            @foreach ($opportunities as $opportunity)
                                <article class="tich-card tich-mb-4" style="border: 1px solid #e5e7eb;">
                                    <div class="tich-card__body tich-grid tich-grid--4" style="gap: 1.5rem; align-items: start;">
                                        <div style="grid-column: span 3;">
                                            <div class="tich-flex tich-flex--between tich-mb-2">
                                                <h3 class="tich-h4" style="margin: 0;">{{ $opportunity->title }}</h3>
                                                @php
                                                    $typeColors = [
                                                        'scholarship' => 'bg-blue-100 text-blue-800',
                                                        'grant' => 'bg-green-100 text-green-800',
                                                        'loan' => 'bg-amber-100 text-amber-800',
                                                        'work_study' => 'bg-purple-100 text-purple-800',
                                                    ];
                                                    $statusColors = [
                                                        'published' => 'bg-green-100 text-green-800',
                                                        'closed' => 'bg-gray-100 text-gray-800',
                                                    ];
                                                @endphp
                                                <span class="tich-badge {{ $typeColors[$opportunity->funding_type] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $opportunity->funding_type)) }}
                                                </span>
                                            </div>
                                            <p class="tich-text tich-text--muted tich-mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                {{ Str::limit($opportunity->description, 200) }}
                                            </p>
                                            <div class="tich-flex tich-flex--wrap tich-gap-4 tich-text--sm tich-text--muted">
                                                @if ($opportunity->amount)
                                                    <span><strong>Amount:</strong> KES {{ number_format((float) $opportunity->amount, 2) }}</span>
                                                @endif
                                                @if ($opportunity->application_open_date || $opportunity->application_deadline)
                                                    <span>
                                                        <strong>Apply by:</strong> 
                                                        @if ($opportunity->application_open_date)
                                                            {{ $opportunity->application_open_date->format('d M Y') }} -
                                                        @endif
                                                        {{ $opportunity->application_deadline?->format('d M Y') ?? 'Open' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="tich-flex tich-flex--col" style="align-items: end; min-width: 120px;">
                                            @if ($opportunity->isOpenForApplications())
                                                <a href="{{ route('financial-aid.apply', $opportunity->slug) }}" class="tich-btn tich-btn-primary" style="width: 100%;">Apply Now</a>
                                            @else
                                                <span class="tich-badge bg-gray-100 text-gray-600" style="width: 100%; text-align: center;">
                                                    {{ $opportunity->application_deadline && $opportunity->application_deadline < now() ? 'Closed' : 'Not Open' }}
                                                </span>
                                            @endif
                                        </div>
                                        <div style="grid-column: span 4;">
                                            <a href="{{ route('financial-aid.show', $opportunity->slug) }}" class="tich-link tich-text--sm">View details &raquo;</a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach

                            {{ $opportunities->links() }}
                        </div>
                    </div>
                @else
                    <div class="tich-card">
                        <div class="tich-card__body" style="text-align: center; padding: 3rem;">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem; color: #9ca3af;">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                            </svg>
                            <h3 class="tich-h3">No opportunities available</h3>
                            <p class="tich-text tich-text--muted tich-mt-2">There are no financial aid opportunities open for applications at the moment. Please check back later.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="tich-section tich-mt-8">
        <div class="tich-container">
            <div class="tich-grid tich-grid--2" style="gap: 2rem;">
                <article class="tich-card">
                    <div class="tich-card__body">
                        <h3 class="tich-h3 tich-mb-4" style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #16a34a;">
                                <path d="M12 21V7m0 0l-4 4m4-4l4 4M5 21h14"/>
                                <path d="M21 15a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/>
                            </svg>
                            Support Our Students
                        </h3>
                        <p class="tich-text tich-text--muted tich-mb-6">
                            Your donation directly funds scholarships, grants, and emergency financial aid for students in need. 
                            Every contribution, regardless of size, helps a student complete their education.
                        </p>
                        <a href="{{ route('donate') }}" class="tich-btn tich-btn-success tich-btn-block" style="background: #16a34a; border-color: #16a34a;">
                            Make a Donation
                        </a>
                    </div>
                </article>

                <article class="tich-card">
                    <div class="tich-card__body">
                        <h3 class="tich-h3 tich-mb-4" style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #1e40af;">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            Sponsor a Student
                        </h3>
                        <p class="tich-text tich-text--muted tich-mb-6">
                            Become a sponsor and directly support a student's education journey. 
                            Our sponsorship team will contact you to discuss options and matching with a student.
                        </p>
                        <a href="{{ route('sponsor') }}" class="tich-btn tich-btn-primary tich-btn-block">
                            Become a Sponsor
                        </a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="tich-section tich-mt-8" style="background: #f8fafc;">
        <div class="tich-container">
            <h2 class="tich-h2" style="text-align: center; margin-bottom: 2rem;">How to Apply</h2>
            <div class="tich-grid tich-grid--3" style="gap: 1.5rem;">
                <article class="tich-card">
                    <div class="tich-card__body" style="text-align: center;">
                        <div class="tich-card__icon" style="margin: 0 auto 1rem;">1</div>
                        <h3 class="tich-h4">Browse Opportunities</h3>
                        <p class="tich-text tich-text--muted">Explore available scholarships, grants, and work-study programs that match your profile.</p>
                    </div>
                </article>
                <article class="tich-card">
                    <div class="tich-card__body" style="text-align: center;">
                        <div class="tich-card__icon" style="margin: 0 auto 1rem;">2</div>
                        <h3 class="tich-h4">Prepare Documents</h3>
                        <p class="tich-text tich-text--muted">Gather required documents: academic transcripts, recommendation letters, financial statements.</p>
                    </div>
                </article>
                <article class="tich-card">
                    <div class="tich-card__body" style="text-align: center;">
                        <div class="tich-card__icon" style="margin: 0 auto 1rem;">3</div>
                        <h3 class="tich-h4">Submit Application</h3>
                        <p class="tich-text tich-text--muted">Complete the online application form and upload supporting documents before the deadline.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection