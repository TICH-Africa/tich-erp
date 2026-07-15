@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <section class="tich-hero">
        <div class="tich-container">
            <p class="tich-hero__badge">Enterprise resource planning platform</p>
            <h1 class="tich-h1" style="font-size: 3rem;">TICH ERP</h1>
            <p class="tich-hero__lead tich-mt-4">
                A unified system for admissions, academics, finance, and human resources across the Tropical Institute of Community Health and Development in Africa.
            </p>

            <div class="tich-flex-wrap tich-mt-6">
                @auth
                    <span class="tich-btn tich-btn-secondary">
                        Signed in as {{ auth()->user()->username }}
                    </span>
                @else
                    <a href="{{ route('login') }}" class="tich-btn tich-btn-blue">Sign in to portal</a>
                    <a href="{{ route('register') }}" class="tich-btn tich-btn-primary">Create account</a>
                @endauth
            </div>
        </div>
    </section>

    <section class="tich-section">
        <div class="tich-container">
            <div class="tich-section__intro">
                <h2 class="tich-h2">Core modules</h2>
                <p class="tich-text">Everything your institution needs, connected in one platform.</p>
            </div>

            <div class="tich-grid tich-grid--4">
                <div class="tich-card">
                    <div class="tich-card__icon tich-card__icon--green tich-icon">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </div>
                    <h3 class="tich-h3">Administration</h3>
                    <p class="tich-text tich-mt-2">Admissions, student records, SACCO, and compliance.</p>
                    <p class="tich-caption tich-mt-4">Coming soon</p>
                </div>

                <div class="tich-card">
                    <div class="tich-card__icon tich-icon">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                        </svg>
                    </div>
                    <h3 class="tich-h3">Academics</h3>
                    <p class="tich-text tich-mt-2">Curriculum, attendance, assessments, and examinations.</p>
                    <p class="tich-caption tich-mt-4">Coming soon</p>
                </div>

                <div class="tich-card">
                    <div class="tich-card__icon tich-icon">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                    <h3 class="tich-h3">Finance & fees</h3>
                    <p class="tich-text tich-mt-2">Student accounts, invoicing, procurement, and payroll.</p>
                    <p class="tich-caption tich-mt-4">Coming soon</p>
                </div>

                <div class="tich-card">
                    <div class="tich-card__icon tich-card__icon--grey tich-icon">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </div>
                    <h3 class="tich-h3">Human resources</h3>
                    <p class="tich-text tich-mt-2">Staff lifecycle, leave, recruitment, and performance.</p>
                    <p class="tich-caption tich-mt-4">Coming soon</p>
                </div>
            </div>
        </div>
    </section>

    <section class="tich-section tich-section--white">
        <div class="tich-container">
            <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
                <div>
                    <h2 class="tich-h2">Built for community health education</h2>
                    <p class="tich-text tich-mt-4">
                        TICH ERP supports multi-campus operations — from the main campus to community colleges and sub-county hubs — with workflows tailored to TVET, nursing, and public health programmes.
                    </p>
                </div>
                <div class="tich-grid tich-grid--2">
                    @foreach ([
                        ['label' => 'Campuses', 'value' => 'Multi-site'],
                        ['label' => 'Students', 'value' => 'Admissions to alumni'],
                        ['label' => 'Security', 'value' => 'RBAC + MFA'],
                        ['label' => 'Status', 'value' => 'In development'],
                    ] as $stat)
                        <div class="tich-stat">
                            <p class="tich-stat__label">{{ $stat['label'] }}</p>
                            <p class="tich-stat__value">{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
