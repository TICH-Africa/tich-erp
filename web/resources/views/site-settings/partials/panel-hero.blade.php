@can('site_settings.manage')
    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Add hero slide</h2>
        <form method="POST" action="{{ route('site-settings.hero-slides.store') }}" enctype="multipart/form-data" class="tich-mt-4">
            @csrf
            <div class="tich-grid tich-grid--2" style="gap: 1rem;">
                <div class="tich-form-group">
                    <label class="tich-label">Title *</label>
                    <input type="text" name="title" class="tich-input" required value="{{ old('title') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display order</label>
                    <input type="number" name="display_order" class="tich-input" min="0" value="{{ old('display_order', $slides->count()) }}">
                </div>
                <div class="tich-form-group" style="grid-column: 1 / -1;">
                    <label class="tich-label">Subtitle / caption</label>
                    <textarea name="subtitle" class="tich-input" rows="2">{{ old('subtitle') }}</textarea>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">CTA label</label>
                    <input type="text" name="cta_label" class="tich-input" value="{{ old('cta_label') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">CTA URL</label>
                    <input type="text" name="cta_url" class="tich-input" placeholder="/programs" value="{{ old('cta_url') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Background image</label>
                    <input type="file" name="image" accept="image/*" class="tich-input">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Video URL (optional)</label>
                    <input type="url" name="video_url" class="tich-input" value="{{ old('video_url') }}">
                </div>
                <div class="tich-form-group" style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="slide-active-new" name="is_active" value="1" checked>
                    <label for="slide-active-new" class="tich-text">Active</label>
                </div>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Add slide</button>
        </form>
    </article>
@endcan

<div class="tich-card tich-table-panel">
    <h2 class="tich-h3">Hero carousel slides ({{ $slides->count() }})</h2>
    <table class="tich-admin-table tich-mt-4">
        <thead>
            <tr>
                <th>Order</th>
                <th>Preview</th>
                <th>Title & caption</th>
                <th>CTA</th>
                <th>Status</th>
                @can('site_settings.manage')<th></th>@endcan
            </tr>
        </thead>
        <tbody>
            @forelse ($slides as $slide)
                <tr>
                    <td>{{ $slide->display_order }}</td>
                    <td>
                        @if ($slide->image_path)
                            <img src="{{ asset(ltrim($slide->image_path, '/')) }}" alt="" style="height:48px; width:80px; object-fit:cover; border-radius:4px;">
                        @else
                            <span class="tich-caption">No image</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $slide->title }}</strong>
                        @if ($slide->subtitle)
                            <br><span class="tich-caption">{{ $slide->subtitle }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($slide->cta_label)
                            {{ $slide->cta_label }} → {{ $slide->cta_url }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $slide->is_active ? 'Active' : 'Hidden' }}</td>
                    @can('site_settings.manage')
                        <td>
                            <details>
                                <summary class="tich-link">Edit</summary>
                                <form method="POST" action="{{ route('site-settings.hero-slides.update', $slide) }}" enctype="multipart/form-data" class="tich-mt-4" style="min-width: 280px;">
                                    @csrf
                                    @method('PUT')
                                    <div class="tich-form-group">
                                        <label class="tich-label">Title</label>
                                        <input type="text" name="title" class="tich-input" required value="{{ $slide->title }}">
                                    </div>
                                    <div class="tich-form-group">
                                        <label class="tich-label">Subtitle</label>
                                        <textarea name="subtitle" class="tich-input" rows="2">{{ $slide->subtitle }}</textarea>
                                    </div>
                                    <div class="tich-form-group">
                                        <label class="tich-label">CTA label / URL</label>
                                        <input type="text" name="cta_label" class="tich-input" value="{{ $slide->cta_label }}">
                                        <input type="text" name="cta_url" class="tich-input tich-mt-2" value="{{ $slide->cta_url }}">
                                    </div>
                                    <div class="tich-form-group">
                                        <label class="tich-label">Replace image</label>
                                        <input type="file" name="image" accept="image/*" class="tich-input">
                                    </div>
                                    <div class="tich-form-group">
                                        <label class="tich-label">Order</label>
                                        <input type="number" name="display_order" class="tich-input" value="{{ $slide->display_order }}">
                                    </div>
                                    <label class="tich-text" style="display:flex; gap:0.5rem; align-items:center;">
                                        <input type="checkbox" name="is_active" value="1" {{ $slide->is_active ? 'checked' : '' }}> Active
                                    </label>
                                    <button type="submit" class="tich-btn tich-btn-secondary tich-mt-3">Save</button>
                                </form>
                                <form method="POST" action="{{ route('site-settings.hero-slides.destroy', $slide) }}" class="tich-mt-3" onsubmit="return confirm('Remove this slide?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tich-link" style="color: var(--tich-danger, #b91c1c);">Delete slide</button>
                                </form>
                            </details>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="6" class="tich-table-empty">No hero slides yet. Add one above or run the homepage seeder.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
