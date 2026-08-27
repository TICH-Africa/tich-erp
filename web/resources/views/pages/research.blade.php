@extends('layouts.app')

@section('title', 'Research')
@section('meta_description', config('tich-seo.pages.research.description'))

@section('content')
    <section class="tich-section" aria-labelledby="research-heading">
        <div class="tich-container">
            <header class="tich-section__intro tich-mb-8">
                <h1 id="research-heading" class="tich-h1">Research</h1>
                <p class="tich-text tich-mt-2">A hub of research excellence linking classrooms to communities and policy.</p>
            </header>

            <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
                <div>
                    <h2 class="tich-h2">Research excellence</h2>
                    <p class="tich-text tich-mt-4">
                        From health and education to technology, environment, and social development, our multidisciplinary research teams work at the intersection of theory and practice—translating research into action and policy.
                    </p>
                    <p class="tich-text tich-mt-4">
                        Through partnership with local and global organizations, we address real-world challenges by generating knowledge that empowers individuals and strengthens communities.
                    </p>

                    <div class="tich-mt-6">
                        <h3 class="tich-h3">Our research areas</h3>
                        <ul class="tich-mt-4">
                            <li>Community Health Systems Strengthening</li>
                            <li>Maternal, Neonatal, and Child Health (MNCH)</li>
                            <li>Health Equity and Social Determinants of Health</li>
                            <li>Digital Health and Innovation</li>
                            <li>Health Policy and Implementation Science</li>
                            <li>Climate Change and Health</li>
                        </ul>
                    </div>
                </div>

                <div class="tich-card">
                    <h3 class="tich-h3">Be a research partner</h3>
                    <form class="tich-mt-4" onsubmit="event.preventDefault(); alert('Thank you for your interest. Our research team will contact you.');">
                        <div class="tich-grid tich-grid--2">
                            <div>
                                <label class="tich-label">First name</label>
                                <input type="text" class="tich-input" required>
                            </div>
                            <div>
                                <label class="tich-label">Last name</label>
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
                            <label class="tich-label">Interested research area</label>
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
                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Submit partnership inquiry</button>
                    </form>
                </div>
            </div>

            @if ($projects->isNotEmpty())
                <div class="tich-mt-10">
                    <h2 class="tich-h2">Research projects</h2>
                    <div class="tich-grid tich-grid--3 tich-mt-6">
                        @foreach ($projects as $project)
                            <article class="tich-card">
                                @if ($project->cover_image_path)
                                    <img
                                        src="{{ str_starts_with($project->cover_image_path, 'http') ? $project->cover_image_path : asset(ltrim($project->cover_image_path, '/')) }}"
                                        alt="{{ $project->title }}"
                                        class="tich-blog-card__image"
                                        style="margin-bottom: 1rem;"
                                    >
                                @endif
                                <p class="tich-caption">{{ ucfirst($project->status ?? 'ongoing') }}@if ($project->is_featured) · Featured @endif</p>
                                <h3 class="tich-h3 tich-mt-2">{{ $project->title }}</h3>
                                @if ($project->summary)
                                    <p class="tich-text tich-mt-2">{{ \Illuminate\Support\Str::limit($project->summary, 180) }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @elseif ($featured)
                <div class="tich-mt-10 tich-card">
                    <p class="tich-caption">Featured {{ $featured->status ?? 'ongoing' }} project</p>
                    <h2 class="tich-h3 tich-mt-2">{{ $featured->title }}</h2>
                    <p class="tich-text tich-mt-4">{{ $featured->summary }}</p>
                </div>
            @endif
        </div>
    </section>
@endsection
