<section class="tich-section" id="events">
    <div class="tich-container">
        <div class="tich-section__intro">
            <h2 class="tich-h2">Events &amp; conferences</h2>
            <p class="tich-text">Symposiums, open days, outreach drives, and institutional calendar highlights.</p>
            @if (!empty($usingFallback['events']))
                <p class="tich-caption">Showing sample events until the events registry is populated.</p>
            @endif
        </div>

        <div class="tich-grid tich-grid--3">
            @foreach ($events as $event)
                <article class="tich-card tich-event-card">
                    @if (!empty($event->cover_image_path))
                        <img src="{{ $event->cover_image_path }}" alt="{{ $event->title }}" class="tich-blog-card__image" style="margin-bottom: 1rem;">
                    @endif
                    <p class="tich-caption">{{ strtoupper(str_replace('_', ' ', $event->event_type)) }}</p>
                    <p class="tich-event-card__date tich-mt-2">{{ $event->formatted_date ?? '' }}</p>
                    <h3 class="tich-h3 tich-mt-2">{{ $event->title }}</h3>
                    @if (!empty($event->subtitle))
                        <p class="tich-text tich-mt-2">{{ $event->subtitle }}</p>
                    @endif
                    @if (!empty($event->venue))
                        <p class="tich-caption tich-mt-4">{{ $event->venue }}</p>
                    @endif
                    @if (!empty($event->registration_url_or_form))
                        <a href="{{ $event->registration_url_or_form }}" class="tich-btn tich-btn-secondary tich-mt-4">Register</a>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="tich-mt-8">
            <a href="{{ route('events') }}" class="tich-btn tich-btn-primary">View all events</a>
        </div>
    </div>
</section>
