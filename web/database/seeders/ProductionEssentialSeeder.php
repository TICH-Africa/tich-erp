<?php

namespace Database\Seeders;

use App\Services\RbacCatalogService;
use Illuminate\Database\Seeder;

/**
 * @deprecated Catalog lives in config (tich-permissions, tich-module-roles, tich-role-categories, tich-navigation).
 * Only ensures thin role rows for user_roles FKs. Does not seed permissions / nav / role_permissions.
 */
class ProductionEssentialSeeder extends Seeder
{
    public function run(): void
    {
        app(RbacCatalogService::class)->ensureRolesExist();

        $this->command?->info('RBAC catalog is code-owned. Ensured role rows only (no permission/nav seeding).');
    }
}
