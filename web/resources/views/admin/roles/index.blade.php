@extends('layouts.admin')

@section('title', 'User roles')

@section('admin-content')
    @php
        $openCreateModal = $errors->any() && old('_form') === 'create';
        $openEditRoleId = old('_form') === 'edit' ? (int) old('edit_role_id') : null;
        $editRole = $openEditRoleId ? $roles->firstWhere('id', $openEditRoleId) : null;
    @endphp

    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: start; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <a href="{{ route('admin.users.index', ['audience' => 'staff']) }}" class="tich-link">&larr; Users &amp; access</a>
            <h1 class="tich-h1 tich-mt-4" style="font-size: 2rem;">User roles</h1>
            <p class="tich-text tich-mt-2" style="margin-bottom: 0;">
                Default system roles are built in. Add custom roles as your organisation grows — employees can hold multiple roles.
            </p>
        </div>
        <button type="button" class="tich-btn tich-btn-primary role-create-trigger" data-open-modal="role-create-modal">
            Add role
        </button>
    </div>

    @if ($errors->has('role'))
        <p class="tich-text tich-mb-4" style="color: #c0392b;">{{ $errors->first('role') }}</p>
    @endif

    @if ($errors->has('category'))
        <p class="tich-text tich-mb-4" style="color: #c0392b;">{{ $errors->first('category') }}</p>
    @endif

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Role name</th>
                    <th>Display name</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Users</th>
                    <th style="width: 6rem;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $roleItem)
                    <tr>
                        <td><strong>{{ $roleItem->role_name }}</strong></td>
                        <td>{{ $roleItem->display_name }}</td>
                        <td>{{ $categoryLabels[$roleItem->role_category] ?? ucfirst(str_replace('_', ' ', $roleItem->role_category)) }}</td>
                        <td>{{ $roleItem->is_system_role ? 'System' : 'Custom' }}</td>
                        <td>{{ $roleItem->users_count }}</td>
                        <td style="white-space: nowrap;">
                            <button
                                type="button"
                                class="tich-squircle-btn role-edit-trigger"
                                title="Edit role"
                                aria-label="Edit {{ $roleItem->role_name }}"
                                data-open-modal="role-edit-modal"
                                data-update-url="{{ route('admin.roles.update', $roleItem) }}"
                                data-role-id="{{ $roleItem->id }}"
                                data-role-name="{{ $roleItem->role_name }}"
                                data-display-name="{{ $roleItem->display_name }}"
                                data-role-category="{{ $roleItem->role_category }}"
                                data-description="{{ $roleItem->description }}"
                                data-is-system="{{ $roleItem->is_system_role ? '1' : '0' }}"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>
                            @unless ($roleItem->is_system_role)
                                <form method="POST" action="{{ route('admin.roles.destroy', $roleItem) }}" style="display: inline;" onsubmit="return confirm('Delete role {{ $roleItem->role_name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tich-squircle-btn" title="Delete role" aria-label="Delete {{ $roleItem->role_name }}" style="color: #c0392b;">
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
                    <tr><td colspan="6" class="tich-table-empty">No roles defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create modal --}}
    <div id="role-create-modal" class="tich-modal{{ $openCreateModal ? ' is-open' : '' }}" aria-hidden="{{ $openCreateModal ? 'false' : 'true' }}" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="role-create-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Add role</h2>
                <button type="button" class="tich-modal__close" data-close-modal="role-create-modal" aria-label="Close">&times;</button>
            </header>
            <form method="POST" action="{{ route('admin.roles.store') }}" class="tich-modal__body">
                @csrf
                <input type="hidden" name="_form" value="create">
                @include('admin.partials.role-form-fields', [
                    'categories' => $categories,
                    'role' => null,
                    'formContext' => 'create',
                    'fieldIdPrefix' => 'role-create-',
                ])
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="role-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Create role</button>
                </footer>
            </form>
        </div>
    </div>

    {{-- Edit modal --}}
    <div id="role-edit-modal" class="tich-modal{{ $openEditRoleId ? ' is-open' : '' }}" aria-hidden="{{ $openEditRoleId ? 'false' : 'true' }}" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="role-edit-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Edit role</h2>
                <button type="button" class="tich-modal__close" data-close-modal="role-edit-modal" aria-label="Close">&times;</button>
            </header>
            <form id="role-edit-form" method="POST" action="{{ $editRole ? route('admin.roles.update', $editRole) : '#' }}" class="tich-modal__body">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="edit_role_id" id="role-edit-id" value="{{ old('edit_role_id') }}">
                @include('admin.partials.role-form-fields', [
                    'categories' => $categories,
                    'role' => $editRole,
                    'fieldIdPrefix' => 'role-edit-',
                    'formContext' => 'edit',
                ])
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="role-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
                </footer>
            </form>
        </div>
    </div>

    <section class="tich-mt-8">
        <h2 class="tich-h2" style="font-size: 1.5rem;">Role categories</h2>
        <p class="tich-text tich-mb-6">
            Categories group roles for organisation and reporting. Default categories are built in; add custom ones as your institution grows.
        </p>

        <div class="tich-grid tich-grid--2" style="align-items: start; gap: 2rem;">
            <article class="tich-card">
                <h3 class="tich-h3">Add category</h3>
                <form method="POST" action="{{ route('admin.role-categories.store') }}" class="tich-mt-4">
                    @csrf
                    <input type="hidden" name="_form" value="create_category">
                    <div class="tich-form-group">
                        <label class="tich-label">Category code</label>
                        <input type="text" name="category_code" class="tich-input" value="{{ old('_form') === 'create_category' ? old('category_code') : '' }}" required placeholder="e.g. operations" pattern="[a-z][a-z0-9_-]*" title="Lowercase letters, numbers, hyphens, and underscores only">
                        <p class="tich-caption tich-mt-1">Lowercase identifier used internally. Cannot be changed after creation.</p>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Display name</label>
                        <input type="text" name="category_name" class="tich-input" value="{{ old('_form') === 'create_category' ? old('category_name') : '' }}" required placeholder="e.g. Operations">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Description</label>
                        <textarea name="description" class="tich-input" rows="2">{{ old('_form') === 'create_category' ? old('description') : '' }}</textarea>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Display order</label>
                        <input type="number" name="display_order" class="tich-input" value="{{ old('_form') === 'create_category' ? old('display_order', 0) : 0 }}" min="0">
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">Create category</button>
                </form>
            </article>

            <div class="tich-card tich-table-panel">
                <h3 class="tich-h3">Existing categories</h3>
                <table class="tich-admin-table tich-mt-4">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Roles</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roleCategories as $categoryItem)
                            <tr>
                                <td>{{ $categoryItem->display_order }}</td>
                                <td><code>{{ $categoryItem->category_code }}</code></td>
                                <td>{{ $categoryItem->category_name }}</td>
                                <td>{{ $rolesPerCategory[$categoryItem->category_code] ?? 0 }}</td>
                                <td>{{ $categoryItem->is_system ? 'System' : 'Custom' }}</td>
                                <td>{{ $categoryItem->is_active ? 'Active' : 'Inactive' }}</td>
                                <td>
                                    <details>
                                        <summary class="tich-link" style="cursor: pointer;">Edit</summary>
                                        <form method="POST" action="{{ route('admin.role-categories.update', $categoryItem) }}" class="tich-mt-4" style="min-width: 16rem;">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="_form" value="edit_category">
                                            <input type="hidden" name="edit_category_id" value="{{ $categoryItem->id }}">
                                            <div class="tich-form-group">
                                                <label class="tich-label">Code</label>
                                                <input type="text" class="tich-input" value="{{ $categoryItem->category_code }}" readonly>
                                            </div>
                                            <div class="tich-form-group">
                                                <label class="tich-label">Display name</label>
                                                <input type="text" name="category_name" class="tich-input" value="{{ old('_form') === 'edit_category' && (int) old('edit_category_id') === $categoryItem->id ? old('category_name') : $categoryItem->category_name }}" required>
                                            </div>
                                            <div class="tich-form-group">
                                                <label class="tich-label">Description</label>
                                                <textarea name="description" class="tich-input" rows="2">{{ old('_form') === 'edit_category' && (int) old('edit_category_id') === $categoryItem->id ? old('description') : $categoryItem->description }}</textarea>
                                            </div>
                                            <div class="tich-form-group">
                                                <label class="tich-label">Display order</label>
                                                <input type="number" name="display_order" class="tich-input" value="{{ old('_form') === 'edit_category' && (int) old('edit_category_id') === $categoryItem->id ? old('display_order', 0) : $categoryItem->display_order }}" min="0">
                                            </div>
                                            <label style="display: flex; gap: 0.5rem; align-items: center;">
                                                <input type="checkbox" name="is_active" value="1" @checked(old('_form') === 'edit_category' && (int) old('edit_category_id') === $categoryItem->id ? old('is_active', true) : $categoryItem->is_active)>
                                                <span class="tich-text">Active</span>
                                            </label>
                                            <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save</button>
                                        </form>
                                        @unless ($categoryItem->is_system)
                                            <form method="POST" action="{{ route('admin.role-categories.destroy', $categoryItem) }}" class="tich-mt-4" onsubmit="return confirm('Delete category {{ $categoryItem->category_name }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="tich-btn tich-btn-secondary" style="color: #c0392b;">Delete category</button>
                                            </form>
                                        @endunless
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No role categories yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @include('admin.partials.tich-modal-assets')

    <script>
    (function () {
        var editForm = document.getElementById('role-edit-form');
        var createForm = document.querySelector('#role-create-modal form');

        function setField(id, value) {
            var field = document.getElementById(id);
            if (field) field.value = value ?? '';
        }

        document.querySelectorAll('.role-create-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                if (!createForm) return;

                setField('role-create-role_name', '');
                setField('role-create-display_name', '');
                setField('role-create-role_category', '');
                setField('role-create-description', '');

                var nameField = document.getElementById('role-create-role_name');
                if (nameField) nameField.readOnly = false;
            });
        });

        if (!editForm) return;

        document.querySelectorAll('.role-edit-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                editForm.action = trigger.getAttribute('data-update-url') || '#';
                setField('role-edit-id', trigger.getAttribute('data-role-id'));
                setField('role-edit-role_name', trigger.getAttribute('data-role-name'));
                setField('role-edit-display_name', trigger.getAttribute('data-display-name'));
                setField('role-edit-role_category', trigger.getAttribute('data-role-category'));
                setField('role-edit-description', trigger.getAttribute('data-description'));

                var nameField = document.getElementById('role-edit-role_name');
                if (nameField) {
                    nameField.readOnly = trigger.getAttribute('data-is-system') === '1';
                }
            });
        });

        if (document.querySelector('.tich-modal.is-open')) {
            document.body.style.overflow = 'hidden';
        }
    })();
    </script>
@endsection
