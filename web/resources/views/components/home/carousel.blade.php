@if ($carousel->isNotEmpty())
<section class="tich-hero-carousel" id="home-hero" data-carousel aria-roledescription="carousel" aria-label="Featured highlights">
    @foreach ($carousel as $index => $slide)
        <article class="tich-hero-carousel__slide {{ $index === 0 ? 'is-active' : '' }}" data-carousel-slide>
            <div class="tich-hero-carousel__bg" aria-hidden="true">
                @if (!empty($slide->image_path))
                    <img src="{{ $slide->image_path }}" alt="" class="tich-hero-carousel__image">
                    <div class="tich-hero-carousel__overlay"></div>
                @else
                    <div class="tich-hero-carousel__placeholder tich-hero-carousel__placeholder--{{ ($index % 3) + 1 }}"></div>
                @endif
            </div>

            <div class="tich-container tich-hero-carousel__content">
                <div class="tich-hero-carousel__inner {{ $index === 0 ? 'is-visible' : '' }}" data-carousel-content>
                    <h1 class="tich-hero-carousel__title">{{ $slide->title }}</h1>
                    @if (!empty($slide->subtitle))
                        <p class="tich-hero-carousel__lead">{{ $slide->subtitle }}</p>
                    @endif
                    @if (!empty($slide->view_url) || (!empty($slide->cta_label) && !empty($slide->cta_url)))
                        <div class="tich-hero-carousel__actions">
                            @if (!empty($slide->view_url))
                                <a href="{{ $slide->view_url }}" class="tich-btn tich-btn-secondary">View Program</a>
                            @endif
                            @if (!empty($slide->cta_label) && !empty($slide->cta_url))
                                <a href="{{ $slide->cta_url }}" class="tich-btn tich-btn-primary">{{ $slide->cta_label }}</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </article>
    @endforeach

    @if ($carousel->count() > 1)
        <button type="button" class="tich-hero-carousel__arrow tich-hero-carousel__arrow--prev" data-carousel-prev aria-label="Previous slide">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" class="tich-hero-carousel__arrow tich-hero-carousel__arrow--next" data-carousel-next aria-label="Next slide">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>

        <div class="tich-hero-carousel__indicators" data-carousel-dots aria-label="Slide navigation">
            @foreach ($carousel as $index => $slide)
                <button
                    type="button"
                    class="tich-hero-carousel__indicator {{ $index === 0 ? 'is-active' : '' }}"
                    data-carousel-dot="{{ $index }}"
                    aria-label="Go to slide {{ $index + 1 }}"
                ></button>
            @endforeach
        </div>
    @endif

    @if (!empty($tickerMessage))
        <div class="tich-hero-ticker" aria-live="polite" aria-label="Announcements">
            <div class="tich-hero-ticker__track">
                <span class="tich-hero-ticker__item">{{ $tickerMessage }}</span>
                <span class="tich-hero-ticker__item">{{ $tickerMessage }}</span>
                <span class="tich-hero-ticker__item">{{ $tickerMessage }}</span>
            </div>
        </div>
    @endif
</section>
@endif
