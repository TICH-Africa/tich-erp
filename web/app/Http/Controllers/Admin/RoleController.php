<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ServesAccessManagementPages;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RoleCategory;
use App\Services\AuditService;
use App\Services\ModuleRoleCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    use ServesAccessManagementPages;

    public function __construct(
        protected AuditService $auditService,
        protected ModuleRoleCatalogService $moduleRoles,
    ) {}

    public function index(Request $request): View
    {
        $moduleFilter = $request->string('module')->toString();
        $institutionKey = config('tich-module-roles.institution_module_key', '_institution');

        $rolesQuery = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderByRaw('module_key IS NULL, module_key')
            ->orderByDesc('is_system_role')
            ->orderBy('role_name');

        if ($moduleFilter === $institutionKey) {
            $rolesQuery->whereNull('module_key');
        } elseif ($moduleFilter !== '') {
            $rolesQuery->where('module_key', $moduleFilter);
        }

        $roles = $rolesQuery->get();
        $categories = RoleCategory::activeOptions();
        $categoryLabels = RoleCategory::labelMap();
        $rolesCount = Role::query()->count();
        $categoriesCount = count(RoleCategory::systemCodes());
        $moduleOptions = $this->moduleRoles->modules();
        $selectedModule = $moduleFilter;

        $permissionMatrices = [];
        foreach ($roles as $role) {
            $permissionMatrices[$role->id] = $this->moduleRoles->permissionMatrixForRole($role);
        }

        return view($this->accessContext()->prefix.'.roles.index', compact(
            'roles',
            'categories',
            'categoryLabels',
            'rolesCount',
            'categoriesCount',
            'moduleOptions',
            'selectedModule',
            'institutionKey',
            'permissionMatrices',
        ) + [
            'access' => $this->accessContext(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $institutionKey = config('tich-module-roles.institution_module_key', '_institution');
        $moduleKeys = array_keys(config('tich-module-roles.modules', []));

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100', 'unique:roles,role_name'],
            'display_name' => ['required', 'string', 'max:150'],
            'role_category' => ['required', Rule::in(RoleCategory::systemCodes())],
            'module_key' => ['required', Rule::in($moduleKeys)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role = Role::create([
            ...$validated,
            'module_key' => $validated['module_key'] === $institutionKey ? null : $validated['module_key'],
            'is_system_role' => false,
        ]);

        $this->auditService->log(
            'rbac.role.created',
            'roles',
            $role->id,
            null,
            $role->only(['role_name', 'display_name', 'role_category', 'module_key', 'description']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Role created successfully. Assign permissions to define what this role can do.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100', 'unique:roles,role_name,'.$role->id],
            'display_name' => ['required', 'string', 'max:150'],
            'role_category' => [
                'required',
                Rule::in(array_unique(array_merge(
                    RoleCategory::systemCodes(),
                    [$role->role_category]
                ))),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if ($role->is_system_role && $validated['role_name'] !== $role->role_name) {
            return back()->withInput()->withErrors([
                'role_name' => 'System role names cannot be renamed. Update the display name instead.',
            ]);
        }

        $old = $role->only(['role_name', 'display_name', 'role_category', 'module_key', 'description']);
        $role->update($validated);

        $this->auditService->log(
            'rbac.role.updated',
            'roles',
            $role->id,
            $old,
            $role->only(['role_name', 'display_name', 'role_category', 'module_key', 'description']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Role updated successfully.');
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $oldPermissionIds = $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();
        $newPermissionIds = collect($validated['permission_ids'] ?? [])->map(fn ($id) => (int) $id)->all();

        $this->moduleRoles->syncRolePermissionIds($role, $newPermissionIds, $request->user()->id);

        $this->auditService->log(
            'rbac.role.permissions_synced',
            'roles',
            $role->id,
            ['permission_ids' => $oldPermissionIds],
            ['permission_ids' => $newPermissionIds],
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', "Permissions updated for {$role->display_name}.");
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        if ($role->is_system_role) {
            return back()->withErrors(['role' => 'System roles cannot be deleted.']);
        }

        $assignedUsers = DB::table('user_roles')->where('role_id', $role->id)->count();

        if ($assignedUsers > 0) {
            return back()->withErrors([
                'role' => "Cannot delete \"{$role->role_name}\" - {$assignedUsers} user(s) still have this role. Reassign them first.",
            ]);
        }

        $snapshot = $role->only(['role_name', 'display_name', 'role_category', 'module_key']);
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
