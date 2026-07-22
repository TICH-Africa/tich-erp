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
        <button
            type="button"
            class="tich-squircle-btn department-edit-trigger"
            title="Edit department"
            aria-label="Edit {{ $department->dept_name }}"
            data-open-modal="department-edit-modal"
            data-update-url="{{ route('admin.departments.update', $department) }}"
            data-department-id="{{ $department->id }}"
            data-dept-code="{{ $department->dept_code }}"
            data-dept-name="{{ $department->dept_name }}"
            data-dept-category="{{ $department->dept_category }}"
            data-department-group-id="{{ $department->department_group_id }}"
            data-parent-dept-id="{{ $department->parent_dept_id }}"
            data-campus-id="{{ $department->campus_id }}"
            data-display-order="{{ $department->display_order ?? 0 }}"
            data-is-active="{{ $department->is_active ? '1' : '0' }}"
        >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 20h9"/>
                <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
            </svg>
        </button>
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
