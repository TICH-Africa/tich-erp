<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Extended modules are defined in config/tich-permissions.php + tich-module-roles.php.
 */
class EnsureExtendedModulePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('EnsureExtendedModulePermissionsSeeder is a no-op. Catalog is code-owned.');
    }
}
