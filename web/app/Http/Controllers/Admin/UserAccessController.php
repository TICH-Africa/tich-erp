<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\RBACService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UserAccessController extends Controller
{
    public function __construct(
        protected RBACService $rbacService,
        protected DashboardService $dashboardService,
    ) {}

    public function index(): View
    {
        $users = User::query()
            ->with([
                'roles' => fn ($query) => $query->withPivot('department_id', 'campus_id'),
                'permissions' => fn ($query) => $query->withPivot('department_id', 'campus_id'),
            ])
            ->where('is_active', 1)
            ->orderBy('username')
            ->paginate(20);

        $departmentNames = Department::query()->pluck('dept_name', 'id');
        $slugToPermission = $this->rbacService->dashboardModuleSlugMap();
        $moduleLabelsBySlug = $slugToPermission->map(function (string $permissionKey) {
            $module = collect(config('tich-dashboards.modules', []))->firstWhere('permission', $permissionKey);

            return $module['label'] ?? $permissionKey;
        });

        return view('admin.users.index', compact('users', 'departmentNames', 'moduleLabelsBySlug'));
    }

    public function edit(User $user): View
    {
        $user->load(['roles', 'permissions']);

        $assignments = $user->roles->map(fn ($role) => [
            'role_id' => $role->id,
            'department_id' => $role->pivot->department_id,
            'campus_id' => $role->pivot->campus_id,
        ])->values()->all();

        $permissionGrants = $this->rbacService->getUserModulePermissionGrants($user);

        $assignableModules = collect(config('tich-dashboards.modules', []))
            ->unique('permission')
            ->values();

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::query()->orderBy('role_name')->get(),
            'roleNamesById' => Role::query()->pluck('role_name', 'id'),
            'campuses' => Campus::query()->where('is_active', 1)->orderBy('campus_name')->get(['id', 'campus_name']),
            'departments' => Department::query()->where('is_active', 1)->orderBy('dept_name')->get(['id', 'dept_name', 'campus_id']),
            'assignments' => $assignments,
            'permissionGrants' => $permissionGrants,
            'assignableModules' => $assignableModules,
            'effectiveModules' => $this->dashboardService->modulesForUser($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.role_id' => ['nullable', 'exists:roles,id'],
            'assignments.*.campus_id' => ['nullable', 'exists:campuses,id'],
            'assignments.*.department_id' => ['nullable', 'exists:departments,id'],
            'permission_grants' => ['nullable', 'array'],
            'permission_grants.*.permission' => ['nullable', 'string'],
            'permission_grants.*.campus_id' => ['nullable', 'exists:campuses,id'],
            'permission_grants.*.department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $roleNamesById = Role::query()->pluck('role_name', 'id');

        $assignments = collect($validated['assignments'] ?? [])
            ->filter(fn (array $row) => ! empty($row['role_id']))
            ->map(fn (array $row) => [
                'role_id' => (int) $row['role_id'],
                'campus_id' => ! empty($row['campus_id']) ? (int) $row['campus_id'] : null,
                'department_id' => ! empty($row['department_id']) ? (int) $row['department_id'] : null,
            ])
            ->values()
            ->all();

        if ($assignments === []) {
            return back()
                ->withInput()
                ->withErrors(['assignments' => 'Add at least one role assignment.']);
        }

        foreach ($assignments as $index => $assignment) {
            $roleName = $roleNamesById[$assignment['role_id']] ?? null;

            if ($roleName && ! $this->rbacService->roleAllowsInstitutionWideAssignment($roleName) && empty($assignment['department_id'])) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "assignments.{$index}.department_id" => "Select a department for the {$roleName} role.",
                    ]);
            }
        }

        $permissionGrants = collect($validated['permission_grants'] ?? [])
            ->filter(fn (array $row) => ! empty($row['permission']))
            ->map(fn (array $row) => [
                'permission' => $row['permission'],
                'campus_id' => ! empty($row['campus_id']) ? (int) $row['campus_id'] : null,
                'department_id' => ! empty($row['department_id']) ? (int) $row['department_id'] : null,
            ])
            ->unique(fn (array $row) => implode(':', [
                $row['permission'],
                $row['department_id'] ?? 'all',
                $row['campus_id'] ?? 'all',
            ]))
            ->values()
            ->all();

        foreach ($permissionGrants as $index => $grant) {
            if ($this->rbacService->permissionRequiresDepartment($grant['permission']) && empty($grant['department_id'])) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "permission_grants.{$index}.department_id" => 'This permission must be assigned to a department.',
                    ]);
            }
        }

        $this->rbacService->syncUserAccess(
            $user,
            $assignments,
            $permissionGrants,
            $request->user()->id
        );

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'User access updated successfully.');
    }
}
