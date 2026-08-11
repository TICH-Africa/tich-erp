<?php

namespace Database\Seeders;

class ResearchDemoSeeder extends ModuleDemoSeeder
{
    protected function deptCode(): string
    {
        return 'RES';
    }

    protected function moduleKey(): string
    {
        return 'research';
    }

    protected function roleName(): string
    {
        return 'Research Manager';
    }

    protected function managerProfile(): array
    {
        return [
            'employee_number' => 'EMP-RES-001',
            'first_name' => 'Daniel',
            'surname' => 'Omondi',
            'job_title' => 'Research Manager',
            'gross_monthly_salary' => 98000,
        ];
    }
}
