<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Use JamesOchiengAcademicCycleSeeder instead.
 */
class StaffMarksDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(JamesOchiengAcademicCycleSeeder::class);
    }
}
