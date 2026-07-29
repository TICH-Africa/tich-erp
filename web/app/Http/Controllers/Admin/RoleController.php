<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    public function index(): View
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderByDesc('is_system_role')
            ->orderBy('role_name')
            ->get();

        $categories = [
            'executive' => 'Executive',
            'academic' => 'Academic',
            'teaching' => 'Teaching',
            'administrative' => 'Administrative',
            'student' => 'Student',
        ];

        return view('admin.roles.index', compact('roles', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100', 'unique:roles,role_name'],
            'display_name' => ['required', 'string', 'max:150'],
            'role_category' => ['required', 'in:executive,academic,teaching,administrative,student'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role = Role::create([
            ...$validated,
            'is_system_role' => false,
        ]);

        $this->auditService->log(
            'rbac.role.created',
            'roles',
            $role->id,
            null,
            $role->only(['role_name', 'display_name', 'role_category', 'description']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Role created successfully.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100', 'unique:roles,role_name,'.$role->id],
            'display_name' => ['required', 'string', 'max:150'],
            'role_category' => ['required', 'in:executive,academic,teaching,administrative,student'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if ($role->is_system_role && $validated['role_name'] !== $role->role_name) {
            return back()->withInput()->withErrors([
                'role_name' => 'System role names cannot be renamed. Update the display name instead.',
            ]);
        }

        $old = $role->only(['role_name', 'display_name', 'role_category', 'description']);
        $role->update($validated);

        $this->auditService->log(
            'rbac.role.updated',
            'roles',
            $role->id,
            $old,
            $role->only(['role_name', 'display_name', 'role_category', 'description']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        if ($role->is_system_role) {
            return back()->withErrors(['role' => 'System roles cannot be deleted.']);
        }

        $assignedUsers = DB::table('user_roles')->where('role_id', $role->id)->count();

        if ($assignedUsers > 0) {
            return back()->withErrors([
                'role' => "Cannot delete \"{$role->role_name}\" — {$assignedUsers} user(s) still have this role. Reassign them first.",
            ]);
        }

        $snapshot = $role->only(['role_name', 'display_name', 'role_category']);
        $roleId = $role->id;

        DB::table('role_permissions')->where('role_id', $role->id)->delete();
        $role->delete();

        $this->auditService->log(
            'rbac.role.deleted',
            'roles',
            $roleId,
            $snapshot,
            null,
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Role deleted successfully.');
    }
}
