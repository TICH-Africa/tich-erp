@extends('layouts.app')

@section('title', 'Home')

@section('content')
    @include('components.home.carousel', ['carousel' => $carousel])

    @include('components.home.programs-matrix', ['programs' => $programs, 'usingFallback' => $usingFallback])

    @include('components.home.research-snippet', ['research' => $research])

    @include('components.home.events-feed', ['events' => $events, 'usingFallback' => $usingFallback])

    @include('components.home.blog-feed', ['blogPosts' => $blogPosts, 'usingFallback' => $usingFallback])

    <section class="tich-section" id="about">
        <div class="tich-container">
            <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
                <div>
                    <h2 class="tich-h2">Built for community health education</h2>
                    <p class="tich-text tich-mt-4">
                        The Tropical Institute of Community Health and Development in Africa supports multi-campus operations - from the main campus to community colleges and sub-county hubs - with programmes tailored to TVET, nursing, and public health practice.
                    </p>
                    <div class="tich-flex-wrap tich-mt-6">
                        @auth
                            <a href="{{ route('dashboard') }}" class="tich-btn tich-btn-primary">Go to dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="tich-btn tich-btn-blue">Sign in to portal</a>
                            <a href="{{ route('apply.index') }}" class="tich-btn tich-btn-primary">Start application</a>
                        @endauth
                    </div>
                </div>
                <div class="tich-grid tich-grid--2">
                    @foreach ([
                        ['label' => 'Campuses', 'value' => 'Multi-site'],
                        ['label' => 'Students', 'value' => 'Admissions to alumni'],
                        ['label' => 'Security', 'value' => 'RBAC + MFA'],
                        ['label' => 'Portal', 'value' => 'Dynamic CMS'],
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
