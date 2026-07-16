@if ($carousel->isNotEmpty())
<section class="tich-carousel" data-carousel aria-label="Featured highlights">
    <div class="tich-carousel__track" data-carousel-track>
        @foreach ($carousel as $index => $slide)
            <article class="tich-carousel__slide {{ $index === 0 ? 'is-active' : '' }}" data-carousel-slide>
                <div class="tich-carousel__media">
                    @if (!empty($slide->image_path))
                        <img src="{{ $slide->image_path }}" alt="{{ $slide->title }}" class="tich-carousel__image">
                    @else
                        <div class="tich-carousel__placeholder" aria-hidden="true">
                            <span>{{ $index + 1 }}</span>
                        </div>
                    @endif
                </div>
                <div class="tich-carousel__content tich-container">
                    <p class="tich-hero__badge">TICH in Africa</p>
                    <h1 class="tich-h1 tich-carousel__title">{{ $slide->title }}</h1>
                    @if (!empty($slide->subtitle))
                        <p class="tich-hero__lead">{{ $slide->subtitle }}</p>
                    @endif
                    @if (!empty($slide->cta_label) && !empty($slide->cta_url))
                        <a href="{{ $slide->cta_url }}" class="tich-btn tich-btn-primary tich-mt-4">{{ $slide->cta_label }}</a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    @if ($carousel->count() > 1)
        <div class="tich-carousel__controls tich-container">
            <button type="button" class="tich-carousel__btn" data-carousel-prev aria-label="Previous slide">&lsaquo;</button>
            <div class="tich-carousel__dots" data-carousel-dots>
                @foreach ($carousel as $index => $slide)
                    <button type="button" class="tich-carousel__dot {{ $index === 0 ? 'is-active' : '' }}" data-carousel-dot="{{ $index }}" aria-label="Go to slide {{ $index + 1 }}"></button>
                @endforeach
            </div>
            <button type="button" class="tich-carousel__btn" data-carousel-next aria-label="Next slide">&rsaquo;</button>
        </div>
    @endif
</section>
@endif
