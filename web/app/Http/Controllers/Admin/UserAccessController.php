<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\DepartmentModuleService;
use App\Services\RBACService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserAccessController extends Controller
{
    private const STUDENT_ROLES = ['Student', 'Applicant', 'Alumni'];

    private const STAFF_USER_TYPES = ['staff', 'admin', 'external'];

    public function __construct(
        protected RBACService $rbacService,
        protected DepartmentModuleService $departmentModuleService,
    ) {}

    public function index(Request $request): View
    {
        $audience = $this->resolveAudience($request);

        $users = User::query()
            ->with([
                'roles' => fn ($query) => $query->withPivot('department_id', 'campus_id'),
                'permissions' => fn ($query) => $query->withPivot('department_id', 'campus_id'),
                'staff.department:id,dept_name',
                'student.program:id,program_code,program_name',
                'student.applicant:id,first_name,surname',
            ])
            ->where('is_active', 1)
            ->when($audience === 'students', fn ($query) => $query->where('user_type', 'student'))
            ->when($audience === 'staff', fn ($query) => $query->whereIn('user_type', self::STAFF_USER_TYPES))
            ->orderBy('email')
            ->paginate(20)
            ->withQueryString();

        $slugToPermission = $this->rbacService->dashboardModuleSlugMap();
        $moduleLabelsBySlug = $slugToPermission->map(function (string $permissionKey) {
            $module = collect(config('tich-dashboards.modules', []))->firstWhere('permission', $permissionKey);

            return $module['label'] ?? $permissionKey;
        });

        $staffCount = User::query()
            ->where('is_active', 1)
            ->whereIn('user_type', self::STAFF_USER_TYPES)
            ->count();

        $studentCount = User::query()
            ->where('is_active', 1)
            ->where('user_type', 'student')
            ->count();

        $viewData = [
            'users' => $users,
            'audience' => $audience,
            'staffCount' => $staffCount,
            'studentCount' => $studentCount,
            'departmentNames' => Department::query()->pluck('dept_name', 'id'),
            'slugToPermission' => $slugToPermission,
            'moduleLabelsBySlug' => $moduleLabelsBySlug,
            'openStaffAccessUserId' => $audience === 'staff' && old('_method') === 'PUT'
                ? (int) old('edit_user_id')
                : null,
            'openStudentAccessUserId' => $audience === 'students' && old('_method') === 'PUT'
                ? (int) old('edit_user_id')
                : null,
        ];

        if ($audience === 'staff') {
            $departments = Department::query()
                ->main()
                ->where('is_active', 1)
                ->orderBy('dept_name')
                ->get(['id', 'dept_name', 'dept_code', 'campus_id', 'dept_category']);

            $viewData = array_merge($viewData, [
                'roles' => Role::query()->whereNotIn('role_name', self::STUDENT_ROLES)->orderBy('role_name')->get(),
                'roleNamesById' => Role::query()->pluck('role_name', 'id'),
                'campuses' => Campus::query()->where('is_active', 1)->orderBy('campus_name')->get(['id', 'campus_name']),
                'departments' => $departments,
                'assignableModules' => collect(config('tich-dashboards.modules', []))
                    ->reject(fn (array $module) => ($module['key'] ?? '') === 'dashboard')
                    ->unique('permission')
                    ->values(),
                'moduleCatalog' => $this->departmentModuleService->catalog(),
                'departmentModuleAssignments' => $this->departmentModuleService->assignedModulesByDepartmentIds(
                    $departments->pluck('id')->all()
                ),
                'departmentPermissionMap' => $this->departmentModuleService->dashboardPermissionsByDepartmentIds(
                    $departments->pluck('id')->all()
                ),
            ]);
        }

        if ($audience === 'students') {
            $viewData['studentRoles'] = Role::query()->whereIn('role_name', self::STUDENT_ROLES)->orderBy('role_name')->get();
        }

        return view('admin.users.index', $viewData);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $audience = $this->resolveAudience($request, $user);

        if ($audience === 'students') {
            return $this->updateStudentAccess($request, $user);
        }

        return $this->updateStaffAccess($request, $user);
    }

    private function updateStudentAccess(Request $request, User $user): RedirectResponse
    {
        $request->merge(['edit_user_id' => $user->id, 'audience' => 'students']);

        $validated = $this->validateRequest($request, [
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.role_id' => ['nullable', 'exists:roles,id'],
            'assignments.*.campus_id' => ['nullable', 'exists:campuses,id'],
            'assignments.*.department_id' => ['nullable', 'exists:departments,id'],
        ], route('admin.users.index', ['audience' => 'students']));

        $studentRoleIds = Role::query()->whereIn('role_name', self::STUDENT_ROLES)->pluck('id')->all();

        $assignments = collect($validated['assignments'] ?? [])
            ->filter(fn (array $row) => ! empty($row['role_id']))
            ->map(fn (array $row) => [
                'role_id' => (int) $row['role_id'],
                'campus_id' => ! empty($row['campus_id']) ? (int) $row['campus_id'] : null,
                'department_id' => ! empty($row['department_id']) ? (int) $row['department_id'] : null,
            ])
            ->filter(fn (array $row) => in_array($row['role_id'], $studentRoleIds, true))
            ->values()
            ->all();

        if ($assignments === []) {
            return redirect()
                ->route('admin.users.index', ['audience' => 'students'])
                ->withInput()
                ->withErrors(['assignments' => 'Assign at least one student role.']);
        }

        $this->rbacService->syncUserAccess($user, $assignments, [], $request->user()->id);

        return redirect()
            ->route('admin.users.index', ['audience' => 'students'])
            ->with('status', 'Student access updated successfully.');
    }

    private function updateStaffAccess(Request $request, User $user): RedirectResponse
    {
        $request->merge(['edit_user_id' => $user->id, 'audience' => 'staff']);

        $validated = $this->validateRequest($request, [
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.role_id' => ['nullable', 'exists:roles,id'],
            'assignments.*.campus_id' => ['nullable', 'exists:campuses,id'],
            'assignments.*.department_id' => ['nullable', $this->mainDepartmentExistsRule()],
            'permission_grants' => ['nullable', 'array'],
            'permission_grants.*.permission' => ['nullable', 'string'],
            'permission_grants.*.campus_id' => ['nullable', 'exists:campuses,id'],
            'permission_grants.*.department_id' => ['nullable', $this->mainDepartmentExistsRule()],
        ], route('admin.users.index', ['audience' => 'staff']));

        $staffRoleIds = Role::query()->whereNotIn('role_name', self::STUDENT_ROLES)->pluck('id')->all();
        $roleNamesById = Role::query()->pluck('role_name', 'id');

        $assignments = collect($validated['assignments'] ?? [])
            ->filter(fn (array $row) => ! empty($row['role_id']))
            ->map(fn (array $row) => [
                'role_id' => (int) $row['role_id'],
                'campus_id' => ! empty($row['campus_id']) ? (int) $row['campus_id'] : null,
                'department_id' => ! empty($row['department_id']) ? (int) $row['department_id'] : null,
            ])
            ->filter(fn (array $row) => in_array($row['role_id'], $staffRoleIds, true))
            ->values()
            ->all();

        if ($assignments === []) {
            return redirect()
                ->route('admin.users.index', ['audience' => 'staff'])
                ->withInput()
                ->withErrors(['assignments' => 'Select a role and department for this employee.']);
        }

        foreach ($assignments as $index => $assignment) {
            $roleName = $roleNamesById[$assignment['role_id']] ?? null;

            if ($roleName && ! $this->rbacService->roleAllowsInstitutionWideAssignment($roleName) && empty($assignment['department_id'])) {
                return redirect()
                    ->route('admin.users.index', ['audience' => 'staff'])
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
                return redirect()
                    ->route('admin.users.index', ['audience' => 'staff'])
                    ->withInput()
                    ->withErrors([
                        "permission_grants.{$index}.department_id" => 'Select which department this module access applies to.',
                    ]);
            }

            if (! empty($grant['department_id'])) {
                $department = Department::query()->find($grant['department_id']);

                if ($department && ! $this->departmentModuleService->departmentSupportsDashboardPermission($department, $grant['permission'])) {
                    return redirect()
                        ->route('admin.users.index', ['audience' => 'staff'])
                        ->withInput()
                        ->withErrors([
                            "permission_grants.{$index}.permission" => 'This module is not enabled for the selected department. Enable it under Admin → Departments first.',
                        ]);
                }
            }
        }

        $this->rbacService->syncUserAccess(
            $user,
            $assignments,
            $permissionGrants,
            $request->user()->id
        );

        return redirect()
            ->route('admin.users.index', ['audience' => 'staff'])
            ->with('status', 'Employee access saved successfully.');
    }

    private function resolveAudience(Request $request, ?User $user = null): string
    {
        $audience = $request->string('audience')->toString();

        if ($user !== null) {
            if ($user->user_type === 'student') {
                return 'students';
            }

            if (in_array($user->user_type, self::STAFF_USER_TYPES, true)) {
                return 'staff';
            }
        }

        return in_array($audience, ['staff', 'students'], true) ? $audience : 'staff';
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function validateRequest(Request $request, array $rules, string $redirectTo): array
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray())
                ->redirectTo($redirectTo);
        }

        return $validator->validated();
    }

    private function mainDepartmentExistsRule(): Exists
    {
        return Rule::exists('departments', 'id')->where(
            fn ($query) => $query->whereNull('parent_dept_id')->where('is_active', 1)
        );
    }
}
