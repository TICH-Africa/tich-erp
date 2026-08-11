<?php

namespace Database\Seeders;

class AdministrationDemoSeeder extends ModuleDemoSeeder
{
    protected function deptCode(): string
    {
        return 'ADM';
    }

    protected function moduleKey(): string
    {
        return 'administration';
    }

    protected function roleName(): string
    {
        return 'Administration Manager';
    }

    protected function managerProfile(): array
    {
        return [
            'employee_number' => 'EMP-ADM-001',
            'first_name' => 'Lucy',
            'surname' => 'Wambui',
            'job_title' => 'Administration Manager',
            'gross_monthly_salary' => 88000,
        ];
    }
}
