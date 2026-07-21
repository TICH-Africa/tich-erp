<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RBACService
{
    public function __construct(protected AuditService $auditService) {}
    public function resolvePermissionSlug(string $permission): string
    {
        $aliases = config('tich.permission_aliases', []);

        if (isset($aliases[$permission])) {
            return $aliases[$permission];
        }

        return str_replace(['.', ':', '-'], '_', $permission);
    }

    public function hasPermission(User $user, string $permission): bool
    {
        if ($this->hasRole($user, 'Super Admin')) {
            return true;
        }

        $slug = $this->resolvePermissionSlug($permission);

        if ($this->userHasPermissionSlug($user, $slug)) {
            return true;
        }

        // Allow category wildcard e.g. admin_manage_applicants_* via prefix match on module+action
        if (str_contains($slug, '_') && $this->userHasPermissionPrefix($user, $slug)) {
            return true;
        }

        return false;
    }

    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    public function hasRole(User $user, string $roleName, ?int $departmentId = null): bool
    {
        return DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $user->id)
            ->where('r.role_name', $roleName)
            ->when($departmentId !== null, function ($query) use ($departmentId) {
                $query->where(function ($scoped) use ($departmentId) {
                    $scoped->whereNull('ur.department_id')
                        ->orWhere('ur.department_id', $departmentId);
                });
            })
            ->where(function ($query) {
                $query->whereNull('ur.expires_at')
                    ->orWhere('ur.expires_at', '>', now());
            })
            ->exists();
    }

    public function hasAnyRole(User $user, array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($user, $roleName)) {
                return true;
            }
        }

        return false;
    }

    public function hasInstitutionWideAdmissionsAccess(User $user): bool
    {
        if ($this->hasAnyRole($user, ['Super Admin', 'Academic Registrar', 'Admissions Officer', 'CEO'])) {
            return true;
        }

        return $this->hasUnscopedPermission($user, 'admissions.read');
    }

    public function hasUnscopedPermission(User $user, string $permission): bool
    {
        if ($this->hasRole($user, 'Super Admin')) {
            return true;
        }

        $slug = $this->resolvePermissionSlug($permission);

        $hasDirect = DB::table('user_permissions as up')
            ->join('permissions as p', 'up.permission_id', '=', 'p.id')
            ->where('up.user_id', $user->id)
            ->where('p.slug', $slug)
            ->whereNull('up.department_id')
            ->where(function ($query) {
                $query->whereNull('up.expires_at')
                    ->orWhere('up.expires_at', '>', now());
            })
            ->exists();

        if ($hasDirect) {
            return true;
        }

        return DB::table('user_roles as ur')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'ur.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('ur.user_id', $user->id)
            ->where('p.slug', $slug)
            ->whereNull('ur.department_id')
            ->where(function ($query) {
                $query->whereNull('ur.expires_at')
                    ->orWhere('ur.expires_at', '>', now());
            })
            ->exists();
    }

    public function roleLevel(string $roleName): int
    {
        return config("tich.role_hierarchy.{$roleName}", 0);
    }

    public function hasMinimumRole(User $user, string $minimumRole): bool
    {
        $requiredLevel = $this->roleLevel($minimumRole);
        $roles = $this->getUserRoles($user);

        foreach ($roles as $role) {
            if ($this->roleLevel($role['role_name']) >= $requiredLevel) {
                return true;
            }
        }

        return false;
    }

    public function canAccessCampus(User $user, int $campusId): bool
    {
        if ($this->hasAnyRole($user, ['Super Admin', 'CEO', 'Academic Registrar'])) {
            return true;
        }

        return DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($campusId) {
                $query->whereNull('campus_id')
                    ->orWhere('campus_id', $campusId);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function canAccessDepartment(User $user, int $departmentId): bool
    {
        if ($this->hasAnyRole($user, ['Super Admin', 'CEO', 'Academic Registrar', 'Dean', 'HR Manager', 'Finance Manager'])) {
            return true;
        }

        return DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($departmentId) {
                $query->whereNull('department_id')
                    ->orWhere('department_id', $departmentId);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function canApprove(User $user, string $module, string $level): bool
    {
        $permission = "{$module}.approve.{$level}";

        if ($this->hasPermission($user, $permission)) {
            return true;
        }

        $approvalHierarchy = [
            'hod' => 'HOD',
            'finance' => 'Finance Manager',
            'ceo' => 'CEO',
            'registrar' => 'Academic Registrar',
            'hr' => 'HR Manager',
        ];

        return isset($approvalHierarchy[$level])
            && $this->hasRole($user, $approvalHierarchy[$level]);
    }

    public function getUserPermissions(User $user): array
    {
        $directPermissions = DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->pluck('permission_id');

        $userRoleIds = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->pluck('role_id');

        $rolePermissions = DB::table('role_permissions')
            ->whereIn('role_id', $userRoleIds)
            ->pluck('permission_id');

        $allPermissionIds = $directPermissions->merge($rolePermissions)->unique()->values();

        return DB::table('permissions')
            ->whereIn('id', $allPermissionIds)
            ->select('slug', 'permission_name', 'module', 'category')
            ->get()
            ->toArray();
    }

    public function getUserRoles(User $user): array
    {
        return DB::table('user_roles as ur')
            ->where('ur.user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('ur.expires_at')
                    ->orWhere('ur.expires_at', '>', now());
            })
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->leftJoin('departments as d', 'ur.department_id', '=', 'd.id')
            ->leftJoin('campuses as c', 'ur.campus_id', '=', 'c.id')
            ->select(
                'r.role_name',
                'r.role_category',
                'ur.role_id',
                'ur.campus_id',
                'ur.department_id',
                'd.dept_name',
                'c.campus_name',
            )
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * @return list<int>
     */
    public function getUserDepartmentIds(User $user): array
    {
        $fromRoles = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->whereNotNull('department_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->pluck('department_id');

        $fromPermissions = DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->whereNotNull('department_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->pluck('department_id');

        $fromStaff = $user->staff?->department_id
            ? collect([$user->staff->department_id])
            : collect();

        return $fromRoles
            ->merge($fromPermissions)
            ->merge($fromStaff)
            ->unique()
            ->values()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function assignRoleToUser(User $user, int $roleId, ?int $campusId = null, ?int $departmentId = null, ?int $assignedBy = null): void
    {
        DB::table('user_roles')->updateOrInsert(
            [
                'user_id' => $user->id,
                'role_id' => $roleId,
                'campus_id' => $campusId,
                'department_id' => $departmentId,
            ],
            [
                'assigned_at' => now(),
                'assigned_by' => $assignedBy,
            ]
        );

        $roleName = DB::table('roles')->where('id', $roleId)->value('role_name');

        $this->auditService->log(
            'rbac.role.assigned',
            'user_roles',
            "{$user->id}:{$roleId}",
            null,
            [
                'target_user_id' => $user->id,
                'role_id' => $roleId,
                'role_name' => $roleName,
                'campus_id' => $campusId,
                'department_id' => $departmentId,
                'assigned_by' => $assignedBy,
            ],
            null,
            'success',
            $assignedBy
        );
    }

    public function revokeRoleFromUser(User $user, int $roleId, ?int $revokedBy = null): void
    {
        $roleName = DB::table('roles')->where('id', $roleId)->value('role_name');

        DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->delete();

        $this->auditService->log(
            'rbac.role.revoked',
            'user_roles',
            "{$user->id}:{$roleId}",
            ['role_id' => $roleId, 'role_name' => $roleName],
            null,
            null,
            'success',
            $revokedBy
        );
    }

    public function assignPermissionToUser(User $user, int $permissionId, ?int $campusId = null, ?int $departmentId = null, ?int $grantedBy = null): void
    {
        DB::table('user_permissions')->updateOrInsert(
            [
                'user_id' => $user->id,
                'permission_id' => $permissionId,
                'campus_id' => $campusId,
                'department_id' => $departmentId,
            ],
            [
                'granted_at' => now(),
                'granted_by' => $grantedBy,
            ]
        );

        $slug = DB::table('permissions')->where('id', $permissionId)->value('slug');

        $this->auditService->log(
            'rbac.permission.assigned',
            'user_permissions',
            "{$user->id}:{$permissionId}",
            null,
            [
                'target_user_id' => $user->id,
                'permission_id' => $permissionId,
                'permission_slug' => $slug,
                'campus_id' => $campusId,
                'department_id' => $departmentId,
                'granted_by' => $grantedBy,
            ],
            null,
            'success',
            $grantedBy
        );
    }

    public function revokePermissionFromUser(User $user, int $permissionId, ?int $revokedBy = null): void
    {
        $slug = DB::table('permissions')->where('id', $permissionId)->value('slug');

        DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where('permission_id', $permissionId)
            ->delete();

        $this->auditService->log(
            'rbac.permission.revoked',
            'user_permissions',
            "{$user->id}:{$permissionId}",
            ['permission_id' => $permissionId, 'permission_slug' => $slug],
            null,
            null,
            'success',
            $revokedBy
        );
    }

    /**
     * @param  list<array{role_id: int, department_id?: int|null, campus_id?: int|null}>  $assignments
     * @param  list<array{permission: string, department_id?: int|null, campus_id?: int|null}>  $permissionGrants
     */
    public function syncUserAccess(
        User $user,
        array $assignments,
        array $permissionGrants,
        int $assignedBy,
    ): void {
        DB::table('user_roles')->where('user_id', $user->id)->delete();

        foreach ($assignments as $assignment) {
            $this->assignRoleToUser(
                $user,
                (int) $assignment['role_id'],
                ! empty($assignment['campus_id']) ? (int) $assignment['campus_id'] : null,
                ! empty($assignment['department_id']) ? (int) $assignment['department_id'] : null,
                $assignedBy
            );
        }

        $moduleSlugs = $this->dashboardModuleSlugMap();

        $modulePermissionIds = DB::table('permissions')
            ->whereIn('slug', $moduleSlugs->keys()->all())
            ->pluck('id');

        DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->whereIn('permission_id', $modulePermissionIds)
            ->delete();

        foreach ($permissionGrants as $grant) {
            if (empty($grant['permission'])) {
                continue;
            }

            $slug = $this->resolvePermissionSlug($grant['permission']);
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');

            if (! $permissionId) {
                continue;
            }

            $this->assignPermissionToUser(
                $user,
                (int) $permissionId,
                ! empty($grant['campus_id']) ? (int) $grant['campus_id'] : null,
                ! empty($grant['department_id']) ? (int) $grant['department_id'] : null,
                $assignedBy
            );
        }

        $this->auditService->log(
            'rbac.user.access_synced',
            'users',
            $user->id,
            null,
            [
                'assignments' => $assignments,
                'permission_grants' => $permissionGrants,
            ],
            'User platform access updated by administrator',
            'success',
            $assignedBy
        );
    }

    /**
     * @return list<array{permission: string, department_id: ?int, campus_id: ?int, label: string}>
     */
    public function getUserModulePermissionGrants(User $user): array
    {
        $slugToPermission = $this->dashboardModuleSlugMap();
        $slugToLabel = collect(config('tich-dashboards.modules', []))
            ->keyBy('permission')
            ->map(fn (array $module) => $module['label']);

        return DB::table('user_permissions as up')
            ->join('permissions as p', 'up.permission_id', '=', 'p.id')
            ->where('up.user_id', $user->id)
            ->whereIn('p.slug', $slugToPermission->keys()->all())
            ->where(function ($query) {
                $query->whereNull('up.expires_at')
                    ->orWhere('up.expires_at', '>', now());
            })
            ->get(['p.slug', 'up.department_id', 'up.campus_id'])
            ->map(function ($row) use ($slugToPermission, $slugToLabel) {
                $permission = $slugToPermission[$row->slug] ?? $row->slug;

                return [
                    'permission' => $permission,
                    'department_id' => $row->department_id ? (int) $row->department_id : null,
                    'campus_id' => $row->campus_id ? (int) $row->campus_id : null,
                    'label' => $slugToLabel[$permission] ?? $permission,
                ];
            })
            ->values()
            ->all();
    }

    public function permissionRequiresDepartment(string $permissionKey): bool
    {
        $module = collect(config('tich-dashboards.modules', []))
            ->firstWhere('permission', $permissionKey);

        return ($module['scope'] ?? 'department') === 'department';
    }

    public function roleAllowsInstitutionWideAssignment(string $roleName): bool
    {
        return in_array($roleName, config('tich.institution_wide_roles', []), true);
    }

    /**
     * @return \Illuminate\Support\Collection<string, string> slug => permission key
     */
    public function dashboardModuleSlugMap(): \Illuminate\Support\Collection
    {
        return collect(config('tich-dashboards.modules', []))
            ->unique('permission')
            ->mapWithKeys(fn (array $module) => [
                $this->resolvePermissionSlug($module['permission']) => $module['permission'],
            ]);
    }

    public function assignDefaultRole(User $user): void
    {
        $roleName = config("tich.default_roles.{$user->user_type}");

        if (! $roleName) {
            return;
        }

        $roleId = Role::query()->where('role_name', $roleName)->value('id');

        if ($roleId) {
            $this->assignRoleToUser($user, $roleId);
        }
    }

    private function userHasPermissionSlug(User $user, string $slug): bool
    {
        $departmentIds = $this->getUserDepartmentIds($user);

        $hasDirectPermission = DB::table('user_permissions as up')
            ->join('permissions as p', 'up.permission_id', '=', 'p.id')
            ->where('up.user_id', $user->id)
            ->where('p.slug', $slug)
            ->where(function ($query) use ($departmentIds) {
                $query->whereNull('up.department_id');

                if ($departmentIds !== []) {
                    $query->orWhereIn('up.department_id', $departmentIds);
                }
            })
            ->where(function ($query) {
                $query->whereNull('up.expires_at')
                    ->orWhere('up.expires_at', '>', now());
            })
            ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        $userRoleIds = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->pluck('role_id');

        if ($userRoleIds->isEmpty()) {
            return false;
        }

        return DB::table('role_permissions as rp')
            ->join('permissions as p', 'rp.permission_id', '=', 'p.id')
            ->whereIn('rp.role_id', $userRoleIds)
            ->where('p.slug', $slug)
            ->exists();
    }

    private function userHasPermissionPrefix(User $user, string $slug): bool
    {
        $parts = explode('_', $slug);
        if (count($parts) < 3) {
            return false;
        }

        array_pop($parts);
        $prefix = implode('_', $parts).'_';

        $permissions = $this->getUserPermissions($user);

        foreach ($permissions as $permission) {
            if (str_starts_with($permission->slug ?? $permission['slug'], $prefix)) {
                return true;
            }
        }

        return false;
    }
}
