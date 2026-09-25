@extends('layouts.app')

@section('title', 'Blog')
@section('meta_description', config('tich-seo.pages.blog.description'))

@section('content')
    <header class="tich-course-page-header">
        <div class="tich-container">
            <h1 class="tich-course-page-header__title">Blog</h1>
            <p class="tich-course-page-header__lead">
                News, student stories, and admissions updates from across TICH campuses.
            </p>
        </div>
    </header>

    <section class="tich-section tich-section--programs" aria-labelledby="blog-heading" data-live-search>
        <div class="tich-container">
            <div class="tich-course-filters tich-mb-6">
                <div class="tich-form-group" style="margin: 0; flex: 1; max-width: 24rem;">
                    <label for="blog-search" class="tich-label">Search posts</label>
                    <input
                        type="search"
                        id="blog-search"
                        data-live-search-input
                        placeholder="Title, topic, keyword..."
                        class="tich-input"
                        autocomplete="off"
                    >
                </div>
            </div>

            <h2 id="blog-heading" class="tich-sr-only">Latest posts</h2>
            <div class="tich-course-grid">
                @forelse ($blogPosts as $post)
                    @include('blog.partials.post-card', [
                        'post' => $post,
                        'headingTag' => 'h2',
                        'excerptLimit' => 160,
                    ])
                @empty
                    <p class="tich-text">No blog posts have been published yet.</p>
                @endforelse
            </div>
            <p class="tich-text tich-mt-6" data-live-search-empty hidden>No posts match your search.</p>
        </div>
    </section>
@endsection
