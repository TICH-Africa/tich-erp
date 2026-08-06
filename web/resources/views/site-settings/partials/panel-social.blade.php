@can('site_settings.manage')
    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Add social link</h2>
        <form method="POST" action="{{ route('site-settings.social-links.store') }}" class="tich-mt-4">
            @csrf
            <div class="tich-grid tich-grid--2" style="gap: 1rem;">
                <div class="tich-form-group">
                    <label class="tich-label">Platform *</label>
                    <input type="text" name="platform" class="tich-input" required placeholder="linkedin">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display name *</label>
                    <input type="text" name="display_name" class="tich-input" required placeholder="LinkedIn">
                </div>
                <div class="tich-form-group" style="grid-column: 1 / -1;">
                    <label class="tich-label">URL *</label>
                    <input type="url" name="url" class="tich-input" required placeholder="https://linkedin.com/company/tich">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Icon name</label>
                    <input type="text" name="icon_name" class="tich-input" placeholder="linkedin">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display order</label>
                    <input type="number" name="display_order" class="tich-input" min="0" value="0">
                </div>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Add social link</button>
        </form>
    </article>
@endcan

<div class="tich-card tich-table-panel">
    <h2 class="tich-h3">Social links ({{ $socialLinks->count() }})</h2>
    <table class="tich-admin-table tich-mt-4">
        <thead>
            <tr>
                <th>Platform</th>
                <th>Name</th>
                <th>URL</th>
                <th>Status</th>
                @can('site_settings.manage')<th></th>@endcan
            </tr>
        </thead>
        <tbody>
            @forelse ($socialLinks as $link)
                <tr>
                    <td>{{ $link->platform }}</td>
                    <td>{{ $link->display_name }}</td>
                    <td><a href="{{ $link->url }}" class="tich-link" target="_blank" rel="noopener">{{ $link->url }}</a></td>
                    <td>{{ $link->is_active ? 'Active' : 'Hidden' }}</td>
                    @can('site_settings.manage')
                        <td>
                            <details>
                                <summary class="tich-link">Edit</summary>
                                <form method="POST" action="{{ route('site-settings.social-links.update', $link) }}" class="tich-mt-4">
                                    @csrf
                                    @method('PUT')
                                    <div class="tich-form-group"><input type="text" name="platform" class="tich-input" required value="{{ $link->platform }}"></div>
                                    <div class="tich-form-group"><input type="text" name="display_name" class="tich-input" required value="{{ $link->display_name }}"></div>
                                    <div class="tich-form-group"><input type="url" name="url" class="tich-input" required value="{{ $link->url }}"></div>
                                    <div class="tich-form-group"><input type="text" name="icon_name" class="tich-input" value="{{ $link->icon_name }}"></div>
                                    <div class="tich-form-group"><input type="number" name="display_order" class="tich-input" value="{{ $link->display_order }}"></div>
                                    <label class="tich-text"><input type="checkbox" name="is_active" value="1" {{ $link->is_active ? 'checked' : '' }}> Active</label>
                                    <button type="submit" class="tich-btn tich-btn-secondary tich-mt-3">Save</button>
                                </form>
                                <form method="POST" action="{{ route('site-settings.social-links.destroy', $link) }}" class="tich-mt-3" onsubmit="return confirm('Remove this link?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tich-link" style="color: var(--tich-danger, #b91c1c);">Delete</button>
                                </form>
                            </details>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="5" class="tich-table-empty">No social links configured.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
