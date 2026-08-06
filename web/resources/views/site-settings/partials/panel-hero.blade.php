@php
    $openCreateSlideModal = $errors->any() && old('_form') === 'create_slide';
    $openEditSlideId = old('_form') === 'edit_slide' ? (int) old('edit_slide_id') : null;
    $editSlide = $openEditSlideId ? $slides->firstWhere('id', $openEditSlideId) : null;
    $assetUrl = app(\App\Services\SiteSettingsService::class);
@endphp

@include('site-settings.partials.sortable-styles')

<p id="hero-sort-status" class="tich-caption tich-mb-4" aria-live="polite"></p>

<div class="tich-card tich-table-panel">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
        <h2 class="tich-h3" style="margin:0;">Hero carousel slides ({{ $slides->count() }})</h2>
        @can('site_settings.manage')
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="hero-create-modal">Add slide</button>
        @endcan
    </div>

    <table class="tich-admin-table tich-mt-4 tich-site-settings-sortable">
        <thead>
            <tr>
                @can('site_settings.manage')<th style="width: 2.5rem;"></th>@endcan
                <th>Preview</th>
                <th>Title & caption</th>
                <th>CTA</th>
                <th>Status</th>
                @can('site_settings.manage')<th style="width: 6rem;"></th>@endcan
            </tr>
        </thead>
        <tbody
            @can('site_settings.manage')
                data-row-sortable
                data-sort-url="{{ route('site-settings.hero-slides.reorder') }}"
                data-sort-status="#hero-sort-status"
            @endcan
        >
            @forelse ($slides as $slide)
                <tr data-sortable-row data-sort-id="{{ $slide->id }}">
                    @can('site_settings.manage')
                        <td class="tich-drag-handle" title="Drag to reorder">⋮⋮</td>
                    @endcan
                    <td>
                        @if ($slide->image_path)
                            <img src="{{ $assetUrl->publicAssetUrl($slide->image_path) }}" alt="" style="height:48px; width:80px; object-fit:cover; border-radius:4px;">
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
                        <td style="white-space: nowrap;">
                            <button
                                type="button"
                                class="tich-squircle-btn hero-edit-trigger"
                                title="Edit slide"
                                aria-label="Edit {{ $slide->title }}"
                                data-open-modal="hero-edit-modal"
                                data-update-url="{{ route('site-settings.hero-slides.update', $slide) }}"
                                data-slide-id="{{ $slide->id }}"
                                data-title="{{ $slide->title }}"
                                data-subtitle="{{ $slide->subtitle }}"
                                data-cta-label="{{ $slide->cta_label }}"
                                data-cta-url="{{ $slide->cta_url }}"
                                data-video-url="{{ $slide->video_url }}"
                                data-is-active="{{ $slide->is_active ? '1' : '0' }}"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>
                            <form method="POST" action="{{ route('site-settings.hero-slides.destroy', $slide) }}" style="display:inline;" onsubmit="return confirm('Remove this slide?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-squircle-btn" title="Delete slide" aria-label="Delete {{ $slide->title }}" style="color: #c0392b;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6"/><path d="M14 11v6"/>
                                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                    </svg>
                                </button>
                            </form>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="6" class="tich-table-empty">No hero slides yet. Add one or run the homepage seeder.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@can('site_settings.manage')
    <div id="hero-create-modal" class="tich-modal{{ $openCreateSlideModal ? ' is-open' : '' }}" aria-hidden="{{ $openCreateSlideModal ? 'false' : 'true' }}" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="hero-create-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Add hero slide</h2>
                <button type="button" class="tich-modal__close" data-close-modal="hero-create-modal" aria-label="Close">&times;</button>
            </header>
            <form method="POST" action="{{ route('site-settings.hero-slides.store') }}" enctype="multipart/form-data" class="tich-modal__body">
                @csrf
                <input type="hidden" name="_form" value="create_slide">
                <div class="tich-form-group">
                    <label class="tich-label">Title *</label>
                    <input type="text" name="title" class="tich-input" required value="{{ old('title') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Subtitle / caption</label>
                    <textarea name="subtitle" class="tich-input" rows="2">{{ old('subtitle') }}</textarea>
                </div>
                <div class="tich-grid tich-grid--2" style="gap: 1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label">CTA label</label>
                        <input type="text" name="cta_label" class="tich-input" value="{{ old('cta_label') }}">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">CTA URL</label>
                        <input type="text" name="cta_url" class="tich-input" placeholder="/programs" value="{{ old('cta_url') }}">
                    </div>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Background image</label>
                    <input type="file" name="image" accept="image/*" class="tich-input">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Video URL (optional)</label>
                    <input type="url" name="video_url" class="tich-input" value="{{ old('video_url') }}">
                </div>
                <label class="tich-text" style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="slide-active-new" name="is_active" value="1" checked> Active
                </label>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="hero-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Add slide</button>
                </footer>
            </form>
        </div>
    </div>

    <div id="hero-edit-modal" class="tich-modal{{ $openEditSlideId ? ' is-open' : '' }}" aria-hidden="{{ $openEditSlideId ? 'false' : 'true' }}" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="hero-edit-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Edit hero slide</h2>
                <button type="button" class="tich-modal__close" data-close-modal="hero-edit-modal" aria-label="Close">&times;</button>
            </header>
            <form id="hero-edit-form" method="POST" action="{{ $editSlide ? route('site-settings.hero-slides.update', $editSlide) : '#' }}" enctype="multipart/form-data" class="tich-modal__body">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_slide">
                <input type="hidden" name="edit_slide_id" id="hero-edit-id" value="{{ old('edit_slide_id') }}">
                <div class="tich-form-group">
                    <label class="tich-label">Title *</label>
                    <input type="text" id="hero-edit-title" name="title" class="tich-input" required value="{{ old('title', $editSlide?->title) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Subtitle / caption</label>
                    <textarea id="hero-edit-subtitle" name="subtitle" class="tich-input" rows="2">{{ old('subtitle', $editSlide?->subtitle) }}</textarea>
                </div>
                <div class="tich-grid tich-grid--2" style="gap: 1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label">CTA label</label>
                        <input type="text" id="hero-edit-cta-label" name="cta_label" class="tich-input" value="{{ old('cta_label', $editSlide?->cta_label) }}">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">CTA URL</label>
                        <input type="text" id="hero-edit-cta-url" name="cta_url" class="tich-input" value="{{ old('cta_url', $editSlide?->cta_url) }}">
                    </div>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Replace background image</label>
                    <input type="file" name="image" accept="image/*" class="tich-input">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Video URL (optional)</label>
                    <input type="url" id="hero-edit-video-url" name="video_url" class="tich-input" value="{{ old('video_url', $editSlide?->video_url) }}">
                </div>
                <label class="tich-text" style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="hero-edit-is-active" name="is_active" value="1"> Active
                </label>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="hero-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')
    <script src="{{ asset('js/tich-row-sort.js') }}" defer></script>
    <script>
    (function () {
        var editForm = document.getElementById('hero-edit-form');
        if (!editForm) return;

        function setField(id, value) {
            var field = document.getElementById(id);
            if (!field) return;
            if (field.type === 'checkbox') {
                field.checked = value === '1' || value === true;
            } else {
                field.value = value ?? '';
            }
        }

        document.querySelectorAll('.hero-edit-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                editForm.action = trigger.getAttribute('data-update-url') || '#';
                setField('hero-edit-id', trigger.getAttribute('data-slide-id'));
                setField('hero-edit-title', trigger.getAttribute('data-title'));
                setField('hero-edit-subtitle', trigger.getAttribute('data-subtitle'));
                setField('hero-edit-cta-label', trigger.getAttribute('data-cta-label'));
                setField('hero-edit-cta-url', trigger.getAttribute('data-cta-url'));
                setField('hero-edit-video-url', trigger.getAttribute('data-video-url'));
                setField('hero-edit-is-active', trigger.getAttribute('data-is-active'));
            });
        });

        if (document.querySelector('.tich-modal.is-open')) {
            document.body.style.overflow = 'hidden';
        }
    })();
    </script>
@endcan
