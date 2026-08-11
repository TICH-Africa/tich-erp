<?php

namespace Database\Seeders;

class IctDemoSeeder extends ModuleDemoSeeder
{
    protected function deptCode(): string
    {
        return 'ICTO';
    }

    protected function moduleKey(): string
    {
        return 'ict';
    }

    protected function roleName(): string
    {
        return 'ICT Manager';
    }

    protected function managerProfile(): array
    {
        return [
            'employee_number' => 'EMP-ICT-001',
            'first_name' => 'Brian',
            'surname' => 'Kariuki',
            'job_title' => 'ICT Manager',
            'gross_monthly_salary' => 102000,
        ];
    }
}
