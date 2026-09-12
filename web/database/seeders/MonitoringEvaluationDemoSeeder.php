<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Services\DepartmentModuleService;
use Illuminate\Support\Facades\DB;

class MonitoringEvaluationDemoSeeder extends ModuleDemoSeeder
{
    protected function deptCode(): string
    {
        return 'MNE';
    }

    protected function moduleKey(): string
    {
        return 'monitoring_evaluation';
    }

    protected function roleName(): string
    {
        return 'Monitoring and Evaluation Officer';
    }

    protected function managerProfile(): array
    {
        return [
            'employee_number' => 'EMP-MNE-001',
            'first_name' => 'Grace',
            'surname' => 'Wanjiru',
            'job_title' => 'Monitoring and Evaluation Officer',
            'gross_monthly_salary' => 92000,
        ];
    }

    public function run(): void
    {
        $this->ensureDepartment();
        parent::run();
    }

    private function ensureDepartment(): void
    {
        $existingId = DB::table('departments')
            ->whereIn('dept_code', ['MNE', 'M&E', 'ME'])
            ->value('id');

        if ($existingId) {
            // Prefer the canonical code used by module budgeting routes.
            DB::table('departments')->where('id', $existingId)->update([
                'dept_code' => 'MNE',
                'dept_name' => 'Monitoring & Evaluation',
                'dept_category' => 'administrative',
                'is_active' => 1,
            ]);

            $department = Department::query()->find($existingId);
            if ($department) {
                app(DepartmentModuleService::class)->syncModules($department, ['monitoring_evaluation']);
            }

            return;
        }

        $campusId = DB::table('campuses')->where('is_active', 1)->value('id');
        $groupId = DB::table('department_groups')->where('group_code', 'IDM')->value('id')
            ?? DB::table('department_groups')->orderBy('id')->value('id');

        $id = DB::table('departments')->insertGetId([
            'dept_code' => 'MNE',
            'dept_name' => 'Monitoring & Evaluation',
            'dept_category' => 'administrative',
            'campus_id' => $campusId,
            'department_group_id' => $groupId,
            'parent_dept_id' => null,
            'display_order' => 5,
            'is_active' => 1,
            'created_at' => now(),
        ]);

        $department = Department::query()->find($id);
        if ($department) {
            app(DepartmentModuleService::class)->syncModules($department, ['monitoring_evaluation']);
        }
    }
}
