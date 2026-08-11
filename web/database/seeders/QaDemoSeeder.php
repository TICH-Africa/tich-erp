<?php

namespace Database\Seeders;

class QaDemoSeeder extends ModuleDemoSeeder
{
    protected function deptCode(): string
    {
        return 'QA';
    }

    protected function moduleKey(): string
    {
        return 'qa';
    }

    protected function roleName(): string
    {
        return 'QA Officer';
    }

    protected function managerProfile(): array
    {
        return [
            'employee_number' => 'EMP-QA-001',
            'first_name' => 'Jane',
            'surname' => 'Chebet',
            'job_title' => 'Quality Assurance Officer',
            'gross_monthly_salary' => 92000,
        ];
    }
}
