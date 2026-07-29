@props([
    'roles',
    'roleNamesById',
    'campuses',
    'departments',
    'assignableModules',
    'moduleCatalog',
    'departmentModuleAssignments',
    'departmentPermissionMap',
    'openUserId' => null,
])

@php
    $institutionWideRoles = config('tich.institution_wide_roles', []);
    $moduleLabelsByKey = collect($moduleCatalog)->keyBy('key');
@endphp

<div
    id="staff-access-modal"
    class="tich-modal{{ $openUserId ? ' is-open' : '' }}"
    aria-hidden="{{ $openUserId ? 'false' : 'true' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="staff-access-modal-title"
>
    <div class="tich-modal__backdrop" data-close-modal="staff-access-modal"></div>
    <div class="tich-modal__dialog" style="max-width: 42rem;">
        <header class="tich-modal__header">
            <h2 id="staff-access-modal-title" class="tich-h3" style="margin: 0;">Assign employee access</h2>
            <button type="button" class="tich-modal__close" data-close-modal="staff-access-modal" aria-label="Close">&times;</button>
        </header>

        <form
            id="staff-access-form"
            method="POST"
            action="{{ $openUserId ? route('admin.users.update', $openUserId) : '#' }}"
            class="tich-modal__body"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="audience" value="staff">
            <input type="hidden" name="edit_user_id" id="staff-access-user-id" value="{{ old('edit_user_id') }}">

            <p class="tich-text tich-mb-4" id="staff-access-user-meta">
                @if ($openUserId)
                    Configuring access for user #{{ $openUserId }}
                @endif
            </p>

            @if ($errors->any() && old('audience') === 'staff')
                <div class="tich-modal__errors tich-mb-4">
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li class="tich-text">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="display: grid; gap: 1.25rem;">
                <section>
                    <h3 class="tich-h3" style="font-size: 1rem;">Role &amp; department</h3>
                    <p class="tich-caption tich-mb-3">Assign one or more roles with a main department each (top-level units only — not schools under Academics). Employees can hold multiple roles at once.</p>

                    <div id="staff-assignment-rows" style="display: grid; gap: 0.75rem;"></div>
                    <button type="button" class="tich-btn tich-btn-secondary tich-mt-3" id="staff-add-assignment">+ Add another role</button>
                </section>

                <section>
                    <h3 class="tich-h3" style="font-size: 1rem;">Additional modules</h3>
                    <p class="tich-caption tich-mb-3">Optional access outside their home department — pick the department that owns the module.</p>

                    <div id="staff-grant-rows" style="display: grid; gap: 0.75rem;"></div>
                    <button type="button" class="tich-btn tich-btn-secondary tich-mt-3" id="staff-add-grant">+ Add module access</button>
                </section>
            </div>

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="staff-access-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Save access</button>
            </footer>
        </form>
    </div>
</div>

<template id="staff-assignment-row-template">
    <div class="staff-assignment-row" style="display: grid; gap: 0.65rem; padding: 0.85rem; border: 1px solid var(--tich-border, #e5e7eb); border-radius: 0.5rem;">
        <div class="tich-form-group" style="margin: 0;">
            <label class="tich-label">Role <span style="color: #c0392b;">*</span></label>
            <select name="assignments[__INDEX__][role_id]" class="tich-input staff-role-select" required>
                <option value="">Select role…</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" data-role-name="{{ $role->role_name }}">{{ $role->role_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group" style="margin: 0;">
            <label class="tich-label staff-dept-label">Department <span class="staff-dept-required" style="color: #c0392b;">*</span></label>
            <select name="assignments[__INDEX__][department_id]" class="tich-input staff-dept-select">
                <option value="">Select department…</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->dept_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group" style="margin: 0;">
            <label class="tich-label">Campus (optional)</label>
            <select name="assignments[__INDEX__][campus_id]" class="tich-input">
                <option value="">All campuses</option>
                @foreach ($campuses as $campus)
                    <option value="{{ $campus->id }}">{{ $campus->campus_name }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="tich-link staff-remove-row" style="justify-self: start; font-size: 0.875rem;">Remove</button>
    </div>
</template>

<template id="staff-grant-row-template">
    <div class="staff-grant-row tich-permission-grant-row" style="display: grid; gap: 0.65rem; padding: 0.85rem; border: 1px solid var(--tich-border, #e5e7eb); border-radius: 0.5rem;">
        <div class="tich-form-group" style="margin: 0;">
            <label class="tich-label">Module</label>
            <select name="permission_grants[__INDEX__][permission]" class="tich-input staff-permission-select">
                <option value="">Select module…</option>
                @foreach ($assignableModules as $module)
                    <option value="{{ $module['permission'] }}" data-scope="{{ $module['scope'] ?? 'department' }}">
                        {{ $module['label'] }}
                        @if (($module['scope'] ?? 'department') === 'institution')
                            (institution-wide)
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group" style="margin: 0;">
            <label class="tich-label">Owning department</label>
            <select name="permission_grants[__INDEX__][department_id]" class="tich-input tich-grant-dept-select">
                <option value="">Select department…</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->dept_name }}</option>
                @endforeach
            </select>
            <p class="tich-caption tich-mt-1 tich-dept-module-hint"></p>
        </div>
        <div class="tich-form-group" style="margin: 0;">
            <label class="tich-label">Campus (optional)</label>
            <select name="permission_grants[__INDEX__][campus_id]" class="tich-input">
                <option value="">All campuses</option>
                @foreach ($campuses as $campus)
                    <option value="{{ $campus->id }}">{{ $campus->campus_name }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="tich-link staff-remove-row" style="justify-self: start; font-size: 0.875rem;">Remove</button>
    </div>
</template>

<script>
(function () {
    var institutionWideRoles = @json($institutionWideRoles);
    var departmentPermissionMap = @json($departmentPermissionMap);
    var departmentModuleAssignments = @json($departmentModuleAssignments);
    var moduleCatalog = @json($moduleLabelsByKey);

    var form = document.getElementById('staff-access-form');
    var assignmentContainer = document.getElementById('staff-assignment-rows');
    var grantContainer = document.getElementById('staff-grant-rows');
    var assignmentTemplate = document.getElementById('staff-assignment-row-template');
    var grantTemplate = document.getElementById('staff-grant-row-template');

    if (!form || !assignmentContainer || !grantContainer) {
        return;
    }

    function moduleLabelsForDepartment(departmentId) {
        var keys = departmentModuleAssignments[departmentId] || [];
        return keys.map(function (key) {
            return moduleCatalog[key] ? moduleCatalog[key].label : key;
        });
    }

    function updateDeptHint(row, departmentId) {
        var hint = row.querySelector('.tich-dept-module-hint');
        if (!hint) return;
        if (!departmentId) {
            hint.textContent = '';
            return;
        }
        var labels = moduleLabelsForDepartment(departmentId);
        hint.textContent = labels.length
            ? 'Modules enabled: ' + labels.join(', ')
            : 'No modules assigned to this department yet.';
    }

    function filterPermissionOptions(row) {
        var departmentSelect = row.querySelector('.tich-grant-dept-select');
        var permissionSelect = row.querySelector('.staff-permission-select');
        if (!departmentSelect || !permissionSelect) return;

        var departmentId = departmentSelect.value;
        var allowed = departmentId ? (departmentPermissionMap[departmentId] || []) : null;

        Array.prototype.forEach.call(permissionSelect.options, function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            var isInstitution = option.getAttribute('data-scope') === 'institution';
            option.hidden = allowed !== null && allowed.indexOf(option.value) === -1 && !isInstitution;
        });

        updateDeptHint(row, departmentId);
    }

    function bindGrantRow(row) {
        var departmentSelect = row.querySelector('.tich-grant-dept-select');
        if (departmentSelect) {
            departmentSelect.addEventListener('change', function () {
                filterPermissionOptions(row);
            });
        }
        filterPermissionOptions(row);

        var removeBtn = row.querySelector('.staff-remove-row');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                row.remove();
            });
        }
    }

    function updateRoleDepartmentRequirement(row) {
        var roleSelect = row.querySelector('.staff-role-select');
        var deptSelect = row.querySelector('.staff-dept-select');
        var requiredMark = row.querySelector('.staff-dept-required');
        if (!roleSelect || !deptSelect) return;

        var selectedOption = roleSelect.options[roleSelect.selectedIndex];
        var roleName = selectedOption ? selectedOption.getAttribute('data-role-name') : '';
        var isInstitutionWide = institutionWideRoles.indexOf(roleName) !== -1;

        if (requiredMark) {
            requiredMark.style.display = isInstitutionWide ? 'none' : '';
        }
        deptSelect.required = !isInstitutionWide;

        var placeholder = deptSelect.options[0];
        if (placeholder) {
            placeholder.textContent = isInstitutionWide ? 'Institution-wide' : 'Select department…';
        }
    }

    function bindAssignmentRow(row) {
        var roleSelect = row.querySelector('.staff-role-select');
        if (roleSelect) {
            roleSelect.addEventListener('change', function () {
                updateRoleDepartmentRequirement(row);
            });
            updateRoleDepartmentRequirement(row);
        }

        var removeBtn = row.querySelector('.staff-remove-row');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (assignmentContainer.querySelectorAll('.staff-assignment-row').length <= 1) {
                    return;
                }
                row.remove();
            });
        }
    }

    function addAssignmentRow(data, index) {
        var html = assignmentTemplate.innerHTML.replace(/__INDEX__/g, String(index));
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        var row = wrapper.firstElementChild;
        assignmentContainer.appendChild(row);

        if (data) {
            row.querySelector('.staff-role-select').value = data.role_id || '';
            row.querySelector('.staff-dept-select').value = data.department_id || '';
            row.querySelector('[name*="[campus_id]"]').value = data.campus_id || '';
        }

        bindAssignmentRow(row);
    }

    function addGrantRow(data, index) {
        var html = grantTemplate.innerHTML.replace(/__INDEX__/g, String(index));
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        var row = wrapper.firstElementChild;
        grantContainer.appendChild(row);

        if (data) {
            row.querySelector('.staff-permission-select').value = data.permission || '';
            row.querySelector('.tich-grant-dept-select').value = data.department_id || '';
            row.querySelector('[name*="[campus_id]"]').value = data.campus_id || '';
        }

        bindGrantRow(row);
    }

    function fillForm(assignments, grants) {
        assignmentContainer.innerHTML = '';
        grantContainer.innerHTML = '';

        var assignmentList = assignments && assignments.length ? assignments : [{}];
        assignmentList.forEach(function (row, index) {
            addAssignmentRow(row, index);
        });

        (grants || []).forEach(function (row, index) {
            addGrantRow(row, index);
        });
    }

    function openStaffModal(trigger) {
        form.action = trigger.getAttribute('data-update-url') || '#';
        document.getElementById('staff-access-user-id').value = trigger.getAttribute('data-user-id') || '';
        document.getElementById('staff-access-modal-title').textContent = 'Assign access — ' + (trigger.getAttribute('data-username') || 'Employee');
        document.getElementById('staff-access-user-meta').textContent = trigger.getAttribute('data-email') || '';

        var assignments = [];
        var grants = [];
        try {
            assignments = JSON.parse(trigger.getAttribute('data-assignments') || '[]');
            grants = JSON.parse(trigger.getAttribute('data-permission-grants') || '[]');
        } catch (error) {
            assignments = [];
            grants = [];
        }

        fillForm(assignments, grants);
    }

    document.getElementById('staff-add-assignment').addEventListener('click', function () {
        addAssignmentRow({}, assignmentContainer.querySelectorAll('.staff-assignment-row').length);
    });

    document.getElementById('staff-add-grant').addEventListener('click', function () {
        addGrantRow({}, grantContainer.querySelectorAll('.staff-grant-row').length);
    });

    document.querySelectorAll('.staff-access-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            openStaffModal(trigger);
        });
    });

    @if ($openUserId)
        fillForm(@json(old('assignments', [])), @json(old('permission_grants', [])));
        document.body.style.overflow = 'hidden';
    @endif
})();
</script>
