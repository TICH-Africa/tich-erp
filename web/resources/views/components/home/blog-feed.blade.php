<section class="tich-section tich-section--white" id="blog">
    <div class="tich-container">
        <div class="tich-section__intro">
            <h2 class="tich-h2">Latest from the blog</h2>
            <p class="tich-text">News, student stories, and admissions updates from across TICH campuses.</p>
            @if (!empty($usingFallback['blogPosts']))
                <p class="tich-caption">Showing default articles until blog posts are published.</p>
            @endif
        </div>

        <div class="tich-grid tich-grid--3">
            @foreach ($blogPosts as $post)
                <article class="tich-card tich-blog-card">
                    @if (!empty($post->featured_image_path))
                        <img src="{{ $post->featured_image_path }}" alt="{{ $post->title }}" class="tich-blog-card__image">
                    @else
                        <div class="tich-blog-card__placeholder" aria-hidden="true"></div>
                    @endif
                    <div class="tich-blog-card__body">
                        <p class="tich-caption">{{ $post->formatted_date ?? '' }}
                            @if (!empty($post->reading_time_minutes))
                                · {{ $post->reading_time_minutes }} min read
                            @endif
                        </p>
                        <h3 class="tich-h3 tich-mt-2">{{ $post->title }}</h3>
                        @if (!empty($post->excerpt))
                            <p class="tich-text tich-mt-2">{{ $post->excerpt }}</p>
                        @endif
                        <a href="{{ $post->url ?? '#blog' }}" class="tich-link tich-mt-4" style="display: inline-block;">Read article</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
