<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RBACService
{
    // Check if user has specific permission (direct or via role)
    public function hasPermission(User $user, string $permission): bool
    {
        // Check direct user permissions with proper joins
        $hasDirectPermission = DB::table('user_permissions as up')
            ->join('permissions as p', 'up.permission_id', '=', 'p.id')
            ->where('up.user_id', $user->id)
            ->where('p.slug', $permission)
            ->where(function ($query) use ($user) {
                // Check campus/department scope
                $query->whereNull('up.campus_id')
                    ->orWhere('up.campus_id', $user->staff?->department?->campus_id)
                    ->orWhere('up.campus_id', $user->student?->enrollment_campus_id ?? null);
            })
            ->where(function ($query) use ($user) {
                // Check department scope
                $query->whereNull('up.department_id')
                    ->orWhere('up.department_id', $user->staff?->department_id);
            })
            ->where(function ($query) {
                // Check expiry
                $query->whereNull('up.expires_at')
                    ->orWhere('up.expires_at', '>', now());
            })
            ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        // Check role permissions
        $userRoleIds = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($user) {
                $query->whereNull('campus_id')
                    ->orWhere('campus_id', $user->staff?->department?->campus_id)
                    ->orWhere('campus_id', $user->student?->enrollment_campus_id ?? null);
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

        // Check role permissions with proper joins
        return DB::table('role_permissions as rp')
            ->join('permissions as p', 'rp.permission_id', '=', 'p.id')
            ->whereIn('rp.role_id', $userRoleIds)
            ->where('p.slug', $permission)
            ->exists();
    }

    // Check if user has any of the given permissions
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }
        return false;
    }

    // Check if user has all of the given permissions
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }
        return true;
    }

    // Check if user has specific role
    public function hasRole(User $user, string $roleName): bool
    {
        return DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $user->id)
            ->where('r.role_name', $roleName)
            ->where(function ($query) use ($user) {
                $query->whereNull('ur.campus_id')
                    ->orWhere('ur.campus_id', $user->staff?->department?->campus_id)
                    ->orWhere('ur.campus_id', $user->student?->enrollment_campus_id ?? null);
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

    // Check if user has any of the given roles
    public function hasAnyRole(User $user, array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($user, $roleName)) {
                return true;
            }
        }
        return false;
    }

    // Check if user can access resource in specific campus
    public function canAccessCampus(User $user, int $campusId): bool
    {
        if ($this->hasRole($user, 'Super Admin')) {
            return true;
        }

        return DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('campus_id', $campusId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    // Check if user can access resource in specific department
    public function canAccessDepartment(User $user, int $departmentId): bool
    {
        if ($this->hasRole($user, 'Super Admin')) {
            return true;
        }

        return DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('department_id', $departmentId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    // Check if user can access student record
    public function canAccessStudent(User $user, int $studentId): bool
    {
        if ($this->hasRole($user, 'Super Admin')) {
            return true;
        }

        if ($this->hasRole($user, 'HOD')) {
            $userDepartmentId = $user->staff?->department_id;
            if (!$userDepartmentId) {
                return false;
            }

            return DB::table('students as s')
                ->join('programs as p', 's.program_id', '=', 'p.id')
                ->where('s.id', $studentId)
                ->where('p.department_id', $userDepartmentId)
                ->exists();
        }

        if ($user->user_type === 'student') {
            return $user->student_id === $studentId;
        }

        return false;
    }

    // Check if user can access staff record
    public function canAccessStaff(User $user, int $staffId): bool
    {
        if ($this->hasRole($user, 'Super Admin')) {
            return true;
        }

        if ($this->hasRole($user, 'HR Manager')) {
            return true;
        }

        if ($this->hasRole($user, 'HOD')) {
            $userDepartmentId = $user->staff?->department_id;
            if (!$userDepartmentId) {
                return false;
            }

            return DB::table('staff')
                ->where('id', $staffId)
                ->where('department_id', $userDepartmentId)
                ->exists();
        }

        if ($user->user_type === 'staff') {
            return $user->staff_id === $staffId;
        }

        return false;
    }

    // Check if user can approve requests in hierarchy
    public function canApprove(User $user, string $module, string $level): bool
    {
        $permission = "{$module}.approve.{$level}";

        if ($this->hasPermission($user, $permission)) {
            return true;
        }

        // Hierarchy-based approval mapping
        $approvalHierarchy = [
            'hod' => 'HOD',
            'finance' => 'Finance Manager',
            'ceo' => 'Principal',
            'hr' => 'HR Manager',
        ];

        if (isset($approvalHierarchy[$level]) && $this->hasRole($user, $approvalHierarchy[$level])) {
            return true;
        }

        return false;
    }

    // Get user's effective permissions (direct + role-based)
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

        return Permission::whereIn('id', $allPermissionIds)
            ->select('slug', 'permission_name', 'module', 'category')
            ->get()
            ->toArray();
    }

    // Get user's roles with scope
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
            ->toArray();
    }

    // Assign permission to user
    public function assignPermissionToUser(User $user, int $permissionId, ?int $campusId = null, ?int $departmentId = null, ?int $grantedBy = null): void
    {
        DB::table('user_permissions')->insert([
            'user_id' => $user->id,
            'permission_id' => $permissionId,
            'campus_id' => $campusId,
            'department_id' => $departmentId,
            'granted_at' => now(),
            'granted_by' => $grantedBy
        ]);
    }

    // Assign role to user
    public function assignRoleToUser(User $user, int $roleId, ?int $campusId = null, ?int $departmentId = null, ?int $assignedBy = null): void
    {
        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'campus_id' => $campusId,
            'department_id' => $departmentId,
            'assigned_at' => now(),
            'assigned_by' => $assignedBy
        ]);
    }

    // Revoke permission from user
    public function revokePermissionFromUser(User $user, int $permissionId): void
    {
        DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where('permission_id', $permissionId)
            ->delete();
    }

    // Revoke role from user
    public function revokeRoleFromUser(User $user, int $roleId): void
    {
        DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->delete();
    }
}