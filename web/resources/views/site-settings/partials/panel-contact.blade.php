@php
    $openCreateContactModal = $errors->any() && old('_form') === 'create_contact';
    $openEditContactId = old('_form') === 'edit_contact' ? (int) old('edit_contact_id') : null;
    $editContact = $openEditContactId ? $contacts->firstWhere('id', $openEditContactId) : null;
@endphp

@include('site-settings.partials.sortable-styles')

<p id="contact-sort-status" class="tich-caption tich-mb-4" aria-live="polite"></p>

<div class="tich-card tich-table-panel">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
        <h2 class="tich-h3" style="margin:0;">Contact channels ({{ $contacts->count() }})</h2>
        @can('site_settings.manage')
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="contact-create-modal">Add contact</button>
        @endcan
    </div>

    <table class="tich-admin-table tich-mt-4 tich-site-settings-sortable">
        <thead>
            <tr>
                @can('site_settings.manage')<th style="width: 2.5rem;"></th>@endcan
                <th>Type</th>
                <th>Label</th>
                <th>Value</th>
                <th>Primary</th>
                <th>Status</th>
                @can('site_settings.manage')<th style="width: 6rem;"></th>@endcan
            </tr>
        </thead>
        <tbody
            @can('site_settings.manage')
                data-row-sortable
                data-sort-url="{{ route('site-settings.contacts.reorder') }}"
                data-sort-status="#contact-sort-status"
            @endcan
        >
            @forelse ($contacts as $contact)
                <tr data-sortable-row data-sort-id="{{ $contact->id }}">
                    @can('site_settings.manage')
                        <td class="tich-drag-handle" title="Drag to reorder">⋮⋮</td>
                    @endcan
                    <td>{{ ucwords(str_replace('_', ' ', $contact->channel_type)) }}</td>
                    <td>{{ $contact->label }}</td>
                    <td>{{ $contact->display_value ?: $contact->value }}</td>
                    <td>{{ $contact->is_primary ? 'Yes' : 'No' }}</td>
                    <td>{{ $contact->is_active ? 'Active' : 'Hidden' }}</td>
                    @can('site_settings.manage')
                        <td style="white-space: nowrap;">
                            <button
                                type="button"
                                class="tich-squircle-btn contact-edit-trigger"
                                title="Edit contact"
                                aria-label="Edit {{ $contact->label }}"
                                data-open-modal="contact-edit-modal"
                                data-update-url="{{ route('site-settings.contacts.update', $contact) }}"
                                data-contact-id="{{ $contact->id }}"
                                data-channel-type="{{ $contact->channel_type }}"
                                data-label="{{ $contact->label }}"
                                data-value="{{ $contact->value }}"
                                data-display-value="{{ $contact->display_value }}"
                                data-is-primary="{{ $contact->is_primary ? '1' : '0' }}"
                                data-is-active="{{ $contact->is_active ? '1' : '0' }}"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>
                            <form method="POST" action="{{ route('site-settings.contacts.destroy', $contact) }}" style="display:inline;" onsubmit="return confirm('Remove this contact?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-squircle-btn" title="Delete contact" aria-label="Delete {{ $contact->label }}" style="color: #c0392b;">
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
                <tr><td colspan="7" class="tich-table-empty">No contact channels configured.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@can('site_settings.manage')
    <div id="contact-create-modal" class="tich-modal{{ $openCreateContactModal ? ' is-open' : '' }}" aria-hidden="{{ $openCreateContactModal ? 'false' : 'true' }}" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="contact-create-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Add contact channel</h2>
                <button type="button" class="tich-modal__close" data-close-modal="contact-create-modal" aria-label="Close">&times;</button>
            </header>
            <form method="POST" action="{{ route('site-settings.contacts.store') }}" class="tich-modal__body">
                @csrf
                <input type="hidden" name="_form" value="create_contact">
                <div class="tich-form-group">
                    <label class="tich-label">Type *</label>
                    <select name="channel_type" class="tich-select" required>
                        @foreach ($channelTypes as $type)
                            <option value="{{ $type }}" @selected(old('channel_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Label *</label>
                    <input type="text" name="label" class="tich-input" required placeholder="Admissions office" value="{{ old('label') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Value *</label>
                    <input type="text" name="value" class="tich-input" required placeholder="admissions@tich.ac.ke" value="{{ old('value') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display value</label>
                    <input type="text" name="display_value" class="tich-input" placeholder="Shown on site (optional)" value="{{ old('display_value') }}">
                </div>
                <label class="tich-text" style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="contact-primary-new" name="is_primary" value="1"> Primary contact
                </label>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="contact-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Add contact</button>
                </footer>
            </form>
        </div>
    </div>

    <div id="contact-edit-modal" class="tich-modal{{ $openEditContactId ? ' is-open' : '' }}" aria-hidden="{{ $openEditContactId ? 'false' : 'true' }}" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="contact-edit-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Edit contact channel</h2>
                <button type="button" class="tich-modal__close" data-close-modal="contact-edit-modal" aria-label="Close">&times;</button>
            </header>
            <form id="contact-edit-form" method="POST" action="{{ $editContact ? route('site-settings.contacts.update', $editContact) : '#' }}" class="tich-modal__body">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_contact">
                <input type="hidden" name="edit_contact_id" id="contact-edit-id" value="{{ old('edit_contact_id') }}">
                <div class="tich-form-group">
                    <label class="tich-label">Type *</label>
                    <select id="contact-edit-channel-type" name="channel_type" class="tich-select" required>
                        @foreach ($channelTypes as $type)
                            <option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Label *</label>
                    <input type="text" id="contact-edit-label" name="label" class="tich-input" required value="{{ old('label', $editContact?->label) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Value *</label>
                    <input type="text" id="contact-edit-value" name="value" class="tich-input" required value="{{ old('value', $editContact?->value) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display value</label>
                    <input type="text" id="contact-edit-display-value" name="display_value" class="tich-input" value="{{ old('display_value', $editContact?->display_value) }}">
                </div>
                <label class="tich-text" style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="contact-edit-is-primary" name="is_primary" value="1"> Primary contact
                </label>
                <label class="tich-text tich-mt-2" style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="contact-edit-is-active" name="is_active" value="1"> Active
                </label>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="contact-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')
    <script src="{{ asset('js/tich-row-sort.js') }}" defer></script>
    <script>
    (function () {
        var editForm = document.getElementById('contact-edit-form');
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

        document.querySelectorAll('.contact-edit-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                editForm.action = trigger.getAttribute('data-update-url') || '#';
                setField('contact-edit-id', trigger.getAttribute('data-contact-id'));
                setField('contact-edit-channel-type', trigger.getAttribute('data-channel-type'));
                setField('contact-edit-label', trigger.getAttribute('data-label'));
                setField('contact-edit-value', trigger.getAttribute('data-value'));
                setField('contact-edit-display-value', trigger.getAttribute('data-display-value'));
                setField('contact-edit-is-primary', trigger.getAttribute('data-is-primary'));
                setField('contact-edit-is-active', trigger.getAttribute('data-is-active'));
            });
        });

        if (document.querySelector('.tich-modal.is-open')) {
            document.body.style.overflow = 'hidden';
        }
    })();
    </script>
@endcan
