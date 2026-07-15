<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RBACService
{
    /**
     * Check if user has specific permission
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // Check direct user permissions
        $hasDirectPermission = DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->whereHas('permission', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->where(function ($query) use ($user) {
                // Check campus/department scope
                $query->whereNull('campus_id')
                    ->orWhere('campus_id', $user->staff?->department?->campus_id)
                    ->orWhere('campus_id', $user->student?->enrollment_campus_id);
            })
            ->where(function ($query) use ($user) {
                // Check department scope
                $query->whereNull('department_id')
                    ->orWhere('department_id', $user->staff?->department_id);
            })
            ->where(function ($query) {
                // Check expiry
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
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

        return DB::table('role_permissions')
            ->whereIn('role_id', $userRoleIds)
            ->whereHas('permission', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists();
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(User $user, string $roleName): bool
    {
        return DB::table('user_roles')
            ->where('user_id', $user->id)
            ->whereHas('role', function ($query) use ($roleName) {
                $query->where('role_name', $roleName);
            })
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
            ->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(User $user, array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($user, $roleName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user can access resource in specific campus
     */
    public function canAccessCampus(User $user, int $campusId): bool
    {
        // Super Admin can access all campuses
        if ($this->hasRole($user, 'Super Admin')) {
            return true;
        }

        // Check if user has role in this campus
        return DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('campus_id', $campusId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if user can access resource in specific department
     */
    public function canAccessDepartment(User $user, int $departmentId): bool
    {
        // Super Admin can access all departments
        if ($this->hasRole($user, 'Super Admin')) {
            return true;
        }

        // Check if user has role in this department
        return DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('department_id', $departmentId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if user can access student record
     */
    public function canAccessStudent(User $user, int $studentId): bool
    {
        // Super Admin can access all students
        if ($this->hasRole($user, 'Super Admin')) {
            return true;
        }

        // HOD can access students in their department
        if ($this->hasRole($user, 'HOD')) {
            $userDepartmentId = $user->staff?->department_id;
            if (!$userDepartmentId) {
                return false;
            }

            return DB::table('students')
                ->where('id', $studentId)
                ->whereHas('program', function ($query) use ($userDepartmentId) {
                    $query->where('department_id', $userDepartmentId);
                })
                ->exists();
        }

        // Lecturer can access students in their units
        if ($this->hasRole($user, 'Lecturer')) {
            $staffId = $user->staff_id;
            if (!$staffId) {
                return false;
            }

            return DB::table('students')
                ->where('id', $studentId)
                ->whereHas('registeredUnits', function ($query) use ($staffId) {
                    $query->whereHas('unit', function ($unitQuery) use ($staffId) {
                        $unitQuery->where('lecturer_id', $staffId);
                    });
                })
                ->exists();
        }

        // Student can only access their own record
        if ($user->user_type === 'student') {
            return $user->student_id === $studentId;
        }

        return false;
    }

    /**
     * Check if user can access staff record
     */
    public function canAccessStaff(User $user, int $staffId): bool
    {
        // Super Admin can access all staff
        if ($this->hasRole($user, 'Super Admin')) {
            return true;
        }

        // HR Manager can access all staff
        if ($this->hasRole($user, 'HR Manager')) {
            return true;
        }

        // HOD can access staff in their department
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

        // Staff can access their own record
        if ($user->user_type === 'staff') {
            return $user->staff_id === $staffId;
        }

        return false;
    }

    /**
     * Check if user can approve requests in hierarchy
     */
    public function canApprove(User $user, string $module, string $level): bool
    {
        $permission = "{$module}.approve.{$level}";

        // Check if user has approval permission
        if ($this->hasPermission($user, $permission)) {
            return true;
        }

        // Check hierarchy-based approval
        if ($level === 'hod' && $this->hasRole($user, 'HOD')) {
            return true;
        }

        if ($level === 'finance' && $this->hasRole($user, 'Finance Manager')) {
            return true;
        }

        if ($level === 'ceo' && $this->hasRole($user, 'Principal')) {
            return true;
        }

        if ($level === 'hr' && $this->hasRole($user, 'HR Manager')) {
            return true;
        }

        return false;
    }

    /**
     * Get user's effective permissions
     */
    public function getUserPermissions(User $user): array
    {
        // Get direct permissions
        $directPermissions = DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->pluck('permission_id');

        // Get role permissions
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

        // Merge and get unique permissions
        $allPermissionIds = $directPermissions->merge($rolePermissions)->unique();

        return Permission::whereIn('id', $allPermissionIds)
            ->select('slug', 'permission_name', 'module', 'category')
            ->get()
            ->toArray();
    }

    /**
     * Get user's roles with scope
     */
    public function getUserRoles(User $user): array
    {
        return DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->select('roles.role_name', 'roles.role_category', 'user_roles.campus_id', 'user_roles.department_id')
            ->get()
            ->toArray();
    }

    /**
     * Assign permission to user
     */
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

    /**
     * Assign role to user
     */
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

    /**
     * Revoke permission from user
     */
    public function revokePermissionFromUser(User $user, int $permissionId): void
    {
        DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where('permission_id', $permissionId)
            ->delete();
    }

    /**
     * Revoke role from user
     */
    public function revokeRoleFromUser(User $user, int $roleId): void
    {
        DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->delete();
    }
}
