@php
    $openCreateSocialModal = $errors->any() && old('_form') === 'create_social';
    $openEditSocialId = old('_form') === 'edit_social' ? (int) old('edit_social_id') : null;
    $editSocial = $openEditSocialId ? $socialLinks->firstWhere('id', $openEditSocialId) : null;
@endphp

@include('site-settings.partials.sortable-styles')

<p id="social-sort-status" class="tich-caption tich-mb-4" aria-live="polite"></p>

<div class="tich-card tich-table-panel">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
        <h2 class="tich-h3" style="margin:0;">Social links ({{ $socialLinks->count() }})</h2>
        @can('site_settings.manage')
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="social-create-modal">Add social link</button>
        @endcan
    </div>

    <table class="tich-admin-table tich-mt-4 tich-site-settings-sortable">
        <thead>
            <tr>
                @can('site_settings.manage')<th style="width: 2.5rem;"></th>@endcan
                <th>Platform</th>
                <th>Name</th>
                <th>URL</th>
                <th>Status</th>
                @can('site_settings.manage')<th style="width: 6rem;"></th>@endcan
            </tr>
        </thead>
        <tbody
            @can('site_settings.manage')
                data-row-sortable
                data-sort-url="{{ route('site-settings.social-links.reorder') }}"
                data-sort-status="#social-sort-status"
            @endcan
        >
            @forelse ($socialLinks as $link)
                <tr data-sortable-row data-sort-id="{{ $link->id }}">
                    @can('site_settings.manage')
                        <td class="tich-drag-handle" title="Drag to reorder">⋮⋮</td>
                    @endcan
                    <td>{{ $link->platform }}</td>
                    <td>{{ $link->display_name }}</td>
                    <td><a href="{{ $link->url }}" class="tich-link" target="_blank" rel="noopener">{{ $link->url }}</a></td>
                    <td>{{ $link->is_active ? 'Active' : 'Hidden' }}</td>
                    @can('site_settings.manage')
                        <td style="white-space: nowrap;">
                            <button
                                type="button"
                                class="tich-squircle-btn social-edit-trigger"
                                title="Edit social link"
                                aria-label="Edit {{ $link->display_name }}"
                                data-open-modal="social-edit-modal"
                                data-update-url="{{ route('site-settings.social-links.update', $link) }}"
                                data-social-id="{{ $link->id }}"
                                data-platform="{{ $link->platform }}"
                                data-display-name="{{ $link->display_name }}"
                                data-url="{{ $link->url }}"
                                data-icon-name="{{ $link->icon_name }}"
                                data-is-active="{{ $link->is_active ? '1' : '0' }}"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>
                            <form method="POST" action="{{ route('site-settings.social-links.destroy', $link) }}" style="display:inline;" onsubmit="return confirm('Remove this link?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-squircle-btn" title="Delete social link" aria-label="Delete {{ $link->display_name }}" style="color: #c0392b;">
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
                <tr><td colspan="6" class="tich-table-empty">No social links configured.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@can('site_settings.manage')
    <div id="social-create-modal" class="tich-modal{{ $openCreateSocialModal ? ' is-open' : '' }}" aria-hidden="{{ $openCreateSocialModal ? 'false' : 'true' }}" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="social-create-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Add social link</h2>
                <button type="button" class="tich-modal__close" data-close-modal="social-create-modal" aria-label="Close">&times;</button>
            </header>
            <form method="POST" action="{{ route('site-settings.social-links.store') }}" class="tich-modal__body">
                @csrf
                <input type="hidden" name="_form" value="create_social">
                <div class="tich-form-group">
                    <label class="tich-label">Platform *</label>
                    <input type="text" name="platform" class="tich-input" required placeholder="linkedin" value="{{ old('platform') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display name *</label>
                    <input type="text" name="display_name" class="tich-input" required placeholder="LinkedIn" value="{{ old('display_name') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">URL *</label>
                    <input type="url" name="url" class="tich-input" required placeholder="https://linkedin.com/company/tich" value="{{ old('url') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Icon name</label>
                    <input type="text" name="icon_name" class="tich-input" placeholder="linkedin" value="{{ old('icon_name') }}">
                </div>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="social-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Add social link</button>
                </footer>
            </form>
        </div>
    </div>

    <div id="social-edit-modal" class="tich-modal{{ $openEditSocialId ? ' is-open' : '' }}" aria-hidden="{{ $openEditSocialId ? 'false' : 'true' }}" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="social-edit-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Edit social link</h2>
                <button type="button" class="tich-modal__close" data-close-modal="social-edit-modal" aria-label="Close">&times;</button>
            </header>
            <form id="social-edit-form" method="POST" action="{{ $editSocial ? route('site-settings.social-links.update', $editSocial) : '#' }}" class="tich-modal__body">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_social">
                <input type="hidden" name="edit_social_id" id="social-edit-id" value="{{ old('edit_social_id') }}">
                <div class="tich-form-group">
                    <label class="tich-label">Platform *</label>
                    <input type="text" id="social-edit-platform" name="platform" class="tich-input" required value="{{ old('platform', $editSocial?->platform) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display name *</label>
                    <input type="text" id="social-edit-display-name" name="display_name" class="tich-input" required value="{{ old('display_name', $editSocial?->display_name) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">URL *</label>
                    <input type="url" id="social-edit-url" name="url" class="tich-input" required value="{{ old('url', $editSocial?->url) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Icon name</label>
                    <input type="text" id="social-edit-icon-name" name="icon_name" class="tich-input" value="{{ old('icon_name', $editSocial?->icon_name) }}">
                </div>
                <label class="tich-text" style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="social-edit-is-active" name="is_active" value="1"> Active
                </label>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="social-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')
    <script src="{{ asset('js/tich-row-sort.js') }}" defer></script>
    <script>
    (function () {
        var editForm = document.getElementById('social-edit-form');
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

        document.querySelectorAll('.social-edit-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                editForm.action = trigger.getAttribute('data-update-url') || '#';
                setField('social-edit-id', trigger.getAttribute('data-social-id'));
                setField('social-edit-platform', trigger.getAttribute('data-platform'));
                setField('social-edit-display-name', trigger.getAttribute('data-display-name'));
                setField('social-edit-url', trigger.getAttribute('data-url'));
                setField('social-edit-icon-name', trigger.getAttribute('data-icon-name'));
                setField('social-edit-is-active', trigger.getAttribute('data-is-active'));
            });
        });

        if (document.querySelector('.tich-modal.is-open')) {
            document.body.style.overflow = 'hidden';
        }
    })();
    </script>
@endcan
