<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Staff;
use App\Models\Student;
use App\Services\Finance\CreditMemoService;
use App\Services\Finance\InvoiceService;
use App\Services\Finance\PaymentService;
use App\Support\IntakeIdentity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceBulkDemoSeeder extends Seeder
{
    private const STUDENT_COUNT = 45;

    private const DEMO_REG_PREFIX = 'REG-FIN-2026-';

    /** @var list<string> */
    private array $firstNames = [
        'Alice', 'Brian', 'Carol', 'David', 'Esther', 'Francis', 'Grace', 'Henry',
        'Irene', 'James', 'Kevin', 'Lucy', 'Mary', 'Nelson', 'Olivia', 'Paul',
        'Queen', 'Robert', 'Sarah', 'Thomas', 'Ursula', 'Victor', 'Winnie', 'Xavier',
        'Yvonne', 'Zachary', 'Ann', 'Ben', 'Cynthia', 'Daniel', 'Faith', 'George',
        'Hannah', 'Isaac', 'Jane', 'Kelvin', 'Linda', 'Michael', 'Naomi', 'Oscar',
        'Patricia', 'Quincy', 'Ruth', 'Samuel', 'Teresa',
    ];

    /** @var list<string> */
    private array $surnames = [
        'Kamau', 'Ochieng', 'Wanjiku', 'Mutua', 'Achieng', 'Kiprop', 'Njeri', 'Odhiambo',
        'Mwangi', 'Chebet', 'Otieno', 'Wambui', 'Kimani', 'Adhiambo', 'Njoroge', 'Akinyi',
        'Maina', 'Wanjala', 'Kibet', 'Nyambura', 'Omondi', 'Wairimu', 'Karanja', 'Atieno',
        'Macharia', 'Cherono', 'Barasa', 'Nyokabi', 'Rotich', 'Wanjiru', 'Sang', 'Moraa',
        'Korir', 'Chepkoech', 'Langat', 'Bosibori', 'Kiptoo', 'Chepngeno', 'Tanui', 'Jepkorir',
        'Bett', 'Chebet', 'Kosgei', 'Rono', 'Sigei',
    ];

    public function run(): void
    {
        $this->call(FinanceAccountsDemoSeeder::class);

        $staffId = (int) (Staff::query()->where('employee_number', 'EMP-FIN-001')->value('id')
            ?? Staff::query()->value('id')
            ?? 0);

        if ($staffId === 0) {
            $this->command?->warn('FinanceBulkDemoSeeder: no staff found - run FinanceDemoSeeder first.');

            return;
        }

        $year = AcademicYear::query()->orderByDesc('start_date')->first();
        $campusId = (int) (DB::table('campuses')->where('is_active', 1)->value('id') ?? 1);
        $semesterId = (int) (DB::table('semesters')->orderByDesc('id')->value('id') ?? 0);
        $financeDeptId = (int) (DB::table('departments')->where('dept_code', 'FIN')->value('id')
            ?? DB::table('departments')->value('id'));

        if (! $year) {
            $this->command?->warn('FinanceBulkDemoSeeder: academic year missing.');

            return;
        }

        $programs = AcademicProgram::query()
            ->orderBy('program_code')
            ->limit(12)
            ->get();

        if ($programs->isEmpty()) {
            $this->command?->warn('FinanceBulkDemoSeeder: no programmes found.');

            return;
        }

        $feeStructures = $this->seedFeeStructures($programs, (int) $year->id, $staffId);
        $students = $this->seedFinanceStudents($programs, $campusId, $semesterId);
        $this->seedExistingStudentFinance($staffId, $feeStructures);
        $invoiceStats = $this->seedStudentFinance($students, $feeStructures, $staffId);
        $supplierStats = $this->seedSuppliersAndPayables($financeDeptId, $staffId);
        $bankCount = $this->seedBankTransactions();
        $donorStats = $this->seedDonorProjects($staffId);
        $budgetCount = $this->seedBudgets($financeDeptId, $staffId);
        $assetStats = $this->seedAssetsAndInventory();
        $payrollCount = $this->seedPayrollHistory($staffId);
        $workStudyCount = $this->seedWorkStudyLedger($students, $staffId);

        $this->command?->info(sprintf(
            'Finance bulk demo: %d students, %d invoices raised, %d suppliers, %d AP invoices, %d bank lines, %d donor projects, %d budgets, %d assets, %d inventory items, %d payroll slips, %d work-study entries.',
            count($students),
            $invoiceStats['invoices'],
            $supplierStats['suppliers'],
            $supplierStats['ap'],
            $bankCount,
            $donorStats['projects'],
            $budgetCount,
            $assetStats['assets'],
            $assetStats['inventory'],
            $payrollCount,
            $workStudyCount,
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AcademicProgram>  $programs
     * @return array<int, FeeStructure> program_id => fee structure
     */
    private function seedFeeStructures($programs, int $yearId, int $staffId): array
    {
        $map = [];
        $multipliers = [0.85, 0.92, 1.0, 1.05, 1.12, 0.98, 1.08, 0.9, 1.15, 1.02, 0.88, 1.1];

        foreach ($programs as $index => $program) {
            $m = $multipliers[$index % count($multipliers)];
            $tuition = round(38000 * $m, 2);

            $feeStructure = FeeStructure::query()->updateOrCreate(
                [
                    'program_id' => $program->id,
                    'academic_year_id' => $yearId,
                ],
                [
                    'application_fee' => 1000,
                    'tuition_fee' => $tuition,
                    'caution_fee' => round(5000 * $m, 2),
                    'computer_lab_fee' => round(2500 * $m, 2),
                    'transport_fee' => 1200,
                    'transport_optional' => 1,
                    'accommodation_fee' => round(14000 * $m, 2),
                    'accommodation_optional' => 1,
                    'partnership_fee' => 1000,
                    'id_card_fee' => 500,
                    'student_union_fee' => 800,
                    'emergency_fund_fee' => 500,
                    'library_fee' => 1500,
                    'examination_external_fee' => round(3000 * $m, 2),
                    'attachment_fee' => round(4000 * $m, 2),
                    'qa_annual_fee' => 1000,
                    'requires_indexing_nck' => str_contains(strtoupper($program->program_code ?? ''), 'NUR') ? 1 : 0,
                    'indexing_nck_fee' => str_contains(strtoupper($program->program_code ?? ''), 'NUR') ? 8500 : 0,
                    'graduation_fee' => 4000,
                    'is_approved' => 1,
                    'approved_by' => $staffId,
                    'approved_at' => now()->subMonths(2),
                    'effective_from' => now()->subMonths(3)->toDateString(),
                    'is_active' => 1,
                    'status' => 'approved',
                ]
            );

            $feeStructure->recalculateTotal();
            $feeStructure->save();
            $map[$program->id] = $feeStructure;
        }

        return $map;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AcademicProgram>  $programs
     * @return list<Student>
     */
    private function seedFinanceStudents($programs, int $campusId, int $semesterId): array
    {
        $students = [];
        $programList = $programs->values()->all();

        for ($i = 0; $i < self::STUDENT_COUNT; $i++) {
            $reg = self::DEMO_REG_PREFIX.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $first = $this->firstNames[$i];
            $surname = $this->surnames[$i];
            $email = strtolower("fin.demo.{$first}.{$surname}.{$i}@tich.ac.ke");
            $program = $programList[$i % count($programList)];

            $existingId = DB::table('students')->where('registration_number', $reg)->value('id');
            if ($existingId) {
                $student = Student::query()->find($existingId);
                if ($student) {
                    $students[] = $student;
                }

                continue;
            }

            $applicantId = DB::table('applicants')->where('email', $email)->value('id');
            if (! $applicantId) {
                $applicantId = DB::table('applicants')->insertGetId([
                    'application_number' => 'APP-FIN-'.strtoupper(Str::random(6)),
                    'program_id' => $program->id,
                    'intake_year' => 2026,
                    'intake_month' => 1,
                    'preferred_campus_id' => $campusId,
                    'first_name' => $first,
                    'surname' => $surname,
                    'date_of_birth' => '2001-'.str_pad((string) (($i % 12) + 1), 2, '0', STR_PAD_LEFT).'-15',
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'email' => $email,
                    'phone_number' => '+2547'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
                    'status' => 'admitted',
                    'academic_review_status' => 'approved',
                    'created_at' => now(),
                ]);
            }

            $studentId = DB::table('students')->insertGetId([
                'registration_number' => $reg,
                'application_id' => $applicantId,
                'program_id' => $program->id,
                'cohort_intake' => IntakeIdentity::cohortLabel(2026, 1),
                'enrollment_campus_id' => $campusId,
                'current_semester_id' => $semesterId ?: null,
                'enrollment_status' => 'active',
                'entry_pathway' => 'regular',
                'date_of_admission' => '2026-01-15',
                'fee_clearance_status' => 'pending',
                'overall_balance' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $student = Student::query()->find($studentId);
            if ($student) {
                $students[] = $student;
            }
        }

        return $students;
    }

    /**
     * @param  array<int, FeeStructure>  $feeStructures
     */
    private function seedExistingStudentFinance(int $staffId, array $feeStructures): void
    {
        $invoiceService = app(InvoiceService::class);

        Student::query()
            ->where('registration_number', 'not like', self::DEMO_REG_PREFIX.'%')
            ->where('is_active', 1)
            ->limit(10)
            ->get()
            ->each(function (Student $student) use ($invoiceService, $staffId, $feeStructures) {
                $feeStructure = $feeStructures[$student->program_id] ?? reset($feeStructures);
                if (! $feeStructure) {
                    return;
                }

                if (Invoice::query()->where('student_id', $student->id)->exists()) {
                    return;
                }

                $invoiceService->generateSemesterInvoice($student, $feeStructure, $staffId, false);
            });
    }

    /**
     * @param  list<Student>  $students
     * @param  array<int, FeeStructure>  $feeStructures
     * @return array{invoices: int, payments: int}
     */
    private function seedStudentFinance(array $students, array $feeStructures, int $staffId): array
    {
        $invoiceService = app(InvoiceService::class);
        $paymentService = app(PaymentService::class);
        $creditMemoService = app(CreditMemoService::class);

        $methods = ['mpesa', 'bank_transfer', 'cash', 'helb', 'sponsor', 'eft', 'card', 'cheque'];
        $invoiceCount = 0;
        $paymentCount = 0;

        foreach ($students as $index => $student) {
            $student->loadMissing('program');
            $feeStructure = $feeStructures[$student->program_id] ?? reset($feeStructures);
            if (! $feeStructure) {
                continue;
            }

            $scenario = $index % 10;

            $hasTuitionInvoice = Invoice::query()
                ->where('student_id', $student->id)
                ->where('invoice_type', 'tuition')
                ->exists();

            if (! $hasTuitionInvoice) {
                $invoice = $invoiceService->generateSemesterInvoice($student, $feeStructure, $staffId, false);
                $invoiceCount++;

                if ($scenario === 7) {
                    $invoice->update([
                        'issue_date' => now()->subDays(75)->toDateString(),
                        'due_date' => now()->subDays(45)->toDateString(),
                        'status' => 'overdue',
                    ]);
                } elseif ($scenario === 8) {
                    $invoice->update([
                        'issue_date' => now()->subDays(60)->toDateString(),
                        'due_date' => now()->subDays(30)->toDateString(),
                        'status' => 'overdue',
                        'reminder_count' => 2,
                        'last_reminder_sent_at' => now()->subDays(7),
                    ]);
                } elseif (in_array($scenario, [0, 1, 2], true)) {
                    $invoice->update([
                        'issue_date' => now()->subDays(45)->toDateString(),
                        'due_date' => now()->subDays(15)->toDateString(),
                    ]);
                }
            } else {
                $invoice = Invoice::query()
                    ->where('student_id', $student->id)
                    ->where('invoice_type', 'tuition')
                    ->latest('id')
                    ->first();
            }

            if (! $invoice) {
                continue;
            }

            $invoice->refresh();
            $method = $methods[$index % count($methods)];

            if (in_array($scenario, [0, 1, 2, 3], true) && (float) $invoice->balance > 0) {
                $payRatio = match ($scenario) {
                    0, 1 => 1.0,
                    2 => 0.65,
                    3 => 0.35,
                    default => 0.5,
                };
                $payAmount = round((float) $invoice->balance * $payRatio, 2);

                if ($payAmount > 0 && $invoice->payments()->count() === 0) {
                    $paymentService->recordPayment($invoice, [
                        'amount' => $payAmount,
                        'payment_method' => $method,
                        'payment_reference' => strtoupper($method === 'mpesa' ? 'QGH' : 'REF').'-FIN-'.($index + 1),
                        'payment_date' => now()->subDays(rand(5, 40))->toDateString(),
                    ], $staffId, false);
                    $paymentCount++;
                    $invoice->refresh();
                }

                if ($scenario === 2 && (float) $invoice->balance > 0) {
                    $paymentService->recordPayment($invoice, [
                        'amount' => (float) $invoice->balance,
                        'payment_method' => 'bank_transfer',
                        'payment_reference' => 'BNK-FIN-'.($index + 1),
                        'payment_date' => now()->subDays(rand(1, 10))->toDateString(),
                    ], $staffId, false);
                    $paymentCount++;
                    $invoice->refresh();
                }
            }

            if ($scenario === 4 && (float) $invoice->balance > 0) {
                $this->seedInstallmentPlan($invoice, $staffId);
            }

            if ($scenario === 5) {
                $this->seedFinancialAdjustment($invoice, $staffId, 'scholarship', 5000, 'approved');
            }

            if ($scenario === 6) {
                $this->seedFinancialAdjustment($invoice, $staffId, 'bursary', 3000, 'pending');
            }

            if ($scenario === 9 && (float) $invoice->balance > 5000 && $invoice->payments()->count() > 0) {
                $creditMemoService->issue($invoice, 2500, 'Demo credit - lab fee waiver', $staffId);
            }

            if ($index % 7 === 0 && ! Invoice::query()->where('student_id', $student->id)->where('invoice_type', 'application')->exists()) {
                $invoiceService->generateApplicationInvoice($student, $feeStructure, $staffId);
                $invoiceCount++;
            }

            if ($index % 11 === 0 && ! Invoice::query()->where('student_id', $student->id)->where('invoice_type', 'graduation')->exists()) {
                $invoiceService->generateForStudent($student, [
                    'invoice_type' => 'graduation',
                    'description' => 'Graduation ceremony and certificate fees',
                    'amount' => (float) $feeStructure->graduation_fee,
                    'due_date' => now()->addMonths(2)->toDateString(),
                ], $staffId, false);
                $invoiceCount++;
            }

            if ($index % 13 === 0 && $invoice->payments()->exists()) {
                $this->seedRefundRequest($invoice, $staffId, $index);
            }
        }

        return ['invoices' => $invoiceCount, 'payments' => $paymentCount];
    }

    private function seedInstallmentPlan(Invoice $invoice, int $staffId): void
    {
        if (DB::table('installment_plans')->where('invoice_id', $invoice->id)->exists()) {
            return;
        }

        $balance = (float) $invoice->balance;
        if ($balance <= 0) {
            return;
        }

        $planId = DB::table('installment_plans')->insertGetId([
            'student_account_id' => $invoice->student_account_id,
            'student_id' => $invoice->student_id,
            'invoice_id' => $invoice->id,
            'plan_number' => 'PLAN-FIN-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT),
            'total_amount' => $balance,
            'paid_amount' => 0,
            'remaining_amount' => $balance,
            'status' => 'active',
            'created_at' => now(),
        ]);

        $installments = 4;
        $each = round($balance / $installments, 2);
        $remainder = round($balance - ($each * ($installments - 1)), 2);

        for ($n = 1; $n <= $installments; $n++) {
            $amount = $n === $installments ? $remainder : $each;
            $paid = $n === 1 ? min($amount, round($balance * 0.25, 2)) : 0;
            DB::table('installment_plan_items')->insert([
                'installment_plan_id' => $planId,
                'installment_number' => $n,
                'amount' => $amount,
                'due_date' => now()->addMonths($n)->toDateString(),
                'status' => $paid > 0 ? 'paid' : ($n === 2 ? 'overdue' : 'pending'),
                'paid_amount' => $paid,
                'paid_at' => $paid > 0 ? now()->subDays(10) : null,
            ]);
        }

        DB::table('installment_plans')->where('id', $planId)->update([
            'paid_amount' => round($balance * 0.25, 2),
            'remaining_amount' => round($balance * 0.75, 2),
        ]);
    }

    private function seedFinancialAdjustment(Invoice $invoice, int $staffId, string $type, float $amount, string $status): void
    {
        $exists = DB::table('financial_adjustments')
            ->where('student_id', $invoice->student_id)
            ->where('adjustment_type', $type)
            ->where('invoice_id', $invoice->id)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('financial_adjustments')->insert([
            'student_account_id' => $invoice->student_account_id,
            'student_id' => $invoice->student_id,
            'invoice_id' => $invoice->id,
            'adjustment_type' => $type,
            'reason' => ucfirst($type).' allocation - finance demo seed',
            'amount' => $amount,
            'status' => $status,
            'requested_by' => $staffId,
            'approved_by' => $status === 'approved' ? $staffId : null,
            'approved_at' => $status === 'approved' ? now()->subDays(3) : null,
            'created_at' => now()->subDays(7),
        ]);
    }

    private function seedRefundRequest(Invoice $invoice, int $staffId, int $index): void
    {
        $payment = $invoice->payments()->first();
        if (! $payment) {
            return;
        }

        $refundNumber = 'RF-FIN-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
        if (DB::table('refunds')->where('refund_number', $refundNumber)->exists()) {
            return;
        }

        DB::table('refunds')->insert([
            'refund_number' => $refundNumber,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'student_account_id' => $invoice->student_account_id,
            'student_id' => $invoice->student_id,
            'amount' => min(1500, (float) $payment->amount),
            'reason' => 'Duplicate payment - demo refund request',
            'status' => $index % 2 === 0 ? 'pending' : 'approved',
            'requested_by' => $staffId,
            'approved_by' => $index % 2 === 1 ? $staffId : null,
            'approved_at' => $index % 2 === 1 ? now()->subDay() : null,
            'created_at' => now()->subDays(2),
        ]);
    }

    /**
     * @return array{suppliers: int, ap: int}
     */
    private function seedSuppliersAndPayables(int $financeDeptId, int $staffId): array
    {
        $supplierDefs = [
            ['SUP-FIN-001', 'Kenya Office Supplies Ltd', 'compliant'],
            ['SUP-FIN-002', 'Nairobi IT Solutions', 'compliant'],
            ['SUP-FIN-003', 'East Africa Medical Equipment Co.', 'compliant'],
            ['SUP-FIN-004', 'Highland Stationers', 'pending_review'],
            ['SUP-FIN-005', 'Safari Catering Services', 'compliant'],
            ['SUP-FIN-006', 'Blue Nile Construction', 'compliant'],
            ['SUP-FIN-007', 'Lakeview Furniture Works', 'non_compliant'],
            ['SUP-FIN-008', 'Prime Cleaning & Hygiene', 'compliant'],
            ['SUP-FIN-009', 'TechHub Kenya Ltd', 'compliant'],
            ['SUP-FIN-010', 'Green Valley Farm Produce', 'compliant'],
        ];

        $supplierIds = [];
        foreach ($supplierDefs as [$code, $name, $compliance]) {
            $id = DB::table('suppliers')->where('supplier_code', $code)->value('id');
            if (! $id) {
                $id = DB::table('suppliers')->insertGetId([
                    'supplier_code' => $code,
                    'supplier_name' => $name,
                    'contact_person' => 'Accounts Manager',
                    'email' => strtolower(str_replace(' ', '.', $code)).'@supplier.demo.ke',
                    'phone' => '+2547'.rand(10000000, 99999999),
                    'postal_address' => 'P.O. Box '.rand(1000, 9999).', Nairobi',
                    'kra_pin' => 'A'.rand(100000000, 999999999).'X',
                    'tax_compliance_status' => $compliance,
                    'bank_name' => 'Equity Bank',
                    'bank_account_name' => $name,
                    'bank_account_number' => (string) rand(1000000000, 9999999999),
                    'is_active' => 1,
                    'created_at' => now()->subMonths(6),
                ]);
            }
            $supplierIds[$code] = (int) $id;
        }

        $apCount = 0;
        $statuses = ['unpaid', 'partial', 'paid', 'unpaid', 'unpaid'];
        $amounts = [85000, 125000, 42000, 210000, 67500, 98000, 156000, 33000, 89000, 112000, 245000, 54000, 77000, 188000, 61000];

        foreach ($amounts as $i => $amount) {
            $apNumber = 'AP-FIN-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            if (DB::table('accounts_payable')->where('invoice_number', $apNumber)->exists()) {
                continue;
            }

            $supplierCode = array_keys($supplierIds)[$i % count($supplierIds)];
            $supplierId = $supplierIds[$supplierCode];
            $status = $statuses[$i % count($statuses)];
            $paid = match ($status) {
                'paid' => $amount,
                'partial' => round($amount * 0.4, 2),
                default => 0,
            };
            $invoiceDate = now()->subDays(90 - ($i * 4));

            $apId = DB::table('accounts_payable')->insertGetId([
                'invoice_number' => $apNumber,
                'supplier_id' => $supplierId,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $invoiceDate->copy()->addDays(30)->toDateString(),
                'invoice_amount' => $amount,
                'tax_amount' => round($amount * 0.16, 2),
                'total_amount' => round($amount * 1.16, 2),
                'amount_paid' => $paid,
                'balance' => round($amount * 1.16 - $paid, 2),
                'three_way_match_status' => $i % 3 === 0 ? 'approved' : 'pending',
                'finance_approval_status' => $i % 4 === 0 ? 'approved' : 'pending',
                'finance_approved_by' => $i % 4 === 0 ? $staffId : null,
                'finance_approved_at' => $i % 4 === 0 ? now()->subDays(10) : null,
                'payment_status' => $status,
                'payment_date' => $status === 'paid' ? now()->subDays(5)->toDateString() : null,
                'payment_reference' => $status !== 'unpaid' ? 'EFT-AP-'.($i + 1) : null,
                'payment_method' => $status !== 'unpaid' ? 'eft' : null,
                'created_at' => $invoiceDate,
                'updated_at' => now(),
            ]);
            $apCount++;

            if ($i % 5 === 0) {
                $this->seedProcurementChain($financeDeptId, $staffId, $supplierId, $amount, (int) $apId);
            }
        }

        return ['suppliers' => count($supplierIds), 'ap' => $apCount];
    }

    private function seedProcurementChain(int $deptId, int $staffId, int $supplierId, float $amount, int $apId): void
    {
        $reqNumber = 'REQ-FIN-'.str_pad((string) $apId, 4, '0', STR_PAD_LEFT);
        $reqId = DB::table('procurement_requisitions')->where('requisition_number', $reqNumber)->value('id');

        if (! $reqId) {
            $reqId = DB::table('procurement_requisitions')->insertGetId([
                'requisition_number' => $reqNumber,
                'requesting_department_id' => $deptId,
                'requested_by' => $staffId,
                'request_date' => now()->subDays(60)->toDateString(),
                'justification' => 'Finance demo procurement - office and operational supplies',
                'estimated_cost' => $amount,
                'budget_code' => 'FIN-OPS-'.date('Y'),
                'status' => 'finance_approved',
                'hod_approval_status' => 'approved',
                'hod_approved_by' => $staffId,
                'hod_approved_at' => now()->subDays(55),
                'finance_approval_status' => 'approved',
                'finance_approved_by' => $staffId,
                'finance_approved_at' => now()->subDays(50),
                'created_at' => now()->subDays(60),
            ]);
        }

        $poNumber = 'PO-FIN-'.str_pad((string) $apId, 4, '0', STR_PAD_LEFT);
        $poId = DB::table('purchase_orders')->where('po_number', $poNumber)->value('id');

        if (! $poId) {
            $poId = DB::table('purchase_orders')->insertGetId([
                'po_number' => $poNumber,
                'supplier_id' => $supplierId,
                'requisition_id' => $reqId,
                'issue_date' => now()->subDays(45)->toDateString(),
                'delivery_date' => now()->subDays(30)->toDateString(),
                'total_amount' => $amount,
                'terms' => 'Net 30 days',
                'status' => 'delivered',
                'created_at' => now()->subDays(45),
            ]);

            DB::table('accounts_payable')->where('id', $apId)->update([
                'requisition_id' => $reqId,
                'purchase_order_id' => $poId,
            ]);
        }

        $grnNumber = 'GRN-FIN-'.str_pad((string) $apId, 4, '0', STR_PAD_LEFT);
        if (! DB::table('goods_received_notes')->where('grn_number', $grnNumber)->exists()) {
            DB::table('goods_received_notes')->insert([
                'grn_number' => $grnNumber,
                'purchase_order_id' => $poId,
                'supplier_delivery_note' => 'DN-'.rand(10000, 99999),
                'received_date' => now()->subDays(28)->toDateString(),
                'received_by' => $staffId,
                'inspection_status' => 'passed',
                'inspection_notes' => 'Goods received in good condition',
                'is_complete' => 1,
                'created_at' => now()->subDays(28),
            ]);
        }
    }

    private function seedBankTransactions(): int
    {
        if (DB::table('bank_transactions')->where('bank_reference', 'like', 'BTX-FIN-%')->count() >= 40) {
            return (int) DB::table('bank_transactions')->where('bank_reference', 'like', 'BTX-FIN-%')->count();
        }

        $balance = 2500000.00;
        $count = 0;

        for ($i = 1; $i <= 55; $i++) {
            $ref = 'BTX-FIN-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            if (DB::table('bank_transactions')->where('bank_reference', $ref)->exists()) {
                continue;
            }

            $isCredit = $i % 3 !== 0;
            $amount = round(rand(5000, 250000) + ($i * 127), 2);

            if ($isCredit) {
                $balance += $amount;
            } else {
                $amount = min($amount, $balance - 100000);
                $balance -= $amount;
            }

            DB::table('bank_transactions')->insert([
                'transaction_date' => now()->subDays(90 - $i)->toDateString(),
                'value_date' => now()->subDays(89 - $i)->toDateString(),
                'description' => $isCredit
                    ? 'Student fee collections / M-Pesa settlement batch #'.$i
                    : 'Supplier payment / payroll disbursement batch #'.$i,
                'debit_amount' => $isCredit ? 0 : $amount,
                'credit_amount' => $isCredit ? $amount : 0,
                'balance' => round($balance, 2),
                'bank_reference' => $ref,
                'eft_reference' => $isCredit ? null : 'EFT-OUT-'.$i,
                'is_reconciled' => $i % 4 === 0 ? 1 : 0,
                'reconciled_at' => $i % 4 === 0 ? now()->subDays(90 - $i) : null,
                'created_at' => now()->subDays(90 - $i),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * @return array{projects: int, disbursements: int}
     */
    private function seedDonorProjects(int $staffId): array
    {
        $projects = [
            ['PRJ-FIN-001', 'USAID Health Systems Strengthening', 'USAID', 'multilateral', 45000000, 'USD'],
            ['PRJ-FIN-002', 'Ford Foundation Scholarship Fund', 'Ford Foundation', 'foundation', 12000000, 'USD'],
            ['PRJ-FIN-003', 'County Bursary Support Programme', 'Nairobi County Government', 'government', 8500000, 'KES'],
            ['PRJ-FIN-004', 'WHO Nursing Capacity Building', 'World Health Organization', 'multilateral', 28000000, 'USD'],
        ];

        $projectCount = 0;
        $disbursementCount = 0;

        foreach ($projects as [$code, $name, $donor, $type, $grant, $currency]) {
            $projectId = DB::table('donor_projects')->where('project_code', $code)->value('id');
            if (! $projectId) {
                $projectId = DB::table('donor_projects')->insertGetId([
                    'project_code' => $code,
                    'project_name' => $name,
                    'donor_name' => $donor,
                    'donor_type' => $type,
                    'total_grant_amount' => $grant,
                    'currency' => $currency,
                    'disbursed_amount' => 0,
                    'disbursement_currency' => $currency,
                    'exchange_rate_at_disbursement' => $currency === 'USD' ? 129.50 : 1.0,
                    'kes_equivalent' => $currency === 'USD' ? $grant * 129.50 : $grant,
                    'start_date' => now()->subYear()->toDateString(),
                    'end_date' => now()->addYears(2)->toDateString(),
                    'project_leader_id' => $staffId,
                    'status' => 'active',
                    'created_at' => now()->subYear(),
                ]);
                $projectCount++;
            }

            for ($d = 1; $d <= 3; $d++) {
                $disbNumber = "DIS-{$code}-{$d}";
                if (DB::table('donor_disbursements')->where('disbursement_number', $disbNumber)->exists()) {
                    continue;
                }

                $received = round($grant * (0.15 + ($d * 0.08)), 2);
                $rate = $currency === 'USD' ? 129.50 : 1.0;

                DB::table('donor_disbursements')->insert([
                    'disbursement_number' => $disbNumber,
                    'donor_project_id' => $projectId,
                    'amount_received' => $received,
                    'currency_received' => $currency,
                    'exchange_rate' => $rate,
                    'kes_amount' => round($received * $rate, 2),
                    'receipt_date' => now()->subMonths(12 - ($d * 3))->toDateString(),
                    'bank_reference' => 'BNK-DON-'.str_replace('-', '', $code)."-{$d}",
                    'purpose' => "Tranche {$d} disbursement - {$name}",
                    'created_at' => now()->subMonths(12 - ($d * 3)),
                ]);
                $disbursementCount++;

                DB::table('donor_projects')->where('id', $projectId)->increment('disbursed_amount', $received);
            }
        }

        return ['projects' => $projectCount, 'disbursements' => $disbursementCount];
    }

    private function seedBudgets(int $financeDeptId, int $staffId): int
    {
        if (! DB::getSchemaBuilder()->hasTable('finance_budgets')) {
            return 0;
        }

        $departments = DB::table('departments')
            ->whereNull('parent_dept_id')
            ->where('is_active', 1)
            ->orderBy('dept_name')
            ->limit(8)
            ->get(['id', 'dept_code', 'dept_name']);

        $definitions = [
            ['BGT-FIN-INST', 'Institution operating budget', 'annual', null, 45000000, 12800000, 3200000],
            ['BGT-FIN-CAPEX', 'Capital expenditure programme', 'annual', null, 18000000, 4200000, 2100000],
        ];

        foreach ($departments as $index => $department) {
            $allocated = round(3500000 + ($index * 750000), 2);
            $definitions[] = [
                'BGT-FIN-'.strtoupper($department->dept_code ?? ('D'.$department->id)),
                $department->dept_name.' operations',
                'departmental',
                (int) $department->id,
                $allocated,
                round($allocated * (0.25 + ($index * 0.04)), 2),
                round($allocated * (0.12 + ($index * 0.02)), 2),
            ];
        }

        $count = 0;
        $year = (int) now()->format('Y');

        foreach ($definitions as [$code, $name, $type, $deptId, $allocated, $spent, $committed]) {
            $exists = DB::table('finance_budgets')->where('budget_code', $code)->exists();
            if ($exists) {
                continue;
            }

            DB::table('finance_budgets')->insert([
                'budget_code' => $code,
                'budget_name' => $name,
                'budget_type' => $type,
                'department_id' => $deptId,
                'fiscal_year' => $year,
                'period_start' => "{$year}-01-01",
                'period_end' => "{$year}-12-31",
                'allocated_amount' => $allocated,
                'spent_amount' => $spent,
                'committed_amount' => $committed,
                'status' => 'active',
                'approved_by' => $staffId,
                'approved_at' => now()->subMonths(2),
                'notes' => 'Finance demo budget - seeded for testing budget vs actual views.',
                'created_at' => now()->subMonths(3),
                'updated_at' => now(),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * @return array{assets: int, inventory: int}
     */
    private function seedAssetsAndInventory(): array
    {
        $assetCategories = ['furniture', 'equipment', 'it_hardware', 'vehicle', 'equipment'];
        $assetCount = 0;

        for ($i = 1; $i <= 22; $i++) {
            $assetNumber = 'AST-FIN-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            if (DB::table('assets')->where('asset_number', $assetNumber)->exists()) {
                continue;
            }

            $cost = round(rand(15000, 850000), 2);
            $years = rand(3, 10);
            $depreciation = round($cost / $years, 2);
            $accumulated = round($depreciation * rand(0, 3), 2);

            DB::table('assets')->insert([
                'asset_number' => $assetNumber,
                'asset_name' => match ($i % 5) {
                    0 => 'HP ProBook Laptop #'.$i,
                    1 => 'Executive Office Desk Set #'.$i,
                    2 => 'Laboratory Microscope #'.$i,
                    3 => 'Institution Van - Unit '.$i,
                    default => 'Multifunction Printer #'.$i,
                },
                'asset_category' => $assetCategories[$i % count($assetCategories)],
                'serial_number' => 'SN-FIN-'.strtoupper(Str::random(8)),
                'description' => 'Finance demo fixed asset register entry',
                'acquisition_date' => now()->subYears(rand(0, 4))->toDateString(),
                'acquisition_cost' => $cost,
                'useful_life_years' => $years,
                'depreciation_method' => 'straight_line',
                'salvage_value' => round($cost * 0.05, 2),
                'current_value' => max($cost - $accumulated, 0),
                'depreciation_per_year' => $depreciation,
                'accumulated_depreciation' => $accumulated,
                'condition' => $accumulated > ($cost * 0.5) ? 'fair' : 'good',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $assetCount++;
        }

        $inventoryItems = [
            ['INV-FIN-001', 'A4 Printing Paper (ream)', 'stationery', 450, 50, 800],
            ['INV-FIN-002', 'Surgical Gloves (box)', 'medical', 120, 30, 650],
            ['INV-FIN-003', 'Hand Sanitizer 500ml', 'hygiene', 85, 20, 320],
            ['INV-FIN-004', 'Whiteboard Markers (pack)', 'stationery', 60, 15, 450],
            ['INV-FIN-005', 'Ethernet Cable Cat6 (roll)', 'it', 25, 5, 3500],
            ['INV-FIN-006', 'Cleaning Detergent 5L', 'hygiene', 40, 10, 890],
            ['INV-FIN-007', 'Student ID Card Blanks', 'admin', 2000, 200, 25],
            ['INV-FIN-008', 'Laboratory Specimen Jars', 'medical', 180, 40, 120],
            ['INV-FIN-009', 'Toner Cartridge HP 85A', 'it', 18, 4, 4500],
            ['INV-FIN-010', 'First Aid Kit Refill Pack', 'medical', 35, 8, 2200],
            ['INV-FIN-011', 'Desk Chairs - Standard', 'furniture', 12, 3, 8500],
            ['INV-FIN-012', 'Fire Extinguisher 6kg', 'safety', 22, 5, 4200],
            ['INV-FIN-013', 'Library Barcode Labels', 'admin', 5000, 500, 2],
            ['INV-FIN-014', 'USB Flash Drive 32GB', 'it', 45, 10, 850],
            ['INV-FIN-015', 'Exam Answer Booklets', 'academic', 800, 100, 35],
        ];

        $inventoryCount = 0;
        $supplierId = DB::table('suppliers')->where('supplier_code', 'SUP-FIN-001')->value('id');
        $staffId = (int) (Staff::query()->value('id') ?? 1);

        foreach ($inventoryItems as [$code, $name, $category, $stock, $reorder, $unitCost]) {
            $itemId = DB::table('inventory_items')->where('item_code', $code)->value('id');
            if (! $itemId) {
                $itemId = DB::table('inventory_items')->insertGetId([
                    'item_code' => $code,
                    'item_name' => $name,
                    'category' => $category,
                    'unit_of_measure' => 'unit',
                    'current_stock' => $stock,
                    'minimum_stock' => $reorder,
                    'maximum_stock' => $stock * 3,
                    'reorder_level' => $reorder,
                    'unit_cost' => $unitCost,
                    'supplier_id' => $supplierId,
                    'store_location' => 'Main Store - Block B',
                    'is_active' => 1,
                    'created_at' => now()->subMonths(4),
                ]);
                $inventoryCount++;
            }

            if (! DB::table('inventory_transactions')->where('inventory_item_id', $itemId)->exists()) {
                DB::table('inventory_transactions')->insert([
                    'inventory_item_id' => $itemId,
                    'transaction_type' => 'purchase',
                    'quantity' => $stock,
                    'unit_cost' => $unitCost,
                    'total_cost' => $stock * $unitCost,
                    'reference_table' => 'purchase_orders',
                    'reference_id' => 'PO-FIN-DEMO',
                    'to_location' => 'Main Store - Block B',
                    'recorded_by' => $staffId,
                    'transaction_date' => now()->subMonths(3)->toDateString(),
                    'notes' => 'Initial stock - finance demo seed',
                    'created_at' => now()->subMonths(3),
                ]);

                if ($stock > $reorder * 2) {
                    DB::table('inventory_transactions')->insert([
                        'inventory_item_id' => $itemId,
                        'transaction_type' => 'issue',
                        'quantity' => (int) round($stock * 0.15),
                        'unit_cost' => $unitCost,
                        'total_cost' => round($stock * 0.15 * $unitCost, 2),
                        'from_location' => 'Main Store - Block B',
                        'recorded_by' => $staffId,
                        'transaction_date' => now()->subWeeks(2)->toDateString(),
                        'notes' => 'Department issue - finance demo',
                        'created_at' => now()->subWeeks(2),
                    ]);
                }
            }
        }

        return ['assets' => $assetCount, 'inventory' => $inventoryCount];
    }

    private function seedPayrollHistory(int $staffId): int
    {
        $financeStaff = Staff::query()
            ->whereIn('employee_number', ['EMP-FIN-001', 'EMP-HR-001'])
            ->orWhere('job_title', 'like', '%Finance%')
            ->limit(8)
            ->get();

        if ($financeStaff->isEmpty()) {
            $financeStaff = Staff::query()->limit(5)->get();
        }

        $count = 0;
        $months = collect(range(0, 5))->map(fn ($m) => now()->subMonths($m));

        foreach ($financeStaff as $member) {
            foreach ($months as $month) {
                $payslip = sprintf(
                    'PS-FIN-%s-%s',
                    $member->employee_number,
                    $month->format('Ym')
                );

                if (DB::table('payroll_items')->where('payslip_number', $payslip)->exists()) {
                    continue;
                }

                $basic = (float) ($member->gross_monthly_salary ?? 75000);
                $gross = round($basic * 1.08, 2);
                $deductions = round($gross * 0.28, 2);
                $net = round($gross - $deductions, 2);

                $payrollId = DB::table('payroll_items')->insertGetId([
                    'payslip_number' => $payslip,
                    'staff_id' => $member->id,
                    'pay_period_year' => (int) $month->format('Y'),
                    'pay_period_month' => (int) $month->format('n'),
                    'basic_salary' => $basic,
                    'gross_salary' => $gross,
                    'total_allowances' => round($gross - $basic, 2),
                    'total_deductions' => $deductions,
                    'net_salary' => $net,
                    'is_processed' => 1,
                    'processed_by' => $staffId,
                    'processed_at' => $month->copy()->endOfMonth(),
                    'is_approved' => 1,
                    'approved_by' => $staffId,
                    'approved_at' => $month->copy()->endOfMonth(),
                    'is_disbursed' => $month->month !== now()->month ? 1 : 0,
                    'disbursement_date' => $month->month !== now()->month ? $month->copy()->endOfMonth()->toDateString() : null,
                    'eft_reference' => $month->month !== now()->month ? 'EFT-PAY-'.$month->format('Ym').'-'.$member->id : null,
                    'created_at' => $month->copy()->endOfMonth(),
                ]);
                $count++;

                foreach (['paye', 'nssf_tier1', 'sha'] as $type) {
                    DB::table('statutory_deductions')->insert([
                        'payroll_item_id' => $payrollId,
                        'staff_id' => $member->id,
                        'deduction_type' => $type,
                        'gross_salary_for_deduction' => $gross,
                        'employee_amount' => round($deductions / 3, 2),
                        'employer_amount' => round($deductions / 6, 2),
                        'is_remitted' => $month->month !== now()->month ? 1 : 0,
                        'remittance_date' => $month->month !== now()->month ? $month->copy()->addMonth()->day(9)->toDateString() : null,
                        'created_at' => $month->copy()->endOfMonth(),
                    ]);
                }
            }
        }

        return $count;
    }

    /**
     * @param  list<Student>  $students
     */
    private function seedWorkStudyLedger(array $students, int $staffId): int
    {
        $count = 0;
        $projects = ['Library shelving', 'Campus grounds maintenance', 'ICT helpdesk support', 'Registry filing', 'Lab preparation'];

        foreach (array_slice($students, 0, 12) as $index => $student) {
            $exists = DB::table('work_study_ledger')
                ->where('student_id', $student->id)
                ->where('project_name', $projects[$index % count($projects)])
                ->exists();

            if ($exists) {
                continue;
            }

            $hours = rand(8, 40);
            $rate = 150;
            $earnings = $hours * $rate;
            $offset = min($earnings, 6000);

            DB::table('work_study_ledger')->insert([
                'student_id' => $student->id,
                'project_name' => $projects[$index % count($projects)],
                'hours_logged' => $hours,
                'hourly_rate' => $rate,
                'total_earnings' => $earnings,
                'tuition_offset_amount' => $offset,
                'offset_reference' => 'WS-FIN-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'work_date' => now()->subDays(rand(10, 60))->toDateString(),
                'verified_by' => $staffId,
                'verified_at' => now()->subDays(rand(5, 30)),
                'status' => $index % 3 === 0 ? 'offset_applied' : 'verified',
                'created_at' => now()->subDays(rand(10, 60)),
            ]);
            $count++;
        }

        return $count;
    }
}
