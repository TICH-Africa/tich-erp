@extends('layouts.admin')

@section('title', 'Departments')

@section('admin-content')
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: start; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h1 class="tich-h1" style="font-size: 2rem;">Departments</h1>
            <p class="tich-text tich-mt-2" style="margin-bottom: 0;">
                Administrative units sit under department groups. Academic departments (courses/programs) sit under <strong>Academics</strong>.
            </p>
        </div>
        <button type="button" class="tich-btn tich-btn-primary" data-open-modal="department-create-modal">
            Add department
        </button>
    </div>

    @if (session('status'))
        <p class="tich-text tich-mb-4" style="color: var(--tich-green);">{{ session('status') }}</p>
    @endif

    <div class="tich-card" style="overflow-x: auto;">
        <h2 class="tich-h3">All departments</h2>

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
        'open' => $errors->any() && ! old('_method'),
    ])
@endsection
