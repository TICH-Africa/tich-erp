@if ($research)
    @php
        // The study's own colour paints this block, so that colour and not the visitor's theme has
        // to pick the ink: --tinted carries pale words on a dark wash, --pale keeps dark words on a
        // light wash even when the site is in dark mode.
        $wash = !empty($research->page_color) ? trim((string) $research->page_color) : null;
        $washInk = $wash ? (!empty($research->page_dark) ? 'tinted' : 'pale') : null;
    @endphp
<section class="tich-section tich-section--white tich-research-hub{{ $washInk ? ' tich-research-hub--'.$washInk : '' }}" id="research" @if ($wash) style="--study-page-bg: {{ $wash }};" @endif>
    <div class="tich-container">
        <div class="tich-grid tich-grid--2" style="align-items: center; gap: 2rem;" data-home-reveal data-home-reveal-cols="2">
            <div class="tich-home-reveal">
                <h2 class="tich-h2">Research hub</h2>
                <p class="tich-caption tich-mt-2">Featured {{ $research->status ?? 'ongoing' }} project</p>
                <h3 class="tich-h3 tich-mt-4">{{ $research->title }}</h3>
                <p class="tich-text tich-mt-4">{{ $research->summary }}</p>
                <div class="tich-flex-wrap tich-mt-6">
                    <a href="{{ route('research') }}" class="tich-btn tich-btn-blue">Read more</a>
                    <a href="{{ route('research') }}" class="tich-btn tich-btn-secondary">View all research</a>
                </div>
            </div>
            <div class="tich-research-card tich-home-reveal">
                @if (!empty($research->cover_image_path))
                    <img src="{{ $research->cover_image_path }}" alt="{{ $research->title }}" class="tich-research-card__image">
                @else
                    <div class="tich-research-card__placeholder">
                        <p class="tich-h3">Community health research</p>
                        <p class="tich-text tich-mt-2">Linking classrooms to county health systems and local partners.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endif
