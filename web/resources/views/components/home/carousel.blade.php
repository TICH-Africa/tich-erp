@if ($carousel->isNotEmpty())
@php
    $slideCount = $carousel->count();
@endphp
<section
    class="tich-hero-carousel"
    id="home-hero"
    data-carousel
    aria-roledescription="carousel"
    aria-label="Featured highlights"
>
    <h1 class="tich-sr-only">{{ $siteMeta['institution_name'] ?? 'TICH in Africa' }} - {{ $siteMeta['tagline'] ?? 'Community health education for Africa' }}</h1>

    <div class="tich-hero-carousel__bg" aria-hidden="true">
        <div class="tich-hero-carousel__viewport">
            <div class="tich-hero-carousel__track" data-carousel-track>
                @foreach ($carousel as $index => $slide)
                    <div class="tich-hero-carousel__slide" data-carousel-slide @if($index !== 0) aria-hidden="true" @endif>
                        <div class="tich-hero-carousel__media">
                            @if (! empty($slide->image_path))
                                <img src="{{ $slide->image_path }}" alt="" class="tich-hero-carousel__image" data-lazy="eager" @if($index === 0) fetchpriority="high" @endif>
                            @elseif (! empty($slide->video_url ?? null))
                                <video muted loop playsinline class="tich-hero-carousel__video" aria-label="{{ $slide->title }}">
                                    <source src="{{ $slide->video_url }}" type="video/mp4">
                                </video>
                            @else
                                <div class="tich-hero-carousel__placeholder tich-hero-carousel__placeholder--{{ ($index % 3) + 1 }}"></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="tich-hero-carousel__overlay"></div>
        <div class="tich-hero-carousel__grid"></div>
    </div>

    <div class="tich-hero-carousel__bracket tich-hero-carousel__bracket--tl" aria-hidden="true"></div>
    <div class="tich-hero-carousel__bracket tich-hero-carousel__bracket--br" aria-hidden="true"></div>

    <div class="tich-hero-carousel__body">
        @foreach ($carousel as $index => $slide)
            <div
                class="tich-hero-carousel__panel{{ $index === 0 ? ' is-active' : '' }}"
                data-carousel-panel
                @if($index !== 0) aria-hidden="true" @endif
            >
                <h2 class="tich-hero-carousel__title">{{ $slide->title }}</h2>
                @if (! empty($slide->subtitle))
                    <p class="tich-hero-carousel__lead">{{ $slide->subtitle }}</p>
                @endif
                @if (! empty($slide->view_url) || (! empty($slide->cta_label) && ! empty($slide->cta_url)))
                    <div class="tich-hero-carousel__actions">
                        @if (! empty($slide->cta_label) && ! empty($slide->cta_url))
                            <a href="{{ $slide->cta_url }}" class="tich-btn tich-btn-primary tich-hero-carousel__btn"><span class="tich-hero-carousel__btn-label">{{ $slide->cta_label }}</span></a>
                        @endif
                        @if (! empty($slide->view_url))
                            <a href="{{ $slide->view_url }}" class="tich-btn tich-btn-ghost tich-hero-carousel__btn tich-hero-carousel__btn--ghost"><span class="tich-hero-carousel__btn-label">View Program</span></a>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if ($slideCount > 1)
        <div class="tich-hero-carousel__indicators" data-carousel-dots role="tablist" aria-label="Slide navigation">
            @foreach ($carousel as $index => $slide)
                <button
                    type="button"
                    class="tich-hero-carousel__indicator{{ $index === 0 ? ' is-active' : '' }}"
                    data-carousel-dot="{{ $index }}"
                    aria-label="Go to slide {{ $index + 1 }}: {{ $slide->title }}"
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                ></button>
            @endforeach
        </div>

        <div class="tich-hero-carousel__controls">
            <button type="button" class="tich-hero-carousel__arrow tich-hero-carousel__arrow--prev" data-carousel-prev aria-label="Previous slide">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <button type="button" class="tich-hero-carousel__arrow tich-hero-carousel__arrow--next" data-carousel-next aria-label="Next slide">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </div>
    @endif

    @if (! empty($tickerMessage))
        <div class="tich-hero-ticker" aria-label="Announcements">
            <div class="tich-hero-ticker__inner">
                <span class="tich-hero-ticker__item">{{ $tickerMessage }}</span>
                <span class="tich-hero-ticker__item" aria-hidden="true">{{ $tickerMessage }}</span>
            </div>
        </div>
    @endif
</section>
@else
    <header class="tich-section tich-section--white">
        <div class="tich-container">
            <h1 class="tich-h1">{{ $siteMeta['institution_name'] ?? 'TICH in Africa' }}</h1>
            @if (! empty($siteMeta['tagline']))
                <p class="tich-text tich-mt-2">{{ $siteMeta['tagline'] }}</p>
            @endif
        </div>
    </header>
@endif
