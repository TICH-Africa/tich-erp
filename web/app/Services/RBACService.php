<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RBACService
{
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

    public function hasRole(User $user, string $roleName): bool
    {
        return DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $user->id)
            ->where('r.role_name', $roleName)
            ->where(function ($query) use ($user) {
                $query->whereNull('ur.campus_id')
                    ->orWhere('ur.campus_id', $user->staff?->department?->campus_id)
                    ->orWhere('ur.campus_id', $user->student?->enrollment_campus_id);
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('ur.department_id')
                    ->orWhere('ur.department_id', $user->staff?->department_id);
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

        if ($this->hasRole($user, 'HOD') && $user->staff?->department_id === $departmentId) {
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
            ->select('r.role_name', 'r.role_category', 'ur.campus_id', 'ur.department_id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
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
        $hasDirectPermission = DB::table('user_permissions as up')
            ->join('permissions as p', 'up.permission_id', '=', 'p.id')
            ->where('up.user_id', $user->id)
            ->where('p.slug', $slug)
            ->where(function ($query) use ($user) {
                $query->whereNull('up.campus_id')
                    ->orWhere('up.campus_id', $user->staff?->department?->campus_id)
                    ->orWhere('up.campus_id', $user->student?->enrollment_campus_id);
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('up.department_id')
                    ->orWhere('up.department_id', $user->staff?->department_id);
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
            ->where(function ($query) use ($user) {
                $query->whereNull('campus_id')
                    ->orWhere('campus_id', $user->staff?->department?->campus_id)
                    ->orWhere('campus_id', $user->student?->enrollment_campus_id);
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('department_id')
                    ->orWhere('department_id', $user->staff?->department_id);
            })
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
