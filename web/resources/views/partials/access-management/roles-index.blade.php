@php
    $openCreateModal = $errors->any() && old('_form') === 'create';
    $openEditRoleId = old('_form') === 'edit' ? (int) old('edit_role_id') : null;
    $editRole = $openEditRoleId ? $roles->firstWhere('id', $openEditRoleId) : null;
@endphp

<a href="{{ $access->route('users.index', ['audience' => 'staff']) }}" class="tich-link">&larr; Users &amp; access</a>

<x-page-toolbar
    title="User roles &amp; categories"
    meta="Module-scoped roles with configurable permissions for access and CRUD operations"
    class="tich-mt-4"
/>

<div class="tich-tabs tich-mb-6">
    <div class="tich-tabs__nav" style="flex-wrap: wrap; gap: 0.5rem;">
        <a href="{{ $access->route('roles.index') }}" class="tich-tabs__btn{{ $selectedModule === '' ? ' is-active' : '' }}">All modules</a>
        @foreach ($moduleOptions as $moduleKey => $module)
            <a
                href="{{ $access->route('roles.index', ['module' => $moduleKey]) }}"
                class="tich-tabs__btn{{ $selectedModule === $moduleKey ? ' is-active' : '' }}"
            >{{ $module['label'] }}</a>
        @endforeach
    </div>
</div>

@include('partials.access-management.roles-tabs', [
    'access' => $access,
    'section' => 'roles',
    'rolesCount' => $rolesCount,
    'categoriesCount' => $categoriesCount,
])

@if ($errors->has('role'))
    <p class="tich-text tich-mb-4" style="color: #c0392b;">{{ $errors->first('role') }}</p>
@endif

<div class="tich-card tich-table-panel">
    <table class="tich-admin-table">
        <thead>
            <tr>
                <th>Role</th>
                <th>Module</th>
                <th>Category</th>
                <th>Type</th>
                <th>Permissions</th>
                <th>Users</th>
                <th style="width: 8rem;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($roles as $roleItem)
                <tr>
                    <td>
                        <strong>{{ $roleItem->display_name }}</strong>
                        <p class="tich-caption">{{ $roleItem->role_name }}</p>
                    </td>
                    <td>{{ app(\App\Services\ModuleRoleCatalogService::class)->moduleLabel($roleItem->module_key) }}</td>
                    <td>{{ $categoryLabels[$roleItem->role_category] ?? ucfirst(str_replace('_', ' ', $roleItem->role_category)) }}</td>
                    <td>{{ $roleItem->is_system_role ? 'Predefined' : 'Custom' }}</td>
                    <td>{{ $roleItem->permissions_count }}</td>
                    <td>{{ $roleItem->users_count }}</td>
                    <td style="white-space: nowrap;">
                        <button
                            type="button"
                            class="tich-squircle-btn role-permissions-trigger"
                            title="Manage permissions"
                            aria-label="Manage permissions for {{ $roleItem->role_name }}"
                            data-open-modal="role-permissions-modal"
                            data-role-id="{{ $roleItem->id }}"
                            data-role-name="{{ $roleItem->display_name }}"
                            data-permissions-url="{{ $access->route('roles.permissions.update', $roleItem) }}"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="tich-squircle-btn role-edit-trigger"
                            title="Edit role"
                            aria-label="Edit {{ $roleItem->role_name }}"
                            data-open-modal="role-edit-modal"
                            data-update-url="{{ $access->route('roles.update', $roleItem) }}"
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
                            <form method="POST" action="{{ $access->route('roles.destroy', $roleItem) }}" style="display: inline;" onsubmit="return confirm('Delete role {{ $roleItem->role_name }}?');">
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
                <tr><td colspan="7" class="tich-table-empty">No roles defined.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="role-create-modal" class="tich-modal{{ $openCreateModal ? ' is-open' : '' }}" aria-hidden="{{ $openCreateModal ? 'false' : 'true' }}" role="dialog" aria-modal="true">
    <div class="tich-modal__backdrop" data-close-modal="role-create-modal"></div>
    <div class="tich-modal__dialog">
        <header class="tich-modal__header">
            <h2 class="tich-h3" style="margin: 0;">Add role</h2>
            <button type="button" class="tich-modal__close" data-close-modal="role-create-modal" aria-label="Close">&times;</button>
        </header>
        <form method="POST" action="{{ $access->route('roles.store') }}" class="tich-modal__body">
            @csrf
            <input type="hidden" name="_form" value="create">
            @include('admin.partials.role-form-fields', [
                'categories' => $categories,
                'role' => null,
                'formContext' => 'create',
                'fieldIdPrefix' => 'role-create-',
                'moduleOptions' => $moduleOptions,
                'selectedModuleKey' => $selectedModule !== '' && $selectedModule !== $institutionKey ? $selectedModule : '',
            ])
            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="role-create-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Create role</button>
            </footer>
        </form>
    </div>
</div>

<div id="role-edit-modal" class="tich-modal{{ $openEditRoleId ? ' is-open' : '' }}" aria-hidden="{{ $openEditRoleId ? 'false' : 'true' }}" role="dialog" aria-modal="true">
    <div class="tich-modal__backdrop" data-close-modal="role-edit-modal"></div>
    <div class="tich-modal__dialog">
        <header class="tich-modal__header">
            <h2 class="tich-h3" style="margin: 0;">Edit role</h2>
            <button type="button" class="tich-modal__close" data-close-modal="role-edit-modal" aria-label="Close">&times;</button>
        </header>
        <form id="role-edit-form" method="POST" action="{{ $editRole ? $access->route('roles.update', $editRole) : '#' }}" class="tich-modal__body">
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

@include('admin.partials.role-permissions-modal', [
    'permissionMatrices' => $permissionMatrices,
    'categoryLabels' => config('tich-module-roles.permission_categories', []),
])

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

            var defaultModule = @json($selectedModule !== '' && $selectedModule !== $institutionKey ? $selectedModule : '');

            setField('role-create-role_name', '');
            setField('role-create-display_name', '');
            setField('role-create-role_category', '');
            setField('role-create-module_key', defaultModule);
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
