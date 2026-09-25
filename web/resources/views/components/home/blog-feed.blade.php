<section class="tich-section tich-section--programs" id="blog">
    <div class="tich-container">
        <div class="tich-section__intro">
            <h2 class="tich-h2">Latest from the blog</h2>
            <p class="tich-text">News, student stories, and admissions updates from across TICH campuses.</p>
            @if (!empty($usingFallback['blogPosts']))
                <p class="tich-caption">Showing default articles until blog posts are published.</p>
            @endif
        </div>

        <div class="tich-course-grid" data-home-reveal data-home-reveal-cols="3">
            @foreach ($blogPosts as $post)
                @include('blog.partials.post-card', [
                    'post' => $post,
                    'extraClass' => 'tich-home-reveal',
                    'headingTag' => 'h3',
                    'excerptLimit' => 140,
                ])
            @endforeach
        </div>

        <div class="tich-mt-8">
            <a href="{{ route('blog') }}" class="tich-btn tich-btn-primary">View all posts</a>
        </div>
    </div>
</section>
