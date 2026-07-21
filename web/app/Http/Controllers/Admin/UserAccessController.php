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
            ->with(['roles' => fn ($query) => $query->withPivot('department_id', 'campus_id')])
            ->where('is_active', 1)
            ->orderBy('username')
            ->paginate(20);

        $departmentNames = Department::query()->pluck('dept_name', 'id');

        return view('admin.users.index', compact('users', 'departmentNames'));
    }

    public function edit(User $user): View
    {
        $user->load(['roles', 'permissions']);

        $assignments = $user->roles->map(fn ($role) => [
            'role_id' => $role->id,
            'department_id' => $role->pivot->department_id,
            'campus_id' => $role->pivot->campus_id,
        ])->values()->all();

        $modulePermissions = collect(config('tich-dashboards.modules', []))
            ->map(function ($module) use ($user) {
                return [
                    ...$module,
                    'granted' => $user->hasPermission($module['permission']),
                ];
            });

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::query()->orderBy('role_name')->get(),
            'campuses' => Campus::query()->where('is_active', 1)->orderBy('campus_name')->get(['id', 'campus_name']),
            'departments' => Department::query()->where('is_active', 1)->orderBy('dept_name')->get(['id', 'dept_name', 'campus_id']),
            'assignments' => $assignments,
            'modulePermissions' => $modulePermissions,
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
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string'],
        ]);

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
                ->withErrors(['assignments' => 'Add at least one role assignment with a department.']);
        }

        $this->rbacService->syncUserAccess(
            $user,
            $assignments,
            $validated['modules'] ?? [],
            $request->user()->id
        );

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'User access updated successfully.');
    }
}
