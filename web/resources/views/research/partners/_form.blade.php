@props([
    'partner' => null,
])

<div class="uf-form-section">
    <div class="uf-section-head">Partner</div>
    <div class="uf-section-body">
        <div class="uf-form-grid-2">
            <div class="uf-field" style="grid-column: 1 / -1;">
                <label for="name">Partner name <span class="uf-req">*</span></label>
                <input id="name" type="text" name="name" value="{{ old('name', $partner->name ?? '') }}" required maxlength="300" placeholder="e.g. County Health Department, Kisumu">
            </div>
            <div class="uf-field" style="grid-column: 1 / -1;">
                <label for="logo">Logo</label>
                <input id="logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
                <span class="uf-hint">Upload the whole logo - it is never cropped on the website. PNG with a transparent background looks best.</span>
                @if ($partner?->logoUrl())
                    <div class="uf-field--check tich-mt-2" style="flex-wrap:nowrap;">
                        <img src="{{ $partner->logoUrl() }}" alt="" style="width:6rem;height:2.5rem;object-fit:contain;border-radius:4px;flex-shrink:0;background:#e8eaed;">
                        <input type="checkbox" id="remove_logo" name="remove_logo" value="1" class="tich-checkbox">
                        <label for="remove_logo">Remove current logo</label>
                    </div>
                @endif
            </div>
            <div class="uf-field uf-field--check" style="grid-column: 1 / -1;">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="tich-checkbox" @checked(old('is_active', $partner->is_active ?? true))>
                <label for="is_active">Show on the website</label>
                <span class="uf-hint">Shown to visitors under "Our partners" on the public Research page.</span>
            </div>
        </div>
    </div>
</div>
