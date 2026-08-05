@extends('layouts.admin')

@section('title', 'Departments')

@section('admin-content')
    @php
        $openCreateModal = $errors->any() && old('_method') !== 'PUT';
        $openEditDepartmentId = old('_method') === 'PUT' ? (int) old('edit_department_id') : null;
        $editDepartment = $openEditDepartmentId ? $allDepartments->firstWhere('id', $openEditDepartmentId) : null;
    @endphp

    <x-page-toolbar title="Departments" meta="Administrative units and academic departments">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="department-create-modal">
                Add department
            </button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <h2 class="tich-h3">All departments</h2>

        @forelse ($groups as $group)
            <h4 class="tich-h3 tich-mt-6" style="font-size: 1rem; text-transform: uppercase; letter-spacing: 0.04em;">{{ $group->group_name }}</h4>
            <table class="tich-admin-table tich-mt-2">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Modules</th>
                        <th>Parent</th>
                        <th>Campus</th>
                        <th>Status</th>
                        <th style="width: 4rem;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($group->departments as $dept)
                        <x-admin.department-row
                            :department="$dept"
                            :campuses="$campuses"
                            :department-groups="$departmentGroups"
                            :parent-departments="$parentDepartments"
                            :dept-categories="$deptCategories"
                            :module-assignments="$moduleAssignments"
                            :module-catalog="$moduleCatalog"
                        />
                    @empty
                        <tr><td colspan="8" class="tich-caption">No departments in this group.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @empty
            <p class="tich-caption tich-mt-4">No department groups defined yet. <a href="{{ route('admin.department-groups.index') }}" class="tich-link">Create groups first</a>.</p>
        @endforelse

        @if ($ungrouped->isNotEmpty())
            <h4 class="tich-h3 tich-mt-6" style="font-size: 1rem;">Ungrouped</h4>
            <table class="tich-admin-table tich-mt-2">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Modules</th>
                        <th>Parent</th>
                        <th>Campus</th>
                        <th>Status</th>
                        <th style="width: 4rem;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ungrouped as $dept)
                        <x-admin.department-row
                            :department="$dept"
                            :campuses="$campuses"
                            :department-groups="$departmentGroups"
                            :parent-departments="$parentDepartments"
                            :dept-categories="$deptCategories"
                            :module-assignments="$moduleAssignments"
                            :module-catalog="$moduleCatalog"
                        />
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <p class="tich-caption tich-mt-6">
        <a href="{{ route('admin.department-groups.index') }}" class="tich-link">← Department groups</a>
        ·
        <a href="{{ route('admin.programs.index') }}" class="tich-link">Manage programmes & courses →</a>
    </p>

    @include('admin.partials.department-create-modal', [
        'campuses' => $campuses,
        'departmentGroups' => $departmentGroups,
        'parentDepartments' => $parentDepartments,
        'deptCategories' => $deptCategories,
        'moduleCatalog' => $moduleCatalog,
        'open' => $openCreateModal,
    ])

    {{-- Edit modal --}}
    <div
        id="department-edit-modal"
        class="tich-modal{{ $openEditDepartmentId ? ' is-open' : '' }}"
        aria-hidden="{{ $openEditDepartmentId ? 'false' : 'true' }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="department-edit-modal-title"
    >
        <div class="tich-modal__backdrop" data-close-modal="department-edit-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 id="department-edit-modal-title" class="tich-h3" style="margin: 0;">Edit department</h2>
                <button type="button" class="tich-modal__close" data-close-modal="department-edit-modal" aria-label="Close">&times;</button>
            </header>
            <form
                id="department-edit-form"
                method="POST"
                action="{{ $editDepartment ? route('admin.departments.update', $editDepartment) : '#' }}"
                class="tich-modal__body"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_department_id" id="department-edit-id" value="{{ old('edit_department_id') }}">
                @if ($errors->any() && old('_method') === 'PUT')
                    <div class="tich-modal__errors">
                        <ul style="margin: 0; padding-left: 1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li class="tich-text">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @include('admin.partials.department-form-fields', [
                    'campuses' => $campuses,
                    'departmentGroups' => $departmentGroups,
                    'parentDepartments' => $parentDepartments,
                    'deptCategories' => $deptCategories,
                    'department' => $editDepartment,
                    'fieldIdPrefix' => 'department-edit-',
                    'excludeDepartmentId' => $editDepartment?->id,
                    'moduleCatalog' => $moduleCatalog,
                    'assignedModules' => $editDepartment ? ($moduleAssignments[$editDepartment->id] ?? []) : [],
                ])
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="department-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')

    <script>
    (function () {
        var form = document.getElementById('department-edit-form');
        if (!form) {
            return;
        }

        function setFieldValue(id, value) {
            var field = document.getElementById(id);
            if (!field) {
                return;
            }

            if (field.type === 'checkbox') {
                field.checked = value === '1' || value === true || value === 'true';
                return;
            }

            field.value = value ?? '';
        }

        function setModuleCheckboxes(moduleKeys) {
            var keys = [];

            try {
                keys = JSON.parse(moduleKeys || '[]');
            } catch (error) {
                keys = [];
            }

            form.querySelectorAll('.tich-dept-module-checkbox').forEach(function (checkbox) {
                checkbox.checked = keys.indexOf(checkbox.value) !== -1;
            });

            var categoryField = document.getElementById('department-edit-dept_category');
            if (categoryField) {
                categoryField.dispatchEvent(new Event('change'));
            }
        }

        function fillEditForm(trigger) {
            form.action = trigger.getAttribute('data-update-url') || '#';
            setFieldValue('department-edit-id', trigger.getAttribute('data-department-id'));
            setFieldValue('department-edit-dept_code', trigger.getAttribute('data-dept-code'));
            setFieldValue('department-edit-dept_name', trigger.getAttribute('data-dept-name'));
            setFieldValue('department-edit-dept_category', trigger.getAttribute('data-dept-category'));
            setFieldValue('department-edit-department_group_id', trigger.getAttribute('data-department-group-id') || '');
            setFieldValue('department-edit-parent_dept_id', trigger.getAttribute('data-parent-dept-id') || '');
            setFieldValue('department-edit-campus_id', trigger.getAttribute('data-campus-id') || '');
            setFieldValue('department-edit-display_order', trigger.getAttribute('data-display-order') || '0');
            setFieldValue('department-edit-is_active', trigger.getAttribute('data-is-active'));
            setModuleCheckboxes(trigger.getAttribute('data-module-keys'));
        }

        document.querySelectorAll('.department-edit-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                fillEditForm(trigger);
            });
        });

        if (document.querySelector('.tich-modal.is-open')) {
            document.body.style.overflow = 'hidden';
        }
    })();
    </script>
@endsection
