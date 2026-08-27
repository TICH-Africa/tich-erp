@props(['post' => null, 'statuses' => [], 'prefix' => ''])

<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}title">Title</label>
    <input id="{{ $prefix }}title" type="text" name="title" class="tich-input" value="{{ old('title', $post->title ?? '') }}" required>
</div>
<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}subtitle">Subtitle</label>
    <input id="{{ $prefix }}subtitle" type="text" name="subtitle" class="tich-input" value="{{ old('subtitle', $post->subtitle ?? '') }}">
</div>
<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}excerpt">Excerpt</label>
    <textarea id="{{ $prefix }}excerpt" name="excerpt" class="tich-input" rows="2">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
</div>
<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}body">Body</label>
    <textarea id="{{ $prefix }}body" name="body" class="tich-input" rows="8" required>{{ old('body', $post->body ?? '') }}</textarea>
</div>
<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}status">Status</label>
    <select id="{{ $prefix }}status" name="status" class="tich-input" required>
        @foreach ($statuses as $status)
            <option value="{{ $status }}" @selected(old('status', $post->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
</div>
<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}featured_image">Featured image</label>
    <input id="{{ $prefix }}featured_image" type="file" name="featured_image" class="tich-input" accept="image/jpeg,image/png,image/webp,image/gif">
    <p class="tich-caption tich-mt-2">Upload a file only (not a URL). Compressed and saved as WebP.</p>
    @if ($post?->imageUrl())
        <label style="display:flex;gap:0.5rem;align-items:center;margin-top:0.5rem;">
            <input type="checkbox" name="remove_image" value="1">
            <span class="tich-text">Remove current image</span>
        </label>
    @endif
</div>
<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}seo_meta_title">SEO title</label>
    <input id="{{ $prefix }}seo_meta_title" type="text" name="seo_meta_title" class="tich-input" maxlength="300" value="{{ old('seo_meta_title', $post->seo_meta_title ?? '') }}" placeholder="Optional — defaults to post title">
</div>
<div class="tich-form-group">
    <label class="tich-label" for="{{ $prefix }}seo_meta_description">SEO description</label>
    <textarea id="{{ $prefix }}seo_meta_description" name="seo_meta_description" class="tich-input" rows="2" maxlength="500" placeholder="Optional — shown in search results (≈150–160 characters)">{{ old('seo_meta_description', $post->seo_meta_description ?? '') }}</textarea>
</div>
