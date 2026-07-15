<?php

namespace App\Http\Controllers;

use App\Services\RBACService;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RBACController extends Controller
{
    protected RBACService $rbacService;

    public function __construct(RBACService $rbacService)
    {
        $this->rbacService = $rbacService;
    }

    /**
     * Get current user's permissions
     */
    public function getUserPermissions(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $permissions = $this->rbacService->getUserPermissions($user);

        return response()->json([
            'permissions' => $permissions
        ]);
    }

    /**
     * Get current user's roles
     */
    public function getUserRoles(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $roles = $this->rbacService->getUserRoles($user);

        return response()->json([
            'roles' => $roles
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        $currentUser = Auth::user();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if current user has permission to assign roles
        if (!$this->rbacService->hasPermission($currentUser, 'admin.manage_staff.assign')) {
            return response()->json(['message' => 'You do not have permission to assign roles'], 403);
        }

        $targetUser = User::findOrFail($request->user_id);

        $this->rbacService->assignRoleToUser(
            $targetUser,
            $request->role_id,
            $request->campus_id,
            $request->department_id,
            $currentUser->id
        );

        return response()->json([
            'message' => 'Role assigned successfully',
            'assigned_at' => now()
        ]);
    }

    /**
     * Revoke role from user
     */
    public function revokeRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $currentUser = Auth::user();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if current user has permission to revoke roles
        if (!$this->rbacService->hasPermission($currentUser, 'admin.manage_staff.revoke')) {
            return response()->json(['message' => 'You do not have permission to revoke roles'], 403);
        }

        $targetUser = User::findOrFail($request->user_id);

        $this->rbacService->revokeRoleFromUser($targetUser, $request->role_id);

        return response()->json([
            'message' => 'Role revoked successfully',
            'revoked_at' => now()
        ]);
    }

    /**
     * Assign permission to user
     */
    public function assignPermission(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission_id' => 'required|exists:permissions,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        $currentUser = Auth::user();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if current user has permission to assign permissions
        if (!$this->rbacService->hasPermission($currentUser, 'admin.manage_staff.assign')) {
            return response()->json(['message' => 'You do not have permission to assign permissions'], 403);
        }

        $targetUser = User::findOrFail($request->user_id);

        $this->rbacService->assignPermissionToUser(
            $targetUser,
            $request->permission_id,
            $request->campus_id,
            $request->department_id,
            $currentUser->id
        );

        return response()->json([
            'message' => 'Permission assigned successfully',
            'assigned_at' => now()
        ]);
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission_id' => 'required|exists:permissions,id'
        ]);

        $currentUser = Auth::user();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if current user has permission to revoke permissions
        if (!$this->rbacService->hasPermission($currentUser, 'admin.manage_staff.revoke')) {
            return response()->json(['message' => 'You do not have permission to revoke permissions'], 403);
        }

        $targetUser = User::findOrFail($request->user_id);

        $this->rbacService->revokePermissionFromUser($targetUser, $request->permission_id);

        return response()->json([
            'message' => 'Permission revoked successfully',
            'revoked_at' => now()
        ]);
    }

    /**
     * Get all roles
     */
    public function getRoles(): JsonResponse
    {
        $currentUser = Auth::user();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if current user has permission to view roles
        if (!$this->rbacService->hasPermission($currentUser, 'admin.manage_staff.view')) {
            return response()->json(['message' => 'You do not have permission to view roles'], 403);
        }

        $roles = Role::all();

        return response()->json([
            'roles' => $roles
        ]);
    }

    /**
     * Get all permissions
     */
    public function getPermissions(): JsonResponse
    {
        $currentUser = Auth::user();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if current user has permission to view permissions
        if (!$this->rbacService->hasPermission($currentUser, 'admin.manage_staff.view')) {
            return response()->json(['message' => 'You do not have permission to view permissions'], 403);
        }

        $permissions = Permission::all();

        return response()->json([
            'permissions' => $permissions
        ]);
    }

    /**
     * Get permissions for a specific role
     */
    public function getRolePermissions(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        $currentUser = Auth::user();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if current user has permission to view permissions
        if (!$this->rbacService->hasPermission($currentUser, 'admin.manage_staff.view')) {
            return response()->json(['message' => 'You do not have permission to view permissions'], 403);
        }

        $role = Role::findOrFail($request->role_id);
        $permissions = $role->permissions;

        return response()->json([
            'role' => $role,
            'permissions' => $permissions
        ]);
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissionsToRole(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id'
        ]);

        $currentUser = Auth::user();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if current user has permission to assign permissions
        if (!$this->rbacService->hasPermission($currentUser, 'admin.manage_staff.assign')) {
            return response()->json(['message' => 'You do not have permission to assign permissions'], 403);
        }

        $role = Role::findOrFail($request->role_id);

        // Sync permissions
        $role->permissions()->sync($request->permission_ids);

        return response()->json([
            'message' => 'Permissions assigned to role successfully',
            'assigned_at' => now()
        ]);
    }
}
