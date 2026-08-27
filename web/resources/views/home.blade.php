@extends('layouts.app')

@section('title', 'Home')
@section('meta_description', config('tich-seo.pages.home.description'))

@section('content')
    @include('components.home.carousel', ['carousel' => $carousel, 'tickerMessage' => $tickerMessage])

    @include('components.home.programs-matrix', ['programs' => $programs, 'usingFallback' => $usingFallback])

    @include('components.home.research-snippet', ['research' => $research])

    @include('components.home.events-feed', ['events' => $events, 'usingFallback' => $usingFallback])

    @include('components.home.blog-feed', ['blogPosts' => $blogPosts, 'usingFallback' => $usingFallback])

    <section class="tich-section" id="about" aria-labelledby="home-about-heading">
        <div class="tich-container">
            <div class="tich-section__intro">
                <h2 id="home-about-heading" class="tich-h2">About Us</h2>
                <p class="tich-text">Learn about our vision, mission, and history.</p>
            </div>
            <div class="tich-mt-6">
                <a href="{{ route('about') }}" class="tich-btn tich-btn-primary">Read About Us</a>
            </div>
        </div>
    </section>
@endsection
