<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnsureExtendedModulePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('permissions')) {
            return;
        }

        $newModules = [
            'administration' => ['manage_registry', 'manage_facilities', 'manage_general_services'],
            'procurement' => ['manage_suppliers', 'manage_purchase_orders', 'manage_tenders', 'manage_inventory'],
            'research' => ['manage_projects', 'manage_grants', 'manage_publications', 'manage_ethics'],
            'ict' => ['manage_helpdesk', 'manage_assets', 'manage_infrastructure', 'manage_systems'],
        ];

        $categories = ['view', 'create', 'edit', 'delete', 'approve', 'reject', 'export', 'import', 'manage', 'audit'];

        foreach ($newModules as $module => $actions) {
            foreach ($actions as $action) {
                foreach ($categories as $category) {
                    $slug = "{$module}_{$action}_{$category}";

                    if (DB::table('permissions')->where('slug', $slug)->exists()) {
                        continue;
                    }

                    DB::table('permissions')->insert([
                        'permission_name' => "{$module}.{$action}.{$category}",
                        'slug' => $slug,
                        'module' => $module,
                        'category' => $category,
                        'description' => "Permission to {$category} {$action} in {$module} module",
                        'is_system' => 1,
                        'created_at' => now(),
                    ]);
                }
            }
        }

        $roles = [
            ['role_name' => 'Administration Manager', 'display_name' => 'Administration Manager', 'role_category' => 'administrative', 'description' => 'General administration and registry services', 'is_system_role' => 1],
            ['role_name' => 'Procurement Manager', 'display_name' => 'Procurement Manager', 'role_category' => 'administrative', 'description' => 'Procurement, suppliers, and logistics', 'is_system_role' => 1],
            ['role_name' => 'Research Manager', 'display_name' => 'Research Manager', 'role_category' => 'administrative', 'description' => 'Research projects, grants, and publications', 'is_system_role' => 1],
            ['role_name' => 'ICT Manager', 'display_name' => 'ICT Manager', 'role_category' => 'administrative', 'description' => 'Information systems, infrastructure, and IT support', 'is_system_role' => 1],
        ];

        foreach ($roles as $role) {
            if (! DB::table('roles')->where('role_name', $role['role_name'])->exists()) {
                DB::table('roles')->insert($role);
            }
        }

        $this->assignRolePermissions('Administration Manager', ['core', 'administration'], ['view', 'create', 'edit', 'manage', 'export']);
        $this->assignRolePermissions('Procurement Manager', ['core', 'procurement'], ['view', 'create', 'edit', 'approve', 'manage', 'export']);
        $this->assignRolePermissions('Research Manager', ['core', 'research', 'portal'], ['view', 'create', 'edit', 'manage', 'export']);
        $this->assignRolePermissions('ICT Manager', ['core', 'ict'], ['view', 'create', 'edit', 'manage', 'export']);
        $this->grantRolePermissionSlugs('ICT Manager', ['admin_manage_staff_manage', 'admin_manage_staff_view']);

        $ceoId = DB::table('roles')->where('role_name', 'CEO')->value('id');
        if ($ceoId) {
            foreach (['administration', 'procurement', 'research', 'ict'] as $module) {
                $permissionIds = DB::table('permissions')->where('module', $module)->pluck('id');
                foreach ($permissionIds as $permissionId) {
                    $exists = DB::table('role_permissions')
                        ->where('role_id', $ceoId)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if (! $exists) {
                        DB::table('role_permissions')->insert([
                            'role_id' => $ceoId,
                            'permission_id' => $permissionId,
                            'granted_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    private function assignRolePermissions(string $roleName, array $modules, ?array $categories = null): void
    {
        $roleId = DB::table('roles')->where('role_name', $roleName)->value('id');

        if (! $roleId) {
            return;
        }

        $query = DB::table('permissions')->whereIn('module', $modules);

        if ($categories) {
            $query->whereIn('category', $categories);
        }

        foreach ($query->pluck('id') as $permissionId) {
            $exists = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'granted_at' => now(),
                ]);
            }
        }
    }

    private function grantRolePermissionSlugs(string $roleName, array $slugs): void
    {
        $roleId = DB::table('roles')->where('role_name', $roleName)->value('id');

        if (! $roleId) {
            return;
        }

        foreach ($slugs as $slug) {
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');

            if (! $permissionId) {
                continue;
            }

            $exists = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'granted_at' => now(),
                ]);
            }
        }
    }
}
