<section class="tich-section" id="programs">
    <div class="tich-container">
        <div class="tich-section__intro">
            <h2 class="tich-h2">Academic programmes</h2>
            <p class="tich-text">Pinned priority programmes for community health practice, development, and health technology.</p>
            @if (!empty($usingFallback['programs']))
                <p class="tich-caption">Showing default programme highlights until CMS content is published.</p>
            @endif
        </div>

        <div class="tich-grid tich-grid--3">
            @foreach ($programs as $program)
                <article class="tich-card tich-program-card">
                    <p class="tich-caption">{{ strtoupper($program->program_code) }} · {{ strtoupper(str_replace('_', ' ', $program->program_type)) }}</p>
                    <h3 class="tich-h3 tich-mt-2">{{ $program->program_name }}</h3>
                    @if (!empty($program->homepage_tagline))
                        <p class="tich-text tich-mt-2">{{ $program->homepage_tagline }}</p>
                    @endif

                    <ul class="tich-program-card__meta tich-mt-4">
                        @if (!empty($program->duration_months))
                            <li><span class="tich-caption">Duration</span> {{ $program->duration_months }} months</li>
                        @endif
                        @if (!empty($program->regulatory_body))
                            <li><span class="tich-caption">Accreditation</span> {{ $program->regulatory_body }}</li>
                        @endif
                        @if (!empty($program->entry_requirements))
                            <li><span class="tich-caption">Entry</span> {{ \Illuminate\Support\Str::limit($program->entry_requirements, 80) }}</li>
                        @endif
                        @if (!empty($program->fee_display))
                            <li><span class="tich-caption">Fees</span> {{ $program->fee_display }}</li>
                        @endif
                    </ul>

                    <a href="{{ $program->apply_url ?? url('/apply') }}" class="tich-btn tich-btn-primary tich-mt-4">Apply now</a>
                </article>
            @endforeach
        </div>

        <div class="tich-text-center tich-mt-8">
            <a href="#programs" class="tich-btn tich-btn-blue">View all academic programmes</a>
        </div>
    </div>
</section>
