<?php

namespace Database\Seeders;

use App\Services\RbacCatalogService;
use Illuminate\Database\Seeder;

/**
 * @deprecated Module roles + permission templates live in config/tich-module-roles.php.
 * Runtime checks use RbacCatalogService — no role_permissions sync.
 */
class ModuleRolesSeeder extends Seeder
{
    public function run(): void
    {
        app(RbacCatalogService::class)->ensureRolesExist();
        $this->command?->warn('ModuleRolesSeeder no longer syncs role_permissions. Catalog is code-owned.');
    }
}
