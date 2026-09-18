@props([
    'campuses',
    'departmentGroups',
    'parentDepartments',
    'deptCategories',
    'department' => null,
    'fieldIdPrefix' => '',
    'excludeDepartmentId' => null,
    'moduleCatalog' => [],
    'assignedModules' => [],
])

<div class="uf-form-grid-2">
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}dept_code" @endif>Department code</label>
        <input
            type="text"
            name="dept_code"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}dept_code" @endif
            value="{{ old('dept_code', $department->dept_code ?? '') }}"
            required
        >
    </div>
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}dept_name" @endif>Department name</label>
        <input
            type="text"
            name="dept_name"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}dept_name" @endif
            value="{{ old('dept_name', $department->dept_name ?? '') }}"
            required
        >
    </div>
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}dept_category" @endif>Category</label>
        <select name="dept_category" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}dept_category" @endif required>
            @foreach ($deptCategories as $value => $label)
                <option value="{{ $value }}" @selected(old('dept_category', $department->dept_category ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}department_group_id" @endif>Department group</label>
        <select name="department_group_id" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}department_group_id" @endif>
            <option value="">None</option>
            @foreach ($departmentGroups as $group)
                <option value="{{ $group->id }}" @selected(old('department_group_id', $department->department_group_id ?? '') == $group->id)>{{ $group->group_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}parent_dept_id" @endif>Parent department</label>
        <select name="parent_dept_id" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}parent_dept_id" @endif>
            <option value="">None (top level in group)</option>
            @foreach ($parentDepartments as $parent)
                @if ($excludeDepartmentId && $parent->id == $excludeDepartmentId)
                    @continue
                @endif
                <option value="{{ $parent->id }}" @selected(old('parent_dept_id', $department->parent_dept_id ?? '') == $parent->id)>{{ $parent->dept_name }}</option>
            @endforeach
        </select>
        <span class="uf-hint">For academic departments that offer programmes, choose a parent department that has the Academics module (typically <em>Academics</em>).</span>
    </div>
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}campus_id" @endif>Campus</label>
        <select name="campus_id" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}campus_id" @endif>
            <option value="">Institution-wide</option>
            @foreach ($campuses as $campus)
                <option value="{{ $campus->id }}" @selected(old('campus_id', $department->campus_id ?? '') == $campus->id)>{{ $campus->campus_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}display_order" @endif>Display order</label>
        <input
            type="number"
            name="display_order"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}display_order" @endif
            value="{{ old('display_order', $department->display_order ?? 0) }}"
            min="0"
        >
    </div>
    @if ($fieldIdPrefix)
        <div class="uf-field" style="flex-direction: row; align-items: center; gap: 0.5rem;">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                id="{{ $fieldIdPrefix }}is_active"
                @checked(old('is_active', $department->is_active ?? true))
            >
            <label for="{{ $fieldIdPrefix }}is_active" style="font-weight: 400;">Active</label>
        </div>
    @endif
</div>

@if ($moduleCatalog !== [])
    @include('admin.partials.department-module-fields', [
        'moduleCatalog' => $moduleCatalog,
        'assignedModules' => $assignedModules,
        'fieldIdPrefix' => $fieldIdPrefix,
        'selectedCategory' => old('dept_category', $department->dept_category ?? ''),
    ])
@else
    <div class="uf-field">
        <span class="uf-hint">Platform module catalog is missing. Ensure <code>config/tich-department-modules.php</code> is deployed.</span>
    </div>
@endif
