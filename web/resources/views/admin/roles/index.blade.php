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
        <button type="button" class="tich-btn tich-btn-primary" data-open-modal="role-create-modal">
            Add role
        </button>
    </div>

    @if (session('status'))
        <p class="tich-text tich-mb-4" style="color: var(--tich-green);">{{ session('status') }}</p>
    @endif

    @if ($errors->has('role'))
        <p class="tich-text tich-mb-4" style="color: #c0392b;">{{ $errors->first('role') }}</p>
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
                @forelse ($roles as $role)
                    <tr>
                        <td><strong>{{ $role->role_name }}</strong></td>
                        <td>{{ $role->display_name }}</td>
                        <td>{{ $categories[$role->role_category] ?? ucfirst($role->role_category) }}</td>
                        <td>{{ $role->is_system_role ? 'System' : 'Custom' }}</td>
                        <td>{{ $role->users_count }}</td>
                        <td style="white-space: nowrap;">
                            <button
                                type="button"
                                class="tich-squircle-btn role-edit-trigger"
                                title="Edit role"
                                aria-label="Edit {{ $role->role_name }}"
                                data-open-modal="role-edit-modal"
                                data-update-url="{{ route('admin.roles.update', $role) }}"
                                data-role-id="{{ $role->id }}"
                                data-role-name="{{ $role->role_name }}"
                                data-display-name="{{ $role->display_name }}"
                                data-role-category="{{ $role->role_category }}"
                                data-description="{{ $role->description }}"
                                data-is-system="{{ $role->is_system_role ? '1' : '0' }}"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>
                            @unless ($role->is_system_role)
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" style="display: inline;" onsubmit="return confirm('Delete role {{ $role->role_name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tich-squircle-btn" title="Delete role" aria-label="Delete {{ $role->role_name }}" style="color: #c0392b;">
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
                @include('admin.partials.role-form-fields', ['categories' => $categories])
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
                ])
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="role-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')

    <script>
    (function () {
        var form = document.getElementById('role-edit-form');
        if (!form) return;

        function setField(id, value) {
            var field = document.getElementById(id);
            if (field) field.value = value ?? '';
        }

        document.querySelectorAll('.role-edit-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                form.action = trigger.getAttribute('data-update-url') || '#';
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
