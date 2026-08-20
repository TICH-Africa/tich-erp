@props([
    'access',
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
            action="{{ $openUserId ? $access->route('users.update', $openUserId) : '#' }}"
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
                    <h3 class="tich-h3" style="font-size: 1rem;">Department &amp; role</h3>
                    <p class="tich-caption tich-mb-3">Pick the department first, then choose a role available for that unit (top-level units only - not schools under Academics). Employees can hold multiple roles at once.</p>

                    <div id="staff-assignment-rows" style="display: grid; gap: 0.75rem;"></div>
                    <button type="button" class="tich-btn tich-btn-secondary tich-mt-3" id="staff-add-assignment">+ Add another role</button>
                </section>

                <section>
                    <h3 class="tich-h3" style="font-size: 1rem;">Additional modules</h3>
                    <p class="tich-caption tich-mb-3">Optional access outside their home department - pick the department that owns the module.</p>

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
            <label class="tich-label staff-dept-label">Department <span class="staff-dept-required" style="color: #c0392b;">*</span></label>
            <select name="assignments[__INDEX__][department_id]" class="tich-input staff-dept-select" required>
                <option value="">Select department…</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->dept_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group" style="margin: 0;">
            <label class="tich-label">Role <span style="color: #c0392b;">*</span></label>
            <select name="assignments[__INDEX__][role_id]" class="tich-input staff-role-select" required disabled>
                <option value="">Select department first…</option>
                @foreach ($roles as $role)
                    <option
                        value="{{ $role->id }}"
                        data-role-name="{{ $role->role_name }}"
                        data-module-key="{{ $role->module_key ?? '' }}"
                        hidden
                    >{{ $role->display_name }}@if ($role->module_key) ({{ ucfirst($role->module_key) }})@else (Institution-wide)@endif</option>
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

    function isInstitutionWideRoleOption(option) {
        if (!option || !option.value) {
            return false;
        }

        var moduleKey = option.getAttribute('data-module-key') || '';
        var roleName = option.getAttribute('data-role-name') || '';

        return moduleKey === '' || institutionWideRoles.indexOf(roleName) !== -1;
    }

    function syncRoleOptionsForDepartment(row, preferredRoleId) {
        var deptSelect = row.querySelector('.staff-dept-select');
        var roleSelect = row.querySelector('.staff-role-select');
        if (!deptSelect || !roleSelect) return;

        var departmentId = deptSelect.value;
        var allowedModules = departmentId ? (departmentModuleAssignments[departmentId] || []) : null;
        var placeholder = roleSelect.options[0];
        var currentValue = preferredRoleId || roleSelect.value || '';

        Array.prototype.forEach.call(roleSelect.options, function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            if (!departmentId) {
                option.hidden = true;
                return;
            }

            var moduleKey = option.getAttribute('data-module-key') || '';
            var isInstitutionWide = isInstitutionWideRoleOption(option);
            option.hidden = !isInstitutionWide && allowedModules.indexOf(moduleKey) === -1;
        });

        if (placeholder) {
            placeholder.textContent = departmentId ? 'Select role…' : 'Select department first…';
        }

        roleSelect.disabled = !departmentId;
        roleSelect.required = !!departmentId;

        var selectedStillVisible = false;
        if (currentValue) {
            Array.prototype.forEach.call(roleSelect.options, function (option) {
                if (option.value === String(currentValue) && !option.hidden) {
                    selectedStillVisible = true;
                }
            });
        }

        roleSelect.value = selectedStillVisible ? String(currentValue) : '';
        updateDepartmentRequirementForRole(row);
    }

    function updateDepartmentRequirementForRole(row) {
        var deptSelect = row.querySelector('.staff-dept-select');
        var requiredMark = row.querySelector('.staff-dept-required');
        if (!deptSelect) return;

        // Department-first flow: department stays required for every assignment row.
        if (requiredMark) {
            requiredMark.style.display = '';
        }
        deptSelect.required = true;

        var placeholder = deptSelect.options[0];
        if (placeholder) {
            placeholder.textContent = 'Select department…';
        }
    }

    function bindAssignmentRow(row) {
        var roleSelect = row.querySelector('.staff-role-select');
        var deptSelect = row.querySelector('.staff-dept-select');

        if (deptSelect) {
            deptSelect.addEventListener('change', function () {
                syncRoleOptionsForDepartment(row);
            });
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', function () {
                updateDepartmentRequirementForRole(row);
            });
        }

        syncRoleOptionsForDepartment(row, roleSelect ? roleSelect.value : '');

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

        var preferredRoleId = '';
        if (data) {
            row.querySelector('.staff-dept-select').value = data.department_id || '';
            preferredRoleId = data.role_id || '';
            row.querySelector('[name*="[campus_id]"]').value = data.campus_id || '';
        }

        bindAssignmentRow(row);
        if (preferredRoleId) {
            syncRoleOptionsForDepartment(row, preferredRoleId);
        }
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
        document.getElementById('staff-access-modal-title').textContent = 'Assign access - ' + (trigger.getAttribute('data-display-name') || 'Employee');
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
