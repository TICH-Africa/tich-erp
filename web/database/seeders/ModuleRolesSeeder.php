<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Services\ModuleRoleCatalogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleRolesSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('roles')) {
            return;
        }

        $catalog = app(ModuleRoleCatalogService::class);

        foreach (config('tich-module-roles.modules', []) as $moduleKey => $moduleConfig) {
            foreach ($moduleConfig['roles'] ?? [] as $definition) {
                $role = Role::query()->updateOrCreate(
                    ['role_name' => $definition['role_name']],
                    [
                        'display_name' => $definition['display_name'],
                        'role_category' => $definition['role_category'],
                        'description' => $definition['description'] ?? null,
                        'module_key' => $moduleKey,
                        'is_system_role' => true,
                    ],
                );

                $catalog->syncPredefinedRolePermissions($role, $definition);
            }
        }

        $this->tagLegacyRoles();
    }

    private function tagLegacyRoles(): void
    {
        $legacyModuleMap = [
            'Dean' => 'academics',
            'Staff' => 'hr',
            'Student' => 'academics',
            'Applicant' => 'admissions',
            'Alumni' => 'portal',
            'Super Admin' => null,
        ];

        foreach ($legacyModuleMap as $roleName => $moduleKey) {
            Role::query()->where('role_name', $roleName)->update(['module_key' => $moduleKey]);
        }

        Role::query()
            ->whereNull('module_key')
            ->where('is_system_role', false)
            ->update(['module_key' => null]);
    }
}
