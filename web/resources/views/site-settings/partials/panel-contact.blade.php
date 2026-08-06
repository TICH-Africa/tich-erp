@can('site_settings.manage')
    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Add contact channel</h2>
        <form method="POST" action="{{ route('site-settings.contacts.store') }}" class="tich-mt-4">
            @csrf
            <div class="tich-grid tich-grid--2" style="gap: 1rem;">
                <div class="tich-form-group">
                    <label class="tich-label">Type *</label>
                    <select name="channel_type" class="tich-select" required>
                        @foreach ($channelTypes as $type)
                            <option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Label *</label>
                    <input type="text" name="label" class="tich-input" required placeholder="Admissions office">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Value *</label>
                    <input type="text" name="value" class="tich-input" required placeholder="admissions@tich.ac.ke">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display value</label>
                    <input type="text" name="display_value" class="tich-input" placeholder="Shown on site (optional)">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display order</label>
                    <input type="number" name="display_order" class="tich-input" min="0" value="0">
                </div>
                <div class="tich-form-group" style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="contact-primary-new" name="is_primary" value="1">
                    <label for="contact-primary-new" class="tich-text">Primary contact</label>
                </div>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Add contact</button>
        </form>
    </article>
@endcan

<div class="tich-card tich-table-panel">
    <h2 class="tich-h3">Contact channels ({{ $contacts->count() }})</h2>
    <table class="tich-admin-table tich-mt-4">
        <thead>
            <tr>
                <th>Type</th>
                <th>Label</th>
                <th>Value</th>
                <th>Primary</th>
                <th>Status</th>
                @can('site_settings.manage')<th></th>@endcan
            </tr>
        </thead>
        <tbody>
            @forelse ($contacts as $contact)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $contact->channel_type)) }}</td>
                    <td>{{ $contact->label }}</td>
                    <td>{{ $contact->display_value ?: $contact->value }}</td>
                    <td>{{ $contact->is_primary ? 'Yes' : 'No' }}</td>
                    <td>{{ $contact->is_active ? 'Active' : 'Hidden' }}</td>
                    @can('site_settings.manage')
                        <td>
                            <details>
                                <summary class="tich-link">Edit</summary>
                                <form method="POST" action="{{ route('site-settings.contacts.update', $contact) }}" class="tich-mt-4">
                                    @csrf
                                    @method('PUT')
                                    <div class="tich-form-group">
                                        <select name="channel_type" class="tich-select">
                                            @foreach ($channelTypes as $type)
                                                <option value="{{ $type }}" @selected($contact->channel_type === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="tich-form-group"><input type="text" name="label" class="tich-input" required value="{{ $contact->label }}"></div>
                                    <div class="tich-form-group"><input type="text" name="value" class="tich-input" required value="{{ $contact->value }}"></div>
                                    <div class="tich-form-group"><input type="text" name="display_value" class="tich-input" value="{{ $contact->display_value }}"></div>
                                    <div class="tich-form-group"><input type="number" name="display_order" class="tich-input" value="{{ $contact->display_order }}"></div>
                                    <label class="tich-text"><input type="checkbox" name="is_primary" value="1" {{ $contact->is_primary ? 'checked' : '' }}> Primary</label>
                                    <label class="tich-text tich-mt-2"><input type="checkbox" name="is_active" value="1" {{ $contact->is_active ? 'checked' : '' }}> Active</label>
                                    <button type="submit" class="tich-btn tich-btn-secondary tich-mt-3">Save</button>
                                </form>
                                <form method="POST" action="{{ route('site-settings.contacts.destroy', $contact) }}" class="tich-mt-3" onsubmit="return confirm('Remove this contact?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tich-link" style="color: var(--tich-danger, #b91c1c);">Delete</button>
                                </form>
                            </details>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="6" class="tich-table-empty">No contact channels configured.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
