@php
    $openGeneralModal = $errors->any() && old('_form') === 'edit_general';
    $brandInitial = strtoupper(substr($siteMeta['brand_name'] ?? $siteMeta['short_name'] ?? 'T', 0, 1));
    $website = trim((string) ($siteMeta['website'] ?? ''));
    $websiteHref = $website !== ''
        ? (str_starts_with($website, 'http://') || str_starts_with($website, 'https://') ? $website : 'https://'.$website)
        : null;
    $hasLogo = ! empty($siteMeta['logo_url']);
@endphp

@include('site-settings.partials.general-panel-styles')

<section class="tich-site-identity-hero tich-mb-8">
    <div class="tich-site-identity-hero__main">
        <p class="tich-site-identity-hero__eyebrow">Public site identity</p>
        <h2 class="tich-site-identity-hero__title">{{ $siteMeta['institution_name'] }}</h2>
        @if ($siteMeta['tagline'])
            <p class="tich-site-identity-hero__tagline">{{ $siteMeta['tagline'] }}</p>
        @else
            <p class="tich-site-identity-hero__tagline">Configure how your institution appears on the public website, navigation bar, and footer.</p>
        @endif

        <div class="tich-site-identity-hero__meta">
            <span class="tich-status-badge {{ $hasLogo ? 'is-success' : 'is-pending' }}">
                {{ $hasLogo ? 'Custom logo active' : 'Letter mark fallback' }}
            </span>
            @if ($websiteHref)
                <a href="{{ $websiteHref }}" class="tich-status-badge is-info" target="_blank" rel="noopener noreferrer">Website linked</a>
            @else
                <span class="tich-status-badge">No website set</span>
            @endif
            <span class="tich-status-badge is-info">{{ $siteMeta['short_name'] }}</span>
        </div>
    </div>

    <div class="tich-site-identity-hero__aside">
        <div class="tich-site-identity-logo-frame">
            @if ($hasLogo)
                <img src="{{ $siteMeta['logo_url'] }}" alt="{{ $siteMeta['brand_name'] }} logo">
            @else
                <div class="tich-site-identity-logo-frame__fallback">
                    <span class="tich-site-identity-logo-frame__mark">{{ $brandInitial }}</span>
                    <span class="tich-caption">Upload a logo to replace the letter mark</span>
                </div>
            @endif
        </div>

        @can('site_settings.manage')
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="general-edit-modal" style="width:100%;">
                Edit identity settings
            </button>
        @endcan
    </div>
</section>

<div class="tich-grid tich-grid--4 tich-dept-stats tich-mb-8">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Short name</p>
        <p class="tich-stat__value" style="font-size:1.05rem;">{{ $siteMeta['short_name'] }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Navbar brand</p>
        <p class="tich-stat__value" style="font-size:1.05rem;">{{ $siteMeta['brand_name'] }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Public website</p>
        <p class="tich-stat__value" style="font-size:1.05rem;">
            @if ($websiteHref)
                <a href="{{ $websiteHref }}" class="tich-site-identity-stat-link" target="_blank" rel="noopener noreferrer">{{ $website }}</a>
            @else
                -
            @endif
        </p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Logo asset</p>
        <p class="tich-stat__value" style="font-size:1.05rem;">{{ $hasLogo ? 'Uploaded' : 'Not set' }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Ticker message</p>
        <p class="tich-stat__value" style="font-size:1.05rem; {{ ! $siteMeta['ticker_message'] ? 'color: var(--tich-neutral-muted);' : '' }}">
            {{ $siteMeta['ticker_message'] ?: 'Not set' }}
        </p>
    </article>
</div>

<div class="tich-grid tich-grid--2 tich-mb-8" style="align-items: stretch; gap: 1.5rem;">
    <article class="tich-card">
        <div class="tich-site-identity-card-head">
            <div>
                <h2 class="tich-h3">Navbar preview</h2>
                <p class="tich-caption">How visitors see your brand in the site header.</p>
            </div>
            <a href="{{ route('home') }}" class="tich-link" target="_blank" rel="noopener noreferrer">Open site</a>
        </div>

        <div class="tich-site-identity-preview">
            <div class="tich-site-identity-preview__label">Header mockup</div>
            <div class="tich-site-identity-preview__bar">
                @include('partials.brand-logo', ['siteMeta' => $siteMeta, 'static' => true])
                <ul class="tich-site-identity-preview__nav" aria-hidden="true">
                    <li class="is-active">Home</li>
                    <li>Programs</li>
                    <li>Admissions</li>
                    <li>Contact</li>
                </ul>
            </div>
            <div class="tich-site-identity-preview__footer">
                <p>
                    <strong>{{ $siteMeta['brand_name'] }}</strong>
                    @if ($siteMeta['brand_tagline'])
                        · {{ $siteMeta['brand_tagline'] }}
                    @endif
                </p>
            </div>
        </div>
    </article>

    <article class="tich-card">
        <div class="tich-site-identity-card-head">
            <div>
                <h2 class="tich-h3">Logo asset</h2>
                <p class="tich-caption">Used across the public site navigation when uploaded.</p>
            </div>
            @if ($hasLogo)
                <span class="tich-status-badge is-success">Active</span>
            @endif
        </div>

        <div class="tich-site-identity-asset">
            <div class="tich-site-identity-asset__stage">
                @if ($hasLogo)
                    <img src="{{ $siteMeta['logo_url'] }}" alt="{{ $siteMeta['brand_name'] }} logo preview">
                @else
                    <div class="tich-site-identity-logo-frame__fallback">
                        <span class="tich-site-identity-logo-frame__mark">{{ $brandInitial }}</span>
                        <span class="tich-caption">No logo uploaded yet</span>
                    </div>
                @endif
            </div>

            <div class="tich-site-identity-asset__meta">
                @if ($hasLogo && ! empty($siteMeta['logo_path']))
                    <p class="tich-caption"><strong>Stored path:</strong> {{ $siteMeta['logo_path'] }}</p>
                @endif
                <p class="tich-caption">Recommended: transparent PNG or SVG on a light background. Max 2 MB.</p>
            </div>
        </div>
    </article>
</div>

<div class="tich-grid tich-grid--2" style="align-items: start; gap: 1.5rem;">
    <article class="tich-card">
        <h2 class="tich-h3">Branding details</h2>
        <p class="tich-caption tich-mt-2">Names and taglines shown in the navigation and page chrome.</p>
        <div class="tich-kv-grid tich-mt-4">
            <div>
                <span class="tich-kv-grid__label">Institution name</span>
                <span class="tich-kv-grid__value">{{ $siteMeta['institution_name'] }}</span>
            </div>
            <div>
                <span class="tich-kv-grid__label">Short name</span>
                <span class="tich-kv-grid__value">{{ $siteMeta['short_name'] }}</span>
            </div>
            <div>
                <span class="tich-kv-grid__label">Navbar brand name</span>
                <span class="tich-kv-grid__value">{{ $siteMeta['brand_name'] }}</span>
            </div>
            <div>
                <span class="tich-kv-grid__label">Navbar tagline</span>
                <span class="tich-kv-grid__value">{{ $siteMeta['brand_tagline'] ?: '-' }}</span>
            </div>
            <div style="grid-column: 1 / -1;">
                <span class="tich-kv-grid__label">Site tagline</span>
                <span class="tich-kv-grid__value tich-kv-grid__value--block">{{ $siteMeta['tagline'] ?: '-' }}</span>
            </div>
            <div style="grid-column: 1 / -1;">
                <span class="tich-kv-grid__label">Default SEO description</span>
                <span class="tich-kv-grid__value tich-kv-grid__value--block">{{ $siteMeta['meta_description'] ?: '-' }}</span>
            </div>
        </div>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Site &amp; legal</h2>
        <p class="tich-caption tich-mt-2">Footer, links, and legal text on the public website.</p>
        <div class="tich-kv-grid tich-mt-4">
            <div style="grid-column: 1 / -1;">
                <span class="tich-kv-grid__label">Website URL</span>
                <span class="tich-kv-grid__value">
                    @if ($websiteHref)
                        <a href="{{ $websiteHref }}" class="tich-link" target="_blank" rel="noopener noreferrer">{{ $website }}</a>
                    @else
                        -
                    @endif
                </span>
            </div>
            <div style="grid-column: 1 / -1;">
                <span class="tich-kv-grid__label">Copyright line</span>
                <span class="tich-kv-grid__value tich-kv-grid__value--block">{{ $siteMeta['copyright'] ?: '-' }}</span>
            </div>
        </div>

        @if ($siteMeta['copyright'])
            <div class="tich-mt-6" style="padding: 1rem 1.15rem; border-radius: var(--radius-sm); background: var(--tich-surface-muted); border: 1px solid var(--tich-border);">
                <p class="tich-caption" style="margin:0 0 0.35rem;">Footer preview</p>
                <p class="tich-text" style="margin:0; font-size:0.875rem; color: var(--tich-neutral-muted);">{{ $siteMeta['copyright'] }}</p>
            </div>
        @endif
    </article>
</div>

@can('site_settings.manage')
    <div id="general-edit-modal" class="tich-modal{{ $openGeneralModal ? ' is-open' : '' }}" aria-hidden="{{ $openGeneralModal ? 'false' : 'true' }}" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="general-edit-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Edit site identity</h2>
                <button type="button" class="tich-modal__close" data-close-modal="general-edit-modal" aria-label="Close">&times;</button>
            </header>
            <form method="POST" action="{{ route('site-settings.general.update') }}" enctype="multipart/form-data" class="tich-modal__body">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_general">

                <div class="tich-grid tich-grid--2" style="gap: 1rem;">
                    <div class="tich-form-group">
                        <label for="institution_name" class="tich-label">Institution name</label>
                        <input type="text" id="institution_name" name="institution_name" class="tich-input" required
                            value="{{ old('institution_name', $siteMeta['institution_name']) }}">
                    </div>
                    <div class="tich-form-group">
                        <label for="short_name" class="tich-label">Short name</label>
                        <input type="text" id="short_name" name="short_name" class="tich-input" required
                            value="{{ old('short_name', $siteMeta['short_name']) }}">
                    </div>
                    <div class="tich-form-group">
                        <label for="brand_name" class="tich-label">Navbar brand name</label>
                        <input type="text" id="brand_name" name="brand_name" class="tich-input" required
                            value="{{ old('brand_name', $siteMeta['brand_name']) }}">
                    </div>
                    <div class="tich-form-group">
                        <label for="brand_tagline" class="tich-label">Navbar tagline</label>
                        <input type="text" id="brand_tagline" name="brand_tagline" class="tich-input"
                            value="{{ old('brand_tagline', $siteMeta['brand_tagline']) }}">
                    </div>
                    <div class="tich-form-group">
                        <label for="tagline" class="tich-label">Site tagline</label>
                        <input type="text" id="tagline" name="tagline" class="tich-input"
                            value="{{ old('tagline', $siteMeta['tagline']) }}">
                    </div>
                    <div class="tich-form-group" style="grid-column: 1 / -1;">
                        <label for="ticker_message" class="tich-label">Homepage ticker message</label>
                        <textarea id="ticker_message" name="ticker_message" class="tich-input" rows="2" placeholder="e.g. TICH 18th Graduation — 9th to 11th September at Mama Grace Social Hall">{{ old('ticker_message', $siteMeta['ticker_message'] ?? '') }}</textarea>
                        <p class="tich-caption tich-mt-2">Shown as a scrolling marquee at the bottom of the homepage hero. Leave blank to hide.</p>
                    </div>
                    <div class="tich-form-group" style="grid-column: 1 / -1;">
                        <label for="meta_description" class="tich-label">Default SEO description</label>
                        <textarea id="meta_description" name="meta_description" class="tich-input" rows="2" maxlength="320" placeholder="Short summary for search engines (≈150–160 characters)">{{ old('meta_description', $siteMeta['meta_description'] ?? '') }}</textarea>
                        <p class="tich-caption tich-mt-2">Used as the fallback meta description and Open Graph text when a page does not set its own.</p>
                    </div>
                    <div class="tich-form-group">
                        <label for="website" class="tich-label">Website</label>
                        <input type="text" id="website" name="website" class="tich-input"
                            value="{{ old('website', $siteMeta['website']) }}">
                    </div>
                    <div class="tich-form-group" style="grid-column: 1 / -1;">
                        <label for="copyright" class="tich-label">Copyright line</label>
                        <input type="text" id="copyright" name="copyright" class="tich-input"
                            value="{{ old('copyright', $siteMeta['copyright']) }}">
                    </div>
                </div>

                <div class="tich-form-group tich-mt-4">
                    <label for="logo" class="tich-label">Site logo</label>
                    @if ($hasLogo)
                        <div class="tich-mb-4" style="display:flex; align-items:center; gap:1rem;">
                            <img src="{{ $siteMeta['logo_url'] }}" alt="Current logo" style="max-height: 56px; max-width: 200px; object-fit: contain;">
                            <label class="tich-text" style="display:flex; align-items:center; gap:0.5rem;">
                                <input type="checkbox" name="remove_logo" value="1">
                                Remove current logo
                            </label>
                        </div>
                    @endif
                    <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" class="tich-input">
                    <p class="tich-caption tich-mt-2">PNG or JPG, up to 2 MB. Used in the navigation bar when uploaded.</p>
                </div>

                <div class="tich-form-group tich-mt-4">
                    <label for="og_image" class="tich-label">Default social share image (Open Graph)</label>
                    @if (! empty($siteMeta['og_image_url']) && ($siteMeta['og_image_path'] ?? null) !== ($siteMeta['logo_path'] ?? null))
                        <div class="tich-mb-4" style="display:flex; align-items:center; gap:1rem;">
                            <img src="{{ $siteMeta['og_image_url'] }}" alt="Current social share image" style="max-height: 72px; max-width: 240px; object-fit: cover;">
                            <label class="tich-text" style="display:flex; align-items:center; gap:0.5rem;">
                                <input type="checkbox" name="remove_og_image" value="1">
                                Remove current image
                            </label>
                        </div>
                    @endif
                    <input type="file" id="og_image" name="og_image" accept="image/jpeg,image/png,image/webp,image/gif" class="tich-input">
                    <p class="tich-caption tich-mt-2">Recommended 1200×630. Falls back to the site logo when empty.</p>
                </div>

                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="general-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')
    @if ($openGeneralModal)
        <script>document.body.style.overflow = 'hidden';</script>
    @endif
@endcan
