<?php

namespace Database\Seeders;

class ProcurementDemoSeeder extends ModuleDemoSeeder
{
    protected function deptCode(): string
    {
        return 'PRC';
    }

    protected function moduleKey(): string
    {
        return 'procurement';
    }

    protected function roleName(): string
    {
        return 'Procurement Manager';
    }

    protected function managerProfile(): array
    {
        return [
            'employee_number' => 'EMP-PRC-001',
            'first_name' => 'Samuel',
            'surname' => 'Mutua',
            'job_title' => 'Procurement Manager',
            'gross_monthly_salary' => 95000,
        ];
    }
}
