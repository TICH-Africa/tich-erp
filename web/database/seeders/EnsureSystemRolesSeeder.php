<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnsureSystemRolesSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::table('permissions')->exists()) {
            $this->call(PermissionsSeeder::class);

            return;
        }

        $roles = [
            ['role_name' => 'CEO', 'role_category' => 'executive', 'description' => 'Executive oversight and final approvals', 'is_system_role' => 1],
            ['role_name' => 'Academic Registrar', 'role_category' => 'executive', 'description' => 'Academic records, admissions, and student lifecycle', 'is_system_role' => 1],
            ['role_name' => 'Admissions Officer', 'role_category' => 'administrative', 'description' => 'Applicant intake, screening, and onboarding', 'is_system_role' => 1],
            ['role_name' => 'Applicant', 'role_category' => 'student', 'description' => 'Pre-admission applicant portal access', 'is_system_role' => 1],
            ['role_name' => 'Alumni', 'role_category' => 'student', 'description' => 'Alumni engagement and records access', 'is_system_role' => 1],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['role_name' => $role['role_name']],
                $role
            );
        }

        $this->ensureRolePermissions('CEO', ['core', 'admin', 'academics', 'finance', 'hr', 'portal', 'qa'], ['approve', 'view', 'manage', 'audit', 'export']);
        $this->ensureRolePermissions('Academic Registrar', ['core', 'admin', 'academics'], ['approve', 'view', 'create', 'edit', 'manage', 'export']);
        $this->ensureRolePermissions('Admissions Officer', ['admin'], ['view', 'create', 'edit', 'approve', 'manage', 'export']);
        $this->ensureRolePermissions('HR Manager', ['core', 'hr'], ['view', 'create', 'edit', 'approve', 'manage', 'export']);
        $this->ensureRolePermissions('Applicant', ['admin'], ['view', 'create']);
        $this->ensureRolePermissions('Alumni', ['portal'], ['view']);
    }

    private function ensureRolePermissions(string $roleName, array $modules, ?array $categories = null): void
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
}
