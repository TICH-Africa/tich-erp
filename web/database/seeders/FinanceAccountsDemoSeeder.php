<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\ChartOfAccount;
use App\Models\FeeStructure;
use App\Models\Staff;
use App\Models\Student;
use App\Services\Finance\InvoiceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanceAccountsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedChartOfAccounts();

        $program = AcademicProgram::query()->orderBy('id')->first();
        $year = AcademicYear::query()->orderByDesc('start_date')->first();
        $student = Student::query()->with('applicant')->orderBy('id')->first();
        $staffId = (int) (Staff::query()->value('id') ?? 1);

        if (! $program || ! $year || ! $student) {
            return;
        }

        $feeStructure = FeeStructure::query()->firstOrCreate(
            [
                'program_id' => $program->id,
                'academic_year_id' => $year->id,
                'semester_number' => 1,
            ],
            [
                'tuition_fee' => 45000,
                'registration_fee' => 3000,
                'examination_fee' => 2500,
                'library_fee' => 1500,
                'activity_fee' => 1000,
                'hostel_fee' => 0,
                'medical_insurance_fee' => 2000,
                'nursing_clinical_fee' => 5000,
                'graduation_fee' => 0,
                'total_semester_fee' => 60000,
                'is_approved' => 1,
                'approved_by' => $staffId,
                'approved_at' => now(),
                'effective_from' => now()->toDateString(),
                'is_active' => 1,
            ]
        );
        $feeStructure->recalculateTotal();
        $feeStructure->save();

        if (DB::table('invoices')->where('student_id', $student->id)->doesntExist()) {
            app(InvoiceService::class)->generateFromFeeStructure($student, $feeStructure, $staffId);
        }
    }

    private function seedChartOfAccounts(): void
    {
        $accounts = [
            ['1000', 'Main Treasury Account', 'asset', 'Treasury', null, 1],
            ['1010', 'M-Pesa Collections', 'asset', 'Cash', '1000', 1],
            ['1020', 'Bank Collections', 'asset', 'Cash', '1000', 1],
            ['1100', 'Accounts Receivable — Students', 'asset', 'Receivables', null, 1],
            ['2000', 'Accounts Payable', 'liability', 'Payables', null, 1],
            ['3000', 'Institutional Equity', 'equity', 'Equity', null, 1],
            ['4000', 'Tuition Revenue', 'revenue', 'Student Fees', null, 1],
            ['4010', 'Application Fee Revenue', 'revenue', 'Student Fees', null, 1],
            ['4020', 'Examination Fee Revenue', 'revenue', 'Student Fees', null, 1],
            ['4030', 'Graduation Fee Revenue', 'revenue', 'Student Fees', null, 1],
            ['4090', 'Other Fee Revenue', 'revenue', 'Student Fees', null, 1],
            ['5000', 'Operating Expenses', 'expense', 'Operations', null, 1],
        ];

        foreach ($accounts as [$code, $name, $type, $category, $parent, $system]) {
            ChartOfAccount::query()->firstOrCreate(
                ['account_code' => $code],
                [
                    'account_name' => $name,
                    'account_type' => $type,
                    'account_category' => $category,
                    'parent_account_code' => $parent,
                    'is_active' => 1,
                    'is_system_account' => $system,
                ]
            );
        }
    }
}
