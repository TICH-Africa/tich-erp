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

        $feeStructure = FeeStructure::query()->updateOrCreate(
            [
                'program_id' => $program->id,
                'academic_year_id' => $year->id,
            ],
            [
                'application_fee' => 1000,
                'tuition_fee' => 45000,
                'caution_fee' => 5000,
                'computer_lab_fee' => 2500,
                'transport_fee' => 1200,
                'transport_optional' => 1,
                'accommodation_fee' => 15000,
                'accommodation_optional' => 1,
                'partnership_fee' => 1000,
                'id_card_fee' => 500,
                'student_union_fee' => 800,
                'emergency_fund_fee' => 500,
                'library_fee' => 1500,
                'examination_external_fee' => 3000,
                'attachment_fee' => 4000,
                'qa_annual_fee' => 1000,
                'requires_indexing_nck' => 0,
                'indexing_nck_fee' => null,
                'graduation_fee' => 4000,
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
            app(InvoiceService::class)->generateSemesterInvoice($student, $feeStructure, $staffId);
        }
    }

    private function seedChartOfAccounts(): void
    {
        $accounts = [
            ['1000', 'Main Treasury Account', 'asset', 'Treasury', null, 1],
            ['1010', 'M-Pesa Collections', 'asset', 'Cash', '1000', 1],
            ['1020', 'Bank Collections', 'asset', 'Cash', '1000', 1],
            ['1100', 'Accounts Receivable - Students', 'asset', 'Receivables', null, 1],
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
