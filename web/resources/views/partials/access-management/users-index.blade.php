<x-page-toolbar title="Users &amp; access" meta="Employee roles, departments, and module access" />

<div class="tich-tabs tich-mb-8">
    <div class="tich-tabs__nav" style="justify-content: space-between; align-items: center;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <a
                href="{{ $access->route('users.index', ['audience' => 'staff']) }}"
                class="tich-tabs__btn{{ $audience === 'staff' ? ' is-active' : '' }}"
            >
                Employees &amp; staff
                <span class="tich-caption">({{ $staffCount }})</span>
            </a>
            <a
                href="{{ $access->route('users.index', ['audience' => 'students']) }}"
                class="tich-tabs__btn{{ $audience === 'students' ? ' is-active' : '' }}"
            >
                Students
                <span class="tich-caption">({{ $studentCount }})</span>
            </a>
        </div>
        @if ($audience === 'staff')
            <a href="{{ $access->route('roles.index') }}" class="tich-btn tich-btn-secondary">Manage roles</a>
        @endif
    </div>
</div>

@if ($audience === 'staff')
    <p class="tich-text tich-mb-4">
        Click the edit icon to assign departments and modules. Each employee can hold <strong>multiple roles</strong> - use “Add another role” in the popup.
    </p>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Account type</th>
                    <th>Roles &amp; departments</th>
                    <th>Staff department</th>
                    <th>Extra modules</th>
                    <th style="width: 4rem;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $extraModules = $user->permissions
                            ->filter(fn ($permission) => $moduleLabelsBySlug->has($permission->slug))
                            ->values();

                        $assignmentsPayload = $user->roles->map(fn ($role) => [
                            'role_id' => $role->id,
                            'department_id' => $role->pivot->department_id ? (int) $role->pivot->department_id : null,
                            'campus_id' => $role->pivot->campus_id ? (int) $role->pivot->campus_id : null,
                        ])->values()->all();

                        $grantsPayload = $extraModules->map(function ($permission) use ($slugToPermission) {
                            return [
                                'permission' => $slugToPermission[$permission->slug] ?? $permission->slug,
                                'department_id' => $permission->pivot->department_id ? (int) $permission->pivot->department_id : null,
                                'campus_id' => $permission->pivot->campus_id ? (int) $permission->pivot->campus_id : null,
                            ];
                        })->values()->all();
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $user->displayName() }}</strong><br>
                            <span class="tich-caption">{{ $user->email }}</span>
                        </td>
                        <td>{{ ucfirst($user->user_type) }}</td>
                        <td>
                            @forelse ($user->roles as $role)
                                {{ $role->role_name }}
                                @if ($role->pivot->department_id)
                                    <span class="tich-caption">· {{ $departmentNames[$role->pivot->department_id] ?? 'Dept #'.$role->pivot->department_id }}</span>
                                @else
                                    <span class="tich-caption">· Institution-wide</span>
                                @endif
                                @if (!$loop->last)<br>@endif
                            @empty
                                <span class="tich-caption">Not assigned</span>
                            @endforelse
                        </td>
                        <td>
                            @if ($user->staff?->department)
                                {{ $user->staff->department->dept_name }}
                            @else
                                <span class="tich-caption">Not linked</span>
                            @endif
                        </td>
                        <td>
                            @forelse ($extraModules as $permission)
                                {{ $moduleLabelsBySlug[$permission->slug] }}
                                @if ($permission->pivot->department_id)
                                    <span class="tich-caption">· {{ $departmentNames[$permission->pivot->department_id] ?? 'Dept #'.$permission->pivot->department_id }}</span>
                                @else
                                    <span class="tich-caption">· Institution-wide</span>
                                @endif
                                @if (!$loop->last)<br>@endif
                            @empty
                                <span class="tich-caption">None</span>
                            @endforelse
                        </td>
                        <td>
                            <button
                                type="button"
                                class="tich-squircle-btn staff-access-trigger"
                                title="Assign department &amp; modules"
                                aria-label="Assign access for {{ $user->displayName() }}"
                                data-open-modal="staff-access-modal"
                                data-update-url="{{ $access->route('users.update', $user) }}"
                                data-user-id="{{ $user->id }}"
                                data-display-name="{{ $user->displayName() }}"
                                data-email="{{ $user->email }}"
                                data-assignments="{{ json_encode($assignmentsPayload) }}"
                                data-permission-grants="{{ json_encode($grantsPayload) }}"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="tich-table-empty">No staff accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="tich-mt-4">{{ $users->links() }}</div>
    </div>

    @include('admin.partials.staff-access-modal', [
        'access' => $access,
        'roles' => $roles,
        'roleNamesById' => $roleNamesById,
        'campuses' => $campuses,
        'departments' => $departments,
        'assignableModules' => $assignableModules,
        'moduleCatalog' => $moduleCatalog,
        'departmentModuleAssignments' => $departmentModuleAssignments,
        'departmentPermissionMap' => $departmentPermissionMap,
        'openUserId' => $openStaffAccessUserId,
    ])

    @include('admin.partials.tich-modal-assets')
@else
    <p class="tich-text tich-mb-4">
        Student accounts are created through admissions and enrolment. Full records live in the Student Information System.
    </p>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Programme</th>
                    <th>Role</th>
                    <th>Portal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $primaryRoleId = $user->roles->first()?->id ?? '';
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $user->displayName() }}</strong><br>
                            <span class="tich-caption">{{ $user->email }}</span>
                        </td>
                        <td>
                            @if ($user->student?->program)
                                {{ $user->student->program->program_code }} · {{ $user->student->program->program_name }}
                            @else
                                <span class="tich-caption">No enrolment linked</span>
                            @endif
                        </td>
                        <td>
                            @forelse ($user->roles as $role)
                                {{ $role->role_name }}@if (!$loop->last), @endif
                            @empty
                                <span class="tich-caption">Student (default)</span>
                            @endforelse
                        </td>
                        <td>
                            @if ($user->student_id || $user->student)
                                <span class="tich-caption">Active</span>
                            @else
                                <span class="tich-caption">No student record</span>
                            @endif
                        </td>
                        <td>
                            <button
                                type="button"
                                class="tich-link student-access-trigger"
                                data-open-modal="student-access-modal"
                                data-update-url="{{ $access->route('users.update', $user) }}"
                                data-display-name="{{ $user->displayName() }}"
                                data-email="{{ $user->email }}"
                                data-role-id="{{ $primaryRoleId }}"
                            >
                                Configure role
                            </button>
                            @if ($user->student)
                                · <a href="{{ route('sis.students.show', $user->student) }}" class="tich-link">SIS record</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="tich-table-empty">No student accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="tich-mt-4">{{ $users->links() }}</div>
    </div>

    @include('admin.partials.student-access-modal', [
        'access' => $access,
        'studentRoles' => $studentRoles,
        'openUserId' => $openStudentAccessUserId,
    ])

    @include('admin.partials.tich-modal-assets')
@endif
