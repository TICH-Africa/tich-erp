<article class="tich-card">
    <h2 class="tich-h3">Site identity</h2>
    <p class="tich-text tich-mt-2">Controls the public site name, navbar branding, and logo shown across the website.</p>

    <form method="POST" action="{{ route('site-settings.general.update') }}" enctype="multipart/form-data" class="tich-mt-6">
        @csrf
        @method('PUT')

        <div class="tich-grid tich-grid--2" style="gap: 1.25rem;">
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

        <div class="tich-form-group tich-mt-6">
            <label for="logo" class="tich-label">Site logo</label>
            @if ($siteMeta['logo_url'])
                <div class="tich-mb-4" style="display:flex; align-items:center; gap:1rem;">
                    <img src="{{ $siteMeta['logo_url'] }}" alt="Current logo" style="max-height: 56px; max-width: 200px; object-fit: contain;">
                    <label class="tich-text" style="display:flex; align-items:center; gap:0.5rem;">
                        <input type="checkbox" name="remove_logo" value="1">
                        Remove current logo
                    </label>
                </div>
            @endif
            <input type="file" id="logo" name="logo" accept="image/*" class="tich-input">
            <p class="tich-caption tich-mt-2">PNG or JPG, up to 2 MB. Used in the navigation bar when uploaded.</p>
        </div>

        @can('site_settings.manage')
            <button type="submit" class="tich-btn tich-btn-primary tich-mt-6">Save identity settings</button>
        @endcan
    </form>
</article>
