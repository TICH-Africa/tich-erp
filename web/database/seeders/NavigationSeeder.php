<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Public navigation lives in config/tich-navigation.php (NavigationService reads config by default).
 */
class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('NavigationSeeder is a no-op. Navigation is in config/tich-navigation.php.');
    }
}
