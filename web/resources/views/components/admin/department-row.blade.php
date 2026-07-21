@props(['department', 'campuses', 'departmentGroups', 'parentDepartments', 'deptCategories', 'depth' => 0])

<tr>
    <td style="padding-left: {{ $depth * 1.25 }}rem;">
        @if ($depth > 0)
            <span class="tich-caption">↳</span>
        @endif
        {{ $department->dept_code }}
    </td>
    <td>{{ $department->dept_name }}</td>
    <td>{{ $deptCategories[$department->dept_category] ?? ucfirst($department->dept_category) }}</td>
    <td>{{ $department->parent?->dept_name ?? '—' }}</td>
    <td>{{ $department->campus?->campus_name ?? '—' }}</td>
    <td>{{ $department->is_active ? 'Active' : 'Inactive' }}</td>
    <td>
        <details>
            <summary class="tich-link" style="cursor: pointer;">Edit</summary>
            <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="tich-mt-4" style="min-width: 18rem;">
                @csrf
                @method('PUT')
                <div class="tich-form-group">
                    <label class="tich-label">Code</label>
                    <input type="text" name="dept_code" class="tich-input" value="{{ $department->dept_code }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Name</label>
                    <input type="text" name="dept_name" class="tich-input" value="{{ $department->dept_name }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Category</label>
                    <select name="dept_category" class="tich-input" required>
                        @foreach ($deptCategories as $value => $label)
                            <option value="{{ $value }}" @selected($department->dept_category === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Group</label>
                    <select name="department_group_id" class="tich-input">
                        <option value="">None</option>
                        @foreach ($departmentGroups as $group)
                            <option value="{{ $group->id }}" @selected($department->department_group_id == $group->id)>{{ $group->group_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Parent department</label>
                    <select name="parent_dept_id" class="tich-input">
                        <option value="">None (top level in group)</option>
                        @foreach ($parentDepartments as $parent)
                            @if ($parent->id !== $department->id)
                                <option value="{{ $parent->id }}" @selected($department->parent_dept_id == $parent->id)>{{ $parent->dept_name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Campus</label>
                    <select name="campus_id" class="tich-input">
                        <option value="">Institution-wide</option>
                        @foreach ($campuses as $campus)
                            <option value="{{ $campus->id }}" @selected($department->campus_id == $campus->id)>{{ $campus->campus_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display order</label>
                    <input type="number" name="display_order" class="tich-input" value="{{ $department->display_order ?? 0 }}" min="0">
                </div>
                <label style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="checkbox" name="is_active" value="1" @checked($department->is_active)>
                    <span class="tich-text">Active</span>
                </label>
                <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save</button>
            </form>
        </details>
    </td>
</tr>

@foreach ($department->children ?? [] as $child)
    <x-admin.department-row
        :department="$child"
        :campuses="$campuses"
        :department-groups="$departmentGroups"
        :parent-departments="$parentDepartments"
        :dept-categories="$deptCategories"
        :depth="$depth + 1"
    />
@endforeach
