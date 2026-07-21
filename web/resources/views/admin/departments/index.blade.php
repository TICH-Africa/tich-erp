@extends('layouts.admin')

@section('title', 'Departments')

@section('admin-content')
    <h1 class="tich-h1" style="font-size: 2rem;">Departments</h1>
    <p class="tich-text tich-mb-8">
        Administrative units sit under department groups. Learning departments (courses/programs) sit under <strong>Academics</strong>.
    </p>

    @if (session('status'))
        <p class="tich-text tich-mb-4" style="color: var(--tich-green);">{{ session('status') }}</p>
    @endif

    <div class="tich-grid tich-grid--2" style="align-items: start; gap: 2rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Add department</h2>
            <form method="POST" action="{{ route('admin.departments.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Department code</label>
                    <input type="text" name="dept_code" class="tich-input" value="{{ old('dept_code') }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Department name</label>
                    <input type="text" name="dept_name" class="tich-input" value="{{ old('dept_name') }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Category</label>
                    <select name="dept_category" class="tich-input" required>
                        @foreach ($deptCategories as $value => $label)
                            <option value="{{ $value }}" @selected(old('dept_category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Department group</label>
                    <select name="department_group_id" class="tich-input">
                        <option value="">None</option>
                        @foreach ($departmentGroups as $group)
                            <option value="{{ $group->id }}" @selected(old('department_group_id') == $group->id)>{{ $group->group_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Parent department</label>
                    <select name="parent_dept_id" class="tich-input">
                        <option value="">None (top level in group)</option>
                        @foreach ($parentDepartments as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_dept_id') == $parent->id)>{{ $parent->dept_name }}</option>
                        @endforeach
                    </select>
                    <p class="tich-caption tich-mt-1">Set parent to <em>Academics</em> for learning departments that offer programmes.</p>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Campus</label>
                    <select name="campus_id" class="tich-input">
                        <option value="">Institution-wide</option>
                        @foreach ($campuses as $campus)
                            <option value="{{ $campus->id }}" @selected(old('campus_id') == $campus->id)>{{ $campus->campus_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display order</label>
                    <input type="number" name="display_order" class="tich-input" value="{{ old('display_order', 0) }}" min="0">
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Create department</button>
            </form>
        </article>

        <div class="tich-card" style="overflow-x: auto;">
            <h2 class="tich-h3">Department structure</h2>

            @forelse ($groups as $group)
                <h4 class="tich-h3 tich-mt-6" style="font-size: 1rem; text-transform: uppercase; letter-spacing: 0.04em;">{{ $group->group_name }}</h4>
                <table class="tich-admin-table tich-mt-2">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Parent</th>
                            <th>Campus</th>
                            <th>Status</th>
                            <th></th>
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
                            />
                        @empty
                            <tr><td colspan="7" class="tich-caption">No departments in this group.</td></tr>
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
                            <th>Parent</th>
                            <th>Campus</th>
                            <th>Status</th>
                            <th></th>
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
                            />
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <p class="tich-caption tich-mt-6">
        <a href="{{ route('admin.department-groups.index') }}" class="tich-link">← Department groups</a>
        ·
        <a href="{{ route('admin.programs.index') }}" class="tich-link">Manage programmes & courses →</a>
    </p>
@endsection
