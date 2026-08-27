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
        return 'Head of ICT';
    }

    protected function managerProfile(): array
    {
        return [
            'employee_number' => 'EMP-ICT-001',
            'first_name' => 'Brian',
            'surname' => 'Kariuki',
            'job_title' => 'Head of ICT',
            'gross_monthly_salary' => 102000,
        ];
    }
}
