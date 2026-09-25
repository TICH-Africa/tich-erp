@if ($events->isNotEmpty())
<section class="tich-section tich-section--programs" id="events">
    <div class="tich-container">
        <div class="tich-section__intro">
            <h2 class="tich-h2">Events &amp; conferences</h2>
            <p class="tich-text">Symposiums, open days, outreach drives, and institutional calendar highlights.</p>
        </div>

        <div class="tich-course-grid" data-home-reveal data-home-reveal-cols="3">
            @foreach ($events as $event)
                @include('events.partials.event-card', [
                    'event' => $event,
                    'extraClass' => 'tich-home-reveal',
                    'headingTag' => 'h3',
                    'excerptLimit' => 140,
                ])
            @endforeach
        </div>

        <div class="tich-mt-8">
            <a href="{{ route('events') }}" class="tich-btn tich-btn-primary">View all events</a>
        </div>
    </div>
</section>
@endif
