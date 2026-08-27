<?php

namespace Database\Seeders;

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
}
