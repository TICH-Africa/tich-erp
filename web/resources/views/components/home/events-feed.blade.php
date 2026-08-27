<section class="tich-section" id="events">
    <div class="tich-container">
        <div class="tich-section__intro">
            <h2 class="tich-h2">Events &amp; conferences</h2>
            <p class="tich-text">Symposiums, open days, outreach drives, and institutional calendar highlights.</p>
            @if (!empty($usingFallback['events']))
                <p class="tich-caption">Showing sample events until the events registry is populated.</p>
            @endif
        </div>

        <div class="tich-grid tich-grid--3" data-home-reveal data-home-reveal-cols="3">
            @foreach ($events as $event)
                <article class="tich-card tich-event-card tich-home-reveal">
                    <div class="tich-event-card__media">
                        @if (!empty($event->cover_image_path))
                            <img src="{{ $event->cover_image_path }}" alt="{{ $event->title }}" class="tich-event-card__image">
                        @else
                            <div class="tich-event-card__placeholder" aria-hidden="true"></div>
                        @endif
                    </div>
                    <div class="tich-event-card__body">
                        <p class="tich-caption">{{ strtoupper(str_replace('_', ' ', $event->event_type)) }}</p>
                        <p class="tich-event-card__date tich-mt-2">{{ $event->formatted_date ?? '' }}</p>
                        <h3 class="tich-h3 tich-mt-2">{{ $event->title }}</h3>
                        @if (!empty($event->subtitle))
                            <p class="tich-text tich-mt-2">{{ $event->subtitle }}</p>
                        @endif
                        @if (!empty($event->venue))
                            <p class="tich-caption tich-mt-4">{{ $event->venue }}</p>
                        @endif
                        <div class="tich-flex-wrap tich-mt-4" style="gap: 0.5rem;">
                            @if (!empty($event->registration_url_or_form))
                                <a href="{{ $event->registration_url_or_form }}" class="tich-btn tich-btn-secondary">Register</a>
                            @endif
                            <a href="{{ $event->url ?? route('events') }}" class="tich-btn tich-btn-primary">View event</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="tich-mt-8">
            <a href="{{ route('events') }}" class="tich-btn tich-btn-primary">View all events</a>
        </div>
    </div>
</section>
