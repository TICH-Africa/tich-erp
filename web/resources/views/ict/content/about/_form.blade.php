@props(['block' => null, 'prefix' => ''])

<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}title">Title</label>
    <input id="{{ $prefix }}title" type="text" name="title" class="tich-input" value="{{ old('title', $block->title ?? '') }}" required>
</div>

<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}subtitle">Subtitle (optional)</label>
    <input id="{{ $prefix }}subtitle" type="text" name="subtitle" class="tich-input" value="{{ old('subtitle', $block->subtitle ?? '') }}">
</div>

<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}body">Content</label>
    <textarea id="{{ $prefix }}body" name="body" class="tich-input" rows="8" required>{{ old('body', $block->body ?? '') }}</textarea>
</div>

<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}image">Image (optional)</label>
    <div id="{{ $prefix }}image_preview_wrap" @if (! ($block?->imageUrl())) hidden @endif>
        <img
            id="{{ $prefix }}image_preview"
            src="{{ $block?->imageUrl() ?? '' }}"
            alt=""
            style="display:block;width:100%;max-width:16rem;height:8rem;object-fit:cover;border-radius:0.5rem;margin-bottom:0.75rem;"
        >
        <label id="{{ $prefix }}remove_image_label" style="display:flex;gap:0.5rem;align-items:center;margin-bottom:0.75rem;" @if (! ($block?->imageUrl())) hidden @endif>
            <input type="checkbox" name="remove_image" value="1" id="{{ $prefix }}remove_image">
            <span class="tich-text">Remove current image</span>
        </label>
    </div>
    <input id="{{ $prefix }}image" type="file" name="image" class="tich-input" accept="image/jpeg,image/png,image/webp,image/gif">
    <p class="tich-caption tich-mt-2">Upload a file only (JPG, PNG, GIF, or WebP) - not a link. Compressed and saved as WebP. If none is set, the public page shows text only.</p>
</div>

<label style="display:flex;gap:0.5rem;align-items:center;">
    <input type="checkbox" name="is_active" value="1" id="{{ $prefix }}is_active" @checked(old('is_active', $block->is_active ?? true))>
    <span class="tich-text">Active on public About Us page</span>
</label>
