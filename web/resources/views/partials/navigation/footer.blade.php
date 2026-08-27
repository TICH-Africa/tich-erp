<footer class="tich-footer tich-footer--rich">
    <div class="tich-container">
        <div class="tich-footer__grid">
            <div class="tich-footer__brand">
                @include('partials.brand-logo')
                <p class="tich-text tich-mt-4">{{ $siteMeta['tagline'] ?? 'Community health education for Africa' }}</p>

                @if (!empty($socialLinks))
                    <div class="tich-footer__social tich-mt-4">
                        @foreach ($socialLinks as $social)
                            <a href="{{ $social['url'] }}" class="tich-footer__social-link" target="_blank" rel="noopener noreferrer" title="{{ $social['display_name'] }}">
                                {{ strtoupper(substr($social['icon_name'] ?? $social['platform'], 0, 1)) }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <nav aria-label="Site navigation">
                <h2 class="tich-h3">Site navigation</h2>
                <ul class="tich-footer__list">
                    @foreach ($footerPrimaryMenu as $item)
                        <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <nav aria-label="Quick links">
                <h2 class="tich-h3">Quick links</h2>
                <ul class="tich-footer__list">
                    @foreach ($footerQuickLinks as $item)
                        <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <div>
                <h2 class="tich-h3">Contact</h2>
                <ul class="tich-footer__list">
                    @foreach ($contactChannels as $channel)
                        <li>
                            <span class="tich-caption">{{ $channel['label'] }}</span><br>
                            @if (!empty($channel['href']) && $channel['href'] !== '#')
                                <a href="{{ $channel['href'] }}">{{ $channel['display_value'] }}</a>
                            @else
                                {{ $channel['display_value'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>

                <form method="POST" action="#" class="tich-footer__newsletter tich-mt-4" onsubmit="return false;">
                    <label for="newsletter-email" class="tich-label">Newsletter</label>
                    <div class="tich-footer__newsletter-row">
                        <input type="email" id="newsletter-email" class="tich-input" placeholder="Your email address">
                        <button type="submit" class="tich-btn tich-btn-blue">Subscribe</button>
                    </div>
                    <p class="tich-caption tich-mt-2">Stay updated on admissions, events, and research.</p>
                </form>
            </div>
        </div>

        <div class="tich-footer__bottom">
            <p>&copy; {{ date('Y') }} {{ $siteMeta['copyright'] ?? $siteMeta['institution_name'] }}</p>
            <p class="tich-caption">Built for community health and development across Africa</p>
        </div>
    </div>
</footer>
