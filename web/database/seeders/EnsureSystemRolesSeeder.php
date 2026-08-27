<?php

namespace Database\Seeders;

use App\Services\RbacCatalogService;
use Illuminate\Database\Seeder;

/**
 * @deprecated System roles live in config/tich-module-roles.php.
 */
class EnsureSystemRolesSeeder extends Seeder
{
    public function run(): void
    {
        app(RbacCatalogService::class)->ensureRolesExist();
    }
}
