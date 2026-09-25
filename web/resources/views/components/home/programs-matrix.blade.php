<section class="tich-section tich-section--programs" id="programs">
    <div class="tich-container">
        <div class="tich-section__intro">
            <h2 class="tich-h2">Academic programmes</h2>
            <p class="tich-text">Pinned priority programmes for community health practice, development, and health technology.</p>
            @if (!empty($usingFallback['programs']))
                <p class="tich-caption">Programme highlights will appear here once programmes are published.</p>
            @endif
        </div>

        @if ($programs->isNotEmpty())
        <div class="tich-course-grid" data-home-reveal data-home-reveal-cols="3">
            @foreach ($programs as $program)
                @include('programs.partials.course-card', [
                    'program' => $program,
                    'extraClass' => 'tich-home-reveal',
                    'excerptLimit' => 180,
                    'entryLimit' => 80,
                ])
            @endforeach
        </div>

        <div class="tich-text-center tich-mt-8">
            <a href="{{ route('programs.index') }}" class="tich-btn tich-btn-blue">View all academic programmes</a>
        </div>
        @else
            <p class="tich-text">Academic programmes will be listed here once published.</p>
        @endif
    </div>
</section>
