@extends('layouts.admin')

@section('title', 'Configure access - '.$user->username)

@section('admin-content')
    <a href="{{ route('admin.users.index') }}" class="tich-link">&larr; All users</a>

    <h1 class="tich-h1 tich-mt-4" style="font-size: 2rem;">{{ $user->username }}</h1>
    <p class="tich-text">{{ $user->email }} · {{ ucfirst($user->user_type) }}</p>

    @if (session('status'))
        <p class="tich-text tich-mt-4" style="color: var(--tich-green);">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="tich-card tich-mt-4" style="border-color: #c0392b;">
            <ul style="margin: 0; padding-left: 1.25rem;">
                @foreach ($errors->all() as $error)
                    <li class="tich-text">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="tich-mt-8">
        @csrf
        @method('PUT')

        <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
            <article class="tich-card">
                <h2 class="tich-h3">Role assignments</h2>
                <p class="tich-text tich-mb-4">
                    Assign a role per department. Most roles require a department; executive roles may be institution-wide.
                </p>

                @php
                    $assignmentRows = old('assignments', $assignments);
                    if ($assignmentRows === []) {
                        $assignmentRows = [['role_id' => '', 'department_id' => '', 'campus_id' => '']];
                    }
                    $assignmentRows[] = ['role_id' => '', 'department_id' => '', 'campus_id' => ''];
                @endphp

                <div style="display: grid; gap: 1rem;">
                    @foreach ($assignmentRows as $index => $row)
                        @php
                            $selectedRoleId = old("assignments.{$index}.role_id", $row['role_id'] ?? '');
                            $selectedRoleName = $roleNamesById[$selectedRoleId] ?? null;
                            $roleIsInstitutionWide = $selectedRoleName
                                ? in_array($selectedRoleName, config('tich.institution_wide_roles', []), true)
                                : false;
                        @endphp
                        <div style="display: grid; gap: 0.75rem; padding: 1rem; border: 1px solid var(--tich-border, #e5e7eb); border-radius: 0.5rem;">
                            <div class="tich-form-group" style="margin: 0;">
                                <label class="tich-label">Role</label>
                                <select name="assignments[{{ $index }}][role_id]" class="tich-input">
                                    <option value="">Select role…</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected($selectedRoleId == $role->id)>
                                            {{ $role->role_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="tich-form-group" style="margin: 0;">
                                <label class="tich-label">
                                    Department
                                    @unless ($roleIsInstitutionWide)
                                        <span style="color: #c0392b;">*</span>
                                    @endunless
                                </label>
                                <select name="assignments[{{ $index }}][department_id]" class="tich-input">
                                    <option value="">@if ($roleIsInstitutionWide) Institution-wide @else Select department… @endif</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected(old("assignments.{$index}.department_id", $row['department_id'] ?? '') == $department->id)>
                                            {{ $department->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="tich-form-group" style="margin: 0;">
                                <label class="tich-label">Campus scope (optional)</label>
                                <select name="assignments[{{ $index }}][campus_id]" class="tich-input">
                                    <option value="">All campuses</option>
                                    @foreach ($campuses as $campus)
                                        <option value="{{ $campus->id }}" @selected(old("assignments.{$index}.campus_id", $row['campus_id'] ?? '') == $campus->id)>
                                            {{ $campus->campus_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="tich-caption tich-mt-4">Leave the last blank row unused, or fill it to add another assignment on save.</p>
            </article>

            <article class="tich-card">
                <h2 class="tich-h3">Department permissions</h2>
                <p class="tich-text tich-mb-4">
                    Grant dashboard permissions scoped to a department. Admissions and academic modules require a department; admin and audit modules are institution-wide.
                </p>

                @php
                    $grantRows = old('permission_grants', $permissionGrants);
                    if ($grantRows === []) {
                        $grantRows = [['permission' => '', 'department_id' => '', 'campus_id' => '']];
                    }
                    $grantRows[] = ['permission' => '', 'department_id' => '', 'campus_id' => ''];
                @endphp

                <div style="display: grid; gap: 1rem;">
                    @foreach ($grantRows as $index => $grant)
                        @php
                            $selectedPermission = old("permission_grants.{$index}.permission", $grant['permission'] ?? '');
                            $selectedModule = $assignableModules->firstWhere('permission', $selectedPermission);
                            $requiresDepartment = $selectedModule
                                ? ($selectedModule['scope'] ?? 'department') === 'department'
                                : true;
                        @endphp
                        <div style="display: grid; gap: 0.75rem; padding: 1rem; border: 1px solid var(--tich-border, #e5e7eb); border-radius: 0.5rem;">
                            <div class="tich-form-group" style="margin: 0;">
                                <label class="tich-label">Permission</label>
                                <select name="permission_grants[{{ $index }}][permission]" class="tich-input">
                                    <option value="">Select permission…</option>
                                    @foreach ($assignableModules as $module)
                                        <option value="{{ $module['permission'] }}" @selected($selectedPermission === $module['permission'])>
                                            {{ $module['label'] }}
                                            @if (($module['scope'] ?? 'department') === 'institution')
                                                (institution-wide)
                                            @else
                                                (per department)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="tich-form-group" style="margin: 0;">
                                <label class="tich-label">
                                    Department
                                    @if ($requiresDepartment)
                                        <span style="color: #c0392b;">*</span>
                                    @endif
                                </label>
                                <select name="permission_grants[{{ $index }}][department_id]" class="tich-input">
                                    <option value="">
                                        @if ($requiresDepartment)
                                            Select department…
                                        @else
                                            Institution-wide
                                        @endif
                                    </option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected(old("permission_grants.{$index}.department_id", $grant['department_id'] ?? '') == $department->id)>
                                            {{ $department->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="tich-form-group" style="margin: 0;">
                                <label class="tich-label">Campus scope (optional)</label>
                                <select name="permission_grants[{{ $index }}][campus_id]" class="tich-input">
                                    <option value="">All campuses</option>
                                    @foreach ($campuses as $campus)
                                        <option value="{{ $campus->id }}" @selected(old("permission_grants.{$index}.campus_id", $grant['campus_id'] ?? '') == $campus->id)>
                                            {{ $campus->campus_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="tich-caption tich-mt-4">Role permissions from assignments above also apply. Use this section for extra department-scoped access.</p>
            </article>
        </div>

        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Effective dashboard (preview)</h2>
            <ul class="tich-mt-4" style="margin: 0; padding-left: 1.25rem;">
                @forelse ($effectiveModules as $module)
                    <li class="tich-text">{{ $module['label'] }}</li>
                @empty
                    <li class="tich-caption">No modules currently visible - assign roles or department permissions.</li>
                @endforelse
            </ul>

            @if ($permissionGrants !== [])
                <h3 class="tich-h3 tich-mt-6" style="font-size: 1rem;">Current department permissions</h3>
                <ul class="tich-mt-2" style="margin: 0; padding-left: 1.25rem;">
                    @foreach ($permissionGrants as $grant)
                        <li class="tich-text">
                            {{ $grant['label'] }}
                            @if ($grant['department_id'])
                                · {{ $departments->firstWhere('id', $grant['department_id'])?->dept_name ?? 'Department #'.$grant['department_id'] }}
                            @else
                                · Institution-wide
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="tich-caption tich-mt-4">Preview reflects current access before saving changes.</p>
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-mt-6">Save access settings</button>
    </form>
@endsection
