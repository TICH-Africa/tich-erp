<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Services\DepartmentModuleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketingDepartmentModulesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('department_modules') || ! Schema::hasTable('departments')) {
            return;
        }

        $marketing = Department::query()->where('dept_code', 'MKT')->where('is_active', 1)->first();

        if (! $marketing) {
            return;
        }

        $service = app(DepartmentModuleService::class);
        $service->syncModules($marketing, ['portal', 'site_settings']);

        $remaining = DB::table('department_modules')
            ->where('department_id', $marketing->id)
            ->pluck('module_key')
            ->toArray();

        echo 'Marketing department modules: ' . implode(', ', $remaining) . PHP_EOL;
    }
}
