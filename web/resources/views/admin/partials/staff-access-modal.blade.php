@props([
    'access',
    'roles',
    'roleNamesById',
    'campuses',
    'departments',
    'departmentModuleAssignments',
    'academicsHostDepartmentIds' => [],
    'learningDepartmentsByParent' => [],
    'openUserId' => null,
])

@php
    $institutionWideRoles = config('tich.institution_wide_roles', []);
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
        <div class="tich-form-group staff-learning-dept-wrap" style="margin: 0; display: none;">
            <label class="tich-label">Learning / training department <span class="staff-learning-dept-required" style="color: #c0392b;">*</span></label>
            <select name="assignments[__INDEX__][learning_department_id]" class="tich-input staff-learning-dept-select">
                <option value="">Select learning department…</option>
            </select>
            <p class="tich-caption tich-mt-1">Required for Head of Department — choose the school or training unit they lead.</p>
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

<script>
(function () {
    var institutionWideRoles = @json($institutionWideRoles);
    var departmentModuleAssignments = @json($departmentModuleAssignments);
    var academicsHostDepartmentIds = @json($academicsHostDepartmentIds);
    var learningDepartmentsByParent = @json($learningDepartmentsByParent);
    var hodRoleName = 'HOD';

    var form = document.getElementById('staff-access-form');
    var assignmentContainer = document.getElementById('staff-assignment-rows');
    var assignmentTemplate = document.getElementById('staff-assignment-row-template');

    if (!form || !assignmentContainer) {
        return;
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
        updateLearningDepartmentField(row);
    }

    function isAcademicsHostDepartment(departmentId) {
        return academicsHostDepartmentIds.indexOf(Number(departmentId)) !== -1;
    }

    function isHodRoleSelect(roleSelect) {
        if (!roleSelect || !roleSelect.value) {
            return false;
        }

        var option = roleSelect.options[roleSelect.selectedIndex];

        return option && option.getAttribute('data-role-name') === hodRoleName;
    }

    function updateLearningDepartmentField(row, preferredLearningDepartmentId) {
        var wrap = row.querySelector('.staff-learning-dept-wrap');
        var deptSelect = row.querySelector('.staff-dept-select');
        var roleSelect = row.querySelector('.staff-role-select');
        var learningSelect = row.querySelector('.staff-learning-dept-select');

        if (!wrap || !learningSelect || !deptSelect || !roleSelect) {
            return;
        }

        var show = isAcademicsHostDepartment(deptSelect.value) && isHodRoleSelect(roleSelect);
        wrap.style.display = show ? '' : 'none';
        learningSelect.required = show;

        if (!show) {
            learningSelect.value = '';
            return;
        }

        var parentId = String(deptSelect.value);
        var options = learningDepartmentsByParent[parentId] || [];
        var currentValue = preferredLearningDepartmentId || learningSelect.value || '';

        learningSelect.innerHTML = '<option value="">Select learning department…</option>';
        options.forEach(function (department) {
            var option = document.createElement('option');
            option.value = String(department.id);
            option.textContent = department.dept_name;
            learningSelect.appendChild(option);
        });

        var stillValid = options.some(function (department) {
            return String(department.id) === String(currentValue);
        });

        learningSelect.value = stillValid ? String(currentValue) : '';
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
                updateLearningDepartmentField(row);
            });
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', function () {
                updateDepartmentRequirementForRole(row);
                updateLearningDepartmentField(row);
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
        var preferredLearningDepartmentId = '';
        if (data) {
            row.querySelector('.staff-dept-select').value = data.department_id || '';
            preferredRoleId = data.role_id || '';
            preferredLearningDepartmentId = data.learning_department_id || '';
            row.querySelector('[name*="[campus_id]"]').value = data.campus_id || '';
        }

        bindAssignmentRow(row);
        if (preferredRoleId) {
            syncRoleOptionsForDepartment(row, preferredRoleId);
        }
        updateLearningDepartmentField(row, preferredLearningDepartmentId);
    }

    function fillForm(assignments) {
        assignmentContainer.innerHTML = '';

        var assignmentList = assignments && assignments.length ? assignments : [{}];
        assignmentList.forEach(function (row, index) {
            addAssignmentRow(row, index);
        });
    }

    function openStaffModal(trigger) {
        form.action = trigger.getAttribute('data-update-url') || '#';
        document.getElementById('staff-access-user-id').value = trigger.getAttribute('data-user-id') || '';
        document.getElementById('staff-access-modal-title').textContent = 'Assign access - ' + (trigger.getAttribute('data-display-name') || 'Employee');
        document.getElementById('staff-access-user-meta').textContent = trigger.getAttribute('data-email') || '';

        var assignments = [];
        try {
            assignments = JSON.parse(trigger.getAttribute('data-assignments') || '[]');
        } catch (error) {
            assignments = [];
        }

        fillForm(assignments);
    }

    document.getElementById('staff-add-assignment').addEventListener('click', function () {
        addAssignmentRow({}, assignmentContainer.querySelectorAll('.staff-assignment-row').length);
    });

    document.querySelectorAll('.staff-access-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            openStaffModal(trigger);
        });
    });

    @if ($openUserId)
        fillForm(@json(old('assignments', [])));
        document.body.style.overflow = 'hidden';
    @endif
})();
</script>
