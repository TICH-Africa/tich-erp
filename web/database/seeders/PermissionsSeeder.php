<?php

namespace Database\Seeders;

use App\Services\RbacCatalogService;
use Illuminate\Database\Seeder;

/**
 * @deprecated Permissions live in config/tich-permissions.php and are resolved at runtime.
 */
class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(RbacCatalogService::class)->ensureRolesExist();
        $this->command?->warn('PermissionsSeeder is a no-op for permission rows. Catalog is in config/tich-permissions.php.');
    }
}
