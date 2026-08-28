<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\RbacCatalogService;
use App\Services\RBACService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RBACController extends Controller
{
    protected RBACService $rbacService;

    public function __construct(RBACService $rbacService)
    {
        $this->rbacService = $rbacService;
    }

    public function getUserPermissions(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'permissions' => $this->rbacService->getUserPermissions($user),
        ]);
    }

    public function getUserRoles(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'roles' => $this->rbacService->getUserRoles($user),
        ]);
    }

    public function assignRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $currentUser = Auth::user();

        if (! $currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! $this->rbacService->hasPermission($currentUser, 'admin.manage_staff.assign')) {
            return response()->json(['message' => 'You do not have permission to assign roles'], 403);
        }

        $targetUser = User::findOrFail($request->user_id);

        $this->rbacService->assignRoleToUser(
            $targetUser,
            $request->role_id,
            $request->campus_id,
            $request->department_id,
            $currentUser->id,
            true,
        );

        return response()->json([
            'message' => 'Role assigned successfully',
            'assigned_at' => now(),
        ]);
    }

    public function revokeRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $currentUser = Auth::user();

        if (! $currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! $this->rbacService->hasPermission($currentUser, 'admin.manage_staff.revoke')) {
            return response()->json(['message' => 'You do not have permission to revoke roles'], 403);
        }

        $targetUser = User::findOrFail($request->user_id);

        $this->rbacService->revokeRoleFromUser($targetUser, $request->role_id, $currentUser->id);

        return response()->json([
            'message' => 'Role revoked successfully',
            'revoked_at' => now(),
        ]);
    }

    public function assignPermission(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Direct user permissions were removed. Assign a role instead.',
        ], 422);
    }

    public function revokePermission(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Direct user permissions were removed. Revoke a role instead.',
        ], 422);
    }

    public function getRoles(): JsonResponse
    {
        $currentUser = Auth::user();

        if (! $currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! $this->rbacService->hasPermission($currentUser, 'admin.manage_staff.view')) {
            return response()->json(['message' => 'You do not have permission to view roles'], 403);
        }

        return response()->json([
            'roles' => Role::all(),
        ]);
    }

    public function getPermissions(): JsonResponse
    {
        $currentUser = Auth::user();

        if (! $currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! $this->rbacService->hasPermission($currentUser, 'admin.manage_staff.view')) {
            return response()->json(['message' => 'You do not have permission to view permissions'], 403);
        }

        return response()->json([
            'permissions' => app(RbacCatalogService::class)->permissions(),
        ]);
    }

    public function getRolePermissions(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $currentUser = Auth::user();

        if (! $currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! $this->rbacService->hasPermission($currentUser, 'admin.manage_staff.view')) {
            return response()->json(['message' => 'You do not have permission to view permissions'], 403);
        }

        $role = Role::findOrFail($request->role_id);
        $catalog = app(RbacCatalogService::class);
        $slugs = array_keys($catalog->grantedSlugSetForRoleRecord($role->role_name, $role->module_key));

        return response()->json([
            'role' => $role,
            'permissions' => collect($catalog->permissions())->whereIn('slug', $slugs)->values(),
        ]);
    }

    public function assignPermissionsToRole(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Role permissions are defined in config and cannot be assigned via the API. Assign roles to users instead.',
        ], 422);
    }
}
