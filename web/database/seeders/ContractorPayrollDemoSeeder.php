<?php

namespace Database\Seeders;

use App\Models\PayrollDeductionType;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractorPayrollDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureWithholdingTaxSetting();

        $hrDepartmentId = DB::table('departments')->where('dept_code', 'HR')->value('id')
            ?? DB::table('departments')->where('id', 9)->value('id')
            ?? DB::table('departments')->whereNull('parent_dept_id')->value('id');

        $financeDepartmentId = DB::table('departments')->where('dept_code', 'FIN')->value('id')
            ?? DB::table('departments')->where('dept_name', 'like', '%Finance%')->value('id')
            ?? $hrDepartmentId;

        $academicDepartmentId = DB::table('departments')->where('dept_code', 'CHS')->value('id')
            ?? DB::table('departments')->where('dept_category', 'academic')->whereNotNull('parent_dept_id')->value('id')
            ?? $hrDepartmentId;

        $campusId = DB::table('campuses')->where('is_active', 1)->value('id');

        if (! $hrDepartmentId) {
            $this->command?->warn('No department found - skipping contractor payroll demo seed.');

            return;
        }

        $contractors = [
            [
                'employee_number' => 'CON-HR-001',
                'first_name' => 'James',
                'surname' => 'Mutua',
                'job_title' => 'HR Systems Consultant',
                'department_id' => $hrDepartmentId,
                'employment_category' => 'consultant',
                'payroll_scheme' => 'withholding',
                'gross_monthly_salary' => 120000,
                'kra_pin' => 'A012345678Z',
            ],
            [
                'employee_number' => 'CON-FIN-001',
                'first_name' => 'Amina',
                'surname' => 'Hassan',
                'job_title' => 'Independent Financial Auditor',
                'department_id' => $financeDepartmentId,
                'employment_category' => 'independent_contractor',
                'payroll_scheme' => 'withholding',
                'gross_monthly_salary' => 85000,
                'kra_pin' => 'A987654321Y',
            ],
            [
                'employee_number' => 'CON-CHS-001',
                'first_name' => 'Daniel',
                'surname' => 'Wekesa',
                'job_title' => 'Curriculum Development Consultant',
                'department_id' => $academicDepartmentId,
                'employment_category' => 'consultant',
                'payroll_scheme' => 'withholding',
                'gross_monthly_salary' => 95000,
                'kra_pin' => 'A564738291X',
            ],
        ];

        foreach ($contractors as $contractor) {
            $emails = [
                'primary_email' => strtolower($contractor['first_name']).'.'.strtolower($contractor['surname']).'@gmail.com',
                'organisation_email' => Staff::organisationEmailFromName($contractor['first_name'], $contractor['surname']),
            ];

            Staff::query()->updateOrCreate(
                ['employee_number' => $contractor['employee_number']],
                [
                    'title' => 'Mr.',
                    'first_name' => $contractor['first_name'],
                    'surname' => $contractor['surname'],
                    'date_of_birth' => '1985-08-10',
                    'gender' => 'male',
                    'primary_email' => $emails['primary_email'],
                    'organisation_email' => $emails['organisation_email'],
                    'phone_number' => '07'.fake()->numerify('########'),
                    'department_id' => $contractor['department_id'],
                    'campus_id' => $campusId,
                    'job_title' => $contractor['job_title'],
                    'employment_category' => $contractor['employment_category'],
                    'payroll_scheme' => $contractor['payroll_scheme'],
                    'employment_start_date' => '2025-01-01',
                    'contract_end_date' => now()->addMonths(6)->toDateString(),
                    'employment_status' => 'active',
                    'gross_monthly_salary' => $contractor['gross_monthly_salary'],
                    'kra_pin' => $contractor['kra_pin'],
                    'is_teaching_staff' => 0,
                ]
            );
        }

        $this->command?->info('Seeded demo consultants/contractors for withholding-tax payroll (CON-HR-001, CON-FIN-001, CON-CHS-001).');
    }

    private function ensureWithholdingTaxSetting(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('payroll_deduction_types')) {
            $this->command?->warn('payroll_deduction_types table missing - run payroll migrations first.');

            return;
        }

        $enumValues = collect(DB::select("SHOW COLUMNS FROM payroll_deduction_types WHERE Field = 'value_type'"))
            ->first();

        if ($enumValues && ! str_contains((string) ($enumValues->Type ?? ''), 'withholding_percent')) {
            DB::statement("ALTER TABLE payroll_deduction_types MODIFY value_type ENUM('band_percent', 'global_fixed', 'withholding_percent') NOT NULL DEFAULT 'band_percent'");
        }

        PayrollDeductionType::query()->updateOrCreate(
            ['code' => 'withholding_tax'],
            [
                'label' => 'Withholding tax (WHT)',
                'value_type' => 'withholding_percent',
                'fixed_amount' => config('tich-payroll.default_withholding_rate', 5),
                'display_order' => 99,
                'is_active' => 1,
            ]
        );
    }
}
