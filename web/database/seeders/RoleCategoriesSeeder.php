<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Categories live in config/tich-role-categories.php.
 */
class RoleCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('RoleCategoriesSeeder is a no-op. Categories are in config/tich-role-categories.php.');
    }
}
