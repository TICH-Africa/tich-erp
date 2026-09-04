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
            @if ($access->prefix === 'ict')
                <a
                    href="{{ $access->route('users.index', ['audience' => 'super_admins']) }}"
                    class="tich-tabs__btn{{ $audience === 'super_admins' ? ' is-active' : '' }}"
                >
                    Super admins
                    <span class="tich-caption">({{ $superAdminCount ?? 0 }})</span>
                </a>
            @endif
        </div>
        @if ($audience === 'staff')
            <a href="{{ $access->route('roles.index') }}" class="tich-btn tich-btn-secondary">Manage roles</a>
        @endif
    </div>
</div>

@if ($audience === 'staff')
    <p class="tich-text tich-mb-4">
        Click the edit icon to assign departments and modules. Pick the <strong>department first</strong>, then a role for that unit. Each employee can hold <strong>multiple roles</strong> - use “Add another role” in the popup.
    </p>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-panel__head">
            <h2 class="tich-table-panel__title">Employees &amp; staff</h2>
            <p class="tich-table-panel__meta">{{ $staffCount }} accounts</p>
        </div>
        <div class="tich-table-wrap">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Account type</th>
                    <th>Roles &amp; departments</th>
                    <th class="tich-admin-table__actions"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $assignmentsPayload = $user->roles->map(function ($role) {
                            $departmentId = $role->pivot->department_id ? (int) $role->pivot->department_id : null;
                            $hubDepartmentId = $departmentId;
                            $learningDepartmentId = null;

                            if ($role->role_name === 'HOD' && $departmentId) {
                                $assignedDepartment = \App\Models\Department::query()->find($departmentId);
                                if ($assignedDepartment?->parent_dept_id) {
                                    $hubDepartmentId = (int) $assignedDepartment->parent_dept_id;
                                    $learningDepartmentId = $departmentId;
                                }
                            }

                            return [
                                'role_id' => $role->id,
                                'department_id' => $hubDepartmentId,
                                'learning_department_id' => $learningDepartmentId,
                                'campus_id' => $role->pivot->campus_id ? (int) $role->pivot->campus_id : null,
                            ];
                        })->values()->all();
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $user->displayName() }}</strong><br>
                            <span class="tich-caption">{{ $user->email }}</span>
                        </td>
                        <td><span class="tich-badge tich-badge--sm">{{ \App\Support\UserType::label($user->user_type) }}</span></td>
                        <td>
                            @forelse ($user->roles as $role)
                                <div style="margin-bottom: {{ $loop->last ? '0' : '0.35rem' }};">
                                    <span class="tich-badge tich-badge--sm tich-badge--info">{{ $role->display_name ?: $role->role_name }}</span>
                                    @if ($role->pivot->department_id)
                                        <span class="tich-caption">{{ $departmentNames[$role->pivot->department_id] ?? 'Dept #'.$role->pivot->department_id }}</span>
                                    @else
                                        <span class="tich-caption">Institution-wide</span>
                                    @endif
                                </div>
                            @empty
                                <span class="tich-caption">Not assigned</span>
                            @endforelse
                        </td>
                        <td class="tich-admin-table__actions">
                            <div class="tich-admin-table__action-group">
                            @if ($access->prefix === 'ict')
                                <a href="{{ $access->route('users.show', $user) }}" class="tich-btn tich-btn-ghost tich-btn--compact">View</a>
                            @endif
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
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="tich-table-empty">No staff accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="tich-mt-4" style="padding: 0 1.25rem 1rem;">{{ $users->links() }}</div>
    </div>

    @include('admin.partials.staff-access-modal', [
        'access' => $access,
        'roles' => $roles,
        'roleNamesById' => $roleNamesById,
        'campuses' => $campuses,
        'departments' => $departments,
        'departmentModuleAssignments' => $departmentModuleAssignments,
        'academicsHostDepartmentIds' => $academicsHostDepartmentIds,
        'learningDepartmentsByParent' => $learningDepartmentsByParent,
        'openUserId' => $openStaffAccessUserId,
    ])

    @include('admin.partials.tich-modal-assets')
@elseif ($audience === 'super_admins')
    <p class="tich-text tich-mb-4">
        Platform super admins have full ERP access and do not use employee HR records. They are managed here for visibility only.
    </p>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Account type</th>
                    <th>Roles</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->displayName() }}</strong><br>
                            <span class="tich-caption">{{ $user->email }}</span>
                        </td>
                        <td>{{ \App\Support\UserType::label($user->user_type) }}</td>
                        <td>
                            @forelse ($user->roles as $role)
                                {{ $role->role_name }}@if (!$loop->last)<br>@endif
                            @empty
                                <span class="tich-caption">Super Admin (by account type)</span>
                            @endforelse
                        </td>
                        <td>
                            <a href="{{ $access->route('users.show', $user) }}" class="tich-btn tich-btn-ghost tich-btn--compact">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="tich-table-empty">No super admin accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="tich-mt-4">{{ $users->links() }}</div>
    </div>
@else
    <p class="tich-text tich-mb-4">
        Student accounts are created through admissions and enrolment. Full records live in the Student Information System.
    </p>

    @php $passwordResetEscalations = $passwordResetEscalations ?? collect(); @endphp
    @if ($passwordResetEscalations->isNotEmpty())
        <div class="tich-card tich-table-panel tich-mb-8">
            <div class="tich-table-panel__head">
                <h2 class="tich-table-panel__title">Open password reset escalations</h2>
                <p class="tich-table-panel__meta">{{ $passwordResetEscalations->count() }} open</p>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Attempts</th>
                            <th>Opened</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($passwordResetEscalations as $escalation)
                            <tr>
                                <td>{{ $escalation->email }}</td>
                                <td>{{ $escalation->attempt_count }}</td>
                                <td class="tich-caption">{{ $escalation->created_at?->format('d M Y H:i') }}</td>
                                <td class="tich-caption">{{ $escalation->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

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
                            ·
                            <details style="display:inline-block;">
                                <summary class="tich-link" style="cursor:pointer;">Reset password</summary>
                                <form method="POST" action="{{ $access->route('users.reset-password', $user) }}" class="tich-form-stack tich-mt-2" style="min-width:16rem;">
                                    @csrf
                                    <input type="password" name="password" class="tich-input" placeholder="New password" required minlength="8">
                                    <input type="password" name="password_confirmation" class="tich-input" placeholder="Confirm" required minlength="8">
                                    <button type="submit" class="tich-btn tich-btn-primary tich-btn--compact">Save</button>
                                </form>
                            </details>
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
