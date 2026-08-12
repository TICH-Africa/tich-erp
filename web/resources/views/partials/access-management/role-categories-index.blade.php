@php
    $openCreateModal = $errors->any() && old('_form') === 'create_category';
    $openEditCategoryId = old('_form') === 'edit_category' ? (int) old('edit_category_id') : null;
    $editCategory = $openEditCategoryId ? $roleCategories->firstWhere('id', $openEditCategoryId) : null;
@endphp

<a href="{{ $access->route('users.index', ['audience' => 'staff']) }}" class="tich-link">&larr; Users &amp; access</a>

<x-page-toolbar
    title="User roles &amp; categories"
    meta="Group roles for organisation and reporting"
    class="tich-mt-4"
/>

@include('partials.access-management.roles-tabs', [
    'access' => $access,
    'section' => 'categories',
    'rolesCount' => $rolesCount,
    'categoriesCount' => $categoriesCount,
])

@if ($errors->has('category'))
    <p class="tich-text tich-mb-4" style="color: #c0392b;">{{ $errors->first('category') }}</p>
@endif

<p id="category-sort-status" class="tich-caption tich-mb-4" aria-live="polite"></p>

<style>
    .tich-category-sortable tbody tr[data-sortable-row] { cursor: default; }
    .tich-category-sortable tbody tr.is-dragging { opacity: 0.45; cursor: grabbing; }
    .tich-drag-handle {
        color: var(--tich-muted, #64748b);
        user-select: none;
        width: 2rem;
        text-align: center;
        font-size: 1.1rem;
        line-height: 1;
        cursor: grab;
    }
    .tich-drag-handle:active { cursor: grabbing; }
</style>

<div class="tich-card tich-table-panel">
    <table class="tich-admin-table tich-category-sortable">
        <thead>
            <tr>
                <th style="width: 2.5rem;"></th>
                <th>Code</th>
                <th>Name</th>
                <th>Roles</th>
                <th>Type</th>
                <th>Status</th>
                <th style="width: 6rem;"></th>
            </tr>
        </thead>
        <tbody
            data-row-sortable
            data-sort-url="{{ $access->route('role-categories.reorder') }}"
            data-sort-status="#category-sort-status"
        >
            @forelse ($roleCategories as $categoryItem)
                <tr data-sortable-row data-sort-id="{{ $categoryItem->id }}">
                    <td class="tich-drag-handle" title="Drag to reorder">⋮⋮</td>
                    <td><code>{{ $categoryItem->category_code }}</code></td>
                    <td><strong>{{ $categoryItem->category_name }}</strong></td>
                    <td>{{ $rolesPerCategory[$categoryItem->category_code] ?? 0 }}</td>
                    <td>{{ $categoryItem->is_system ? 'System' : 'Custom' }}</td>
                    <td>{{ $categoryItem->is_active ? 'Active' : 'Inactive' }}</td>
                    <td style="white-space: nowrap;">
                        <button
                            type="button"
                            class="tich-squircle-btn category-edit-trigger"
                            title="Edit category"
                            aria-label="Edit {{ $categoryItem->category_name }}"
                            data-open-modal="category-edit-modal"
                            data-update-url="{{ $access->route('role-categories.update', $categoryItem) }}"
                            data-category-id="{{ $categoryItem->id }}"
                            data-category-code="{{ $categoryItem->category_code }}"
                            data-category-name="{{ $categoryItem->category_name }}"
                            data-description="{{ $categoryItem->description }}"
                            data-is-active="{{ $categoryItem->is_active ? '1' : '0' }}"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                            </svg>
                        </button>
                        @unless ($categoryItem->is_system)
                            <form method="POST" action="{{ $access->route('role-categories.destroy', $categoryItem) }}" style="display: inline;" onsubmit="return confirm('Delete category {{ $categoryItem->category_name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-squircle-btn" title="Delete category" aria-label="Delete {{ $categoryItem->category_name }}" style="color: #c0392b;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6"/><path d="M14 11v6"/>
                                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                    </svg>
                                </button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="tich-table-empty">No role categories yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="category-create-modal" class="tich-modal{{ $openCreateModal ? ' is-open' : '' }}" aria-hidden="{{ $openCreateModal ? 'false' : 'true' }}" role="dialog" aria-modal="true">
    <div class="tich-modal__backdrop" data-close-modal="category-create-modal"></div>
    <div class="tich-modal__dialog">
        <header class="tich-modal__header">
            <h2 class="tich-h3" style="margin: 0;">Add category</h2>
            <button type="button" class="tich-modal__close" data-close-modal="category-create-modal" aria-label="Close">&times;</button>
        </header>
        <form method="POST" action="{{ $access->route('role-categories.store') }}" class="tich-modal__body">
            @csrf
            <input type="hidden" name="_form" value="create_category">
            @include('admin.partials.role-category-form-fields', [
                'category' => null,
                'formContext' => 'create_category',
                'fieldIdPrefix' => 'category-create-',
            ])
            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="category-create-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Create category</button>
            </footer>
        </form>
    </div>
</div>

<div id="category-edit-modal" class="tich-modal{{ $openEditCategoryId ? ' is-open' : '' }}" aria-hidden="{{ $openEditCategoryId ? 'false' : 'true' }}" role="dialog" aria-modal="true">
    <div class="tich-modal__backdrop" data-close-modal="category-edit-modal"></div>
    <div class="tich-modal__dialog">
        <header class="tich-modal__header">
            <h2 class="tich-h3" style="margin: 0;">Edit category</h2>
            <button type="button" class="tich-modal__close" data-close-modal="category-edit-modal" aria-label="Close">&times;</button>
        </header>
        <form id="category-edit-form" method="POST" action="{{ $editCategory ? $access->route('role-categories.update', $editCategory) : '#' }}" class="tich-modal__body">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit_category">
            <input type="hidden" name="edit_category_id" id="category-edit-id" value="{{ old('edit_category_id') }}">
            @include('admin.partials.role-category-form-fields', [
                'category' => $editCategory,
                'formContext' => 'edit_category',
                'fieldIdPrefix' => 'category-edit-',
            ])
            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="category-edit-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
            </footer>
        </form>
    </div>
</div>

@include('admin.partials.tich-modal-assets')

<script src="{{ asset('js/tich-row-sort.js') }}" defer></script>
<script>
(function () {
    var editForm = document.getElementById('category-edit-form');
    var createForm = document.querySelector('#category-create-modal form');

    function setField(id, value) {
        var field = document.getElementById(id);
        if (!field) return;
        if (field.type === 'checkbox') {
            field.checked = value === '1' || value === true;
        } else {
            field.value = value ?? '';
        }
    }

    document.querySelectorAll('.category-create-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            if (!createForm) return;

            setField('category-create-category_code', '');
            setField('category-create-category_name', '');
            setField('category-create-description', '');
        });
    });

    if (editForm) {
        document.querySelectorAll('.category-edit-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                editForm.action = trigger.getAttribute('data-update-url') || '#';
                setField('category-edit-id', trigger.getAttribute('data-category-id'));
                setField('category-edit-category_name', trigger.getAttribute('data-category-name'));
                setField('category-edit-description', trigger.getAttribute('data-description'));
                setField('category-edit-is_active', trigger.getAttribute('data-is-active'));

                var codeDisplay = editForm.querySelector('[readonly]');
                if (codeDisplay) {
                    codeDisplay.value = trigger.getAttribute('data-category-code') ?? '';
                }
            });
        });
    }

    if (document.querySelector('.tich-modal.is-open')) {
        document.body.style.overflow = 'hidden';
    }
})();
</script>
