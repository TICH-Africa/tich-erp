<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Services\DepartmentModuleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DepartmentModulesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('department_modules')) {
            return;
        }

        $service = app(DepartmentModuleService::class);

        Department::query()->each(function (Department $department) use ($service) {
            if ($service->assignedModuleKeys($department) !== []) {
                return;
            }

            $keys = $service->legacyModulesForDeptCode($department->dept_code);

            if ($keys === [] && $department->dept_category === 'academic' && $department->parent_dept_id !== null) {
                $keys = $service->defaultModulesForCategory('academic');
            }

            if ($keys === [] && $department->dept_category === 'administrative') {
                $keys = $service->legacyModulesForDeptCode($department->dept_code);
            }

            if ($keys !== []) {
                $service->syncModules($department, $keys);
            }
        });
    }
}
