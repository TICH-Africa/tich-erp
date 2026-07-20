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
            ->with(['roles:id,role_name'])
            ->where('is_active', 1)
            ->orderBy('username')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user): View
    {
        $user->load(['roles', 'permissions']);

        $assignedRole = $user->roles->first();
        $rolePivot = $assignedRole?->pivot;

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
            'assignedRoleId' => $assignedRole?->id,
            'assignedCampusId' => $rolePivot?->campus_id,
            'assignedDepartmentId' => $rolePivot?->department_id,
            'modulePermissions' => $modulePermissions,
            'effectiveModules' => $this->dashboardService->modulesForUser($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string'],
        ]);

        $this->rbacService->syncUserAccess(
            $user,
            (int) $validated['role_id'],
            $validated['campus_id'] ?? null,
            $validated['department_id'] ?? null,
            $validated['modules'] ?? [],
            $request->user()->id
        );

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'User access updated successfully.');
    }
}
