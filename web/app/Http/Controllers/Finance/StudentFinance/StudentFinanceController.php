<?php

namespace App\Http\Controllers\Finance\StudentFinance;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Finance\FeeStructure;
use App\Models\Finance\FinancialAdjustment;
use App\Models\Finance\InstallmentPlan;
use App\Models\Finance\InstallmentPlanItem;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceItem;
use App\Models\Finance\Payment;
use App\Models\Finance\PaymentMilestone;
use App\Models\Finance\Receipt;
use App\Models\Finance\StudentAccount;
use App\Models\Student;
use App\Services\DepartmentDashboardService;
use App\Services\Finance\InvoiceService;
use App\Services\Finance\StudentFinanceService;
use App\Services\PrintDocumentService;
use App\Support\SafeWrite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentFinanceController extends Controller
{
    /** Students billed per click; the screen keeps a cursor for the rest. */
    private const BULK_INVOICE_BATCH = 50;

    public function __construct(
        protected DepartmentDashboardService $departmentDashboard,
        protected StudentFinanceService $financeService,
        protected InvoiceService $invoiceService,
        protected \App\Services\FinanceDashboardStatsService $stats,
    ) {
    }

    protected function view(Request $request, string $view, Department $department, array $data = []): View
    {
        $sidebarNavigation = $this->departmentDashboard->sidebarNavigation($request->user(), $department);

        return view($view, array_merge([
            'department' => $department,
            'categoryLabel' => fn (Department $dept) => $this->departmentDashboard->categoryLabel($dept),
            'sidebarNavigation' => $sidebarNavigation,
        ], $data));
    }

    public function index(Request $request, Department $department): View
    {
        $stats = [
            'total_accounts' => 0,
            'outstanding_balance' => 0,
            'pending_invoices' => 0,
            'credit_balances' => 0,
        ];

        if (Schema::hasTable('student_accounts')) {
            $stats['total_accounts'] = StudentAccount::count();
            $stats['outstanding_balance'] = StudentAccount::sum('outstanding_balance');
            if (Schema::hasColumn('student_accounts', 'credit_balance')) {
                $stats['credit_balances'] = StudentAccount::where('credit_balance', '>', 0)->count();
            }
        }

        if (Schema::hasTable('invoices')) {
            $stats['pending_invoices'] = Invoice::whereIn('status', ['issued', 'partial', 'overdue'])->count();
        }

        return $this->view($request, 'finance.student-finance.index', $department, [
            'stats' => $stats,
            'chartData' => $this->stats->studentFinanceChartData(),
        ]);
    }

    public function accounts(Request $request, Department $department): View
    {
        $search = trim((string) $request->query('search', ''));
        $programId = (int) ($request->query('program_id', 0) ?: 0);
        $programs = $this->activeProgramsForFilter();

        if (! Schema::hasTable('student_accounts') || StudentAccount::count() === 0) {
            $mockStudent1 = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };
            $mockStudent2 = new class {
                public function fullName(): string { return 'Otieno Daniel'; }
                public $registration_number = 'STU/2024/045';
            };
            $mockStudent3 = new class {
                public function fullName(): string { return 'Achieng Faith'; }
                public $registration_number = 'STU/2024/102';
            };
            $mockYear = new class { public $year_label = '2024/2025'; };

            $accounts = collect([
                (object) [
                    'id' => 1,
                    'student' => $mockStudent1,
                    'academicYear' => $mockYear,
                    'total_chargeable' => 61000,
                    'total_paid' => 30500,
                    'outstanding_balance' => 30500,
                    'credit_balance' => 0,
                    'is_cleared' => false,
                    'last_payment_date' => now()->subDays(5),
                ],
                (object) [
                    'id' => 2,
                    'student' => $mockStudent2,
                    'academicYear' => $mockYear,
                    'total_chargeable' => 85000,
                    'total_paid' => 85000,
                    'outstanding_balance' => 0,
                    'credit_balance' => 5000,
                    'is_cleared' => true,
                    'last_payment_date' => now()->subDays(10),
                ],
                (object) [
                    'id' => 3,
                    'student' => $mockStudent3,
                    'academicYear' => $mockYear,
                    'total_chargeable' => 72000,
                    'total_paid' => 36000,
                    'outstanding_balance' => 36000,
                    'credit_balance' => 0,
                    'is_cleared' => false,
                    'last_payment_date' => now()->subDays(2),
                ],
            ]);
        } else {
            $query = StudentAccount::query()
                ->with(['student.program', 'academicYear']);

            if ($search !== '') {
                $query->whereHas('student', fn (Builder $sq) => $this->applyStudentSearchOnQuery($sq, $search));
            }

            if ($programId > 0) {
                $query->whereHas('student', fn (Builder $sq) => $sq->where('program_id', $programId));
            }

            $accounts = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        }

        return $this->view($request, 'finance.student-finance.accounts.index', $department, [
            'accounts' => $accounts,
            'search' => $search,
            'programId' => $programId,
            'programs' => $programs,
        ]);
    }

    public function accountShow(Request $request, int $id, Department $department): View
    {
        if (! Schema::hasTable('student_accounts') || StudentAccount::count() === 0) {
            $mockStudent1 = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };
            $mockYear = new class { public $year_label = '2024/2025'; };
            $mockSemester1 = new class { public $semester_label = 'Semester 1'; };
            $mockSemester2 = new class { public $semester_label = 'Semester 2'; };

            $account = (object) [
                'id' => $id,
                'student' => $mockStudent1,
                'academicYear' => $mockYear,
                'total_chargeable' => 61000,
                'total_paid' => 30500,
                'outstanding_balance' => 30500,
                'credit_balance' => 0,
                'is_cleared' => false,
                'last_payment_date' => now()->subDays(5),
                'scholarship_amount' => 5000,
                'helb_amount' => 0,
                'sponsor_amount' => 0,
                'work_study_credit' => 0,
                'invoices' => collect([
                    (object) [
                        'id' => 1,
                        'invoice_number' => 'INV-2024-0001',
                        'invoice_type' => 'tuition',
                        'amount' => 50000,
                        'amount_paid' => 25000,
                        'balance' => 25000,
                        'status' => 'partial',
                        'due_date' => now()->addDays(15),
                        'issue_date' => now()->subDays(10),
                        'semester' => $mockSemester1,
                'items' => collect([
                    (object) ['fee_item' => 'Tuition', 'description' => 'Semester tuition fee', 'amount' => 45000, 'scholarship_adjustment' => 0, 'bursary_adjustment' => 0, 'waiver_adjustment' => 0, 'net_amount' => 45000],
                    (object) ['fee_item' => 'Examination', 'description' => 'Final examination fee', 'amount' => 3000, 'scholarship_adjustment' => 0, 'bursary_adjustment' => 0, 'waiver_adjustment' => 0, 'net_amount' => 3000],
                    (object) ['fee_item' => 'Library', 'description' => 'Library access and resources', 'amount' => 2000, 'scholarship_adjustment' => 0, 'bursary_adjustment' => 0, 'waiver_adjustment' => 0, 'net_amount' => 2000],
                ]),
                        'payments' => collect([
                            (object) [
                                'id' => 1,
                                'payment_number' => 'PAY-2024-0001',
                                'amount' => 25000,
                                'payment_method' => 'mpesa',
                                'payment_reference' => 'SFE123ABC456',
                                'payment_date' => now()->subDays(5),
                                'receipt' => (object) ['receipt_number' => 'RCP-2024-0001'],
                            ],
                        ]),
                    ],
                    (object) [
                        'id' => 2,
                        'invoice_number' => 'INV-2024-0002',
                        'invoice_type' => 'hostel',
                        'amount' => 15000,
                        'amount_paid' => 5500,
                        'balance' => 9500,
                        'status' => 'partial',
                        'due_date' => now()->addDays(30),
                        'issue_date' => now()->subDays(3),
                        'semester' => $mockSemester2,
                        'items' => collect([
                            (object) ['fee_item' => 'Hostel', 'description' => 'Semester hostel accommodation', 'amount' => 15000, 'scholarship_adjustment' => 0, 'bursary_adjustment' => 0, 'waiver_adjustment' => 0, 'net_amount' => 15000],
                        ]),
                        'payments' => collect([
                            (object) [
                                'id' => 2,
                                'payment_number' => 'PAY-2024-0002',
                                'amount' => 5500,
                                'payment_method' => 'cash',
                                'payment_reference' => 'CASH-REF-001',
                                'payment_date' => now()->subDays(2),
                                'receipt' => (object) ['receipt_number' => 'RCP-2024-0002'],
                            ],
                        ]),
                    ],
                ]),
                'payments' => collect([
                    (object) [
                        'id' => 1,
                        'payment_number' => 'PAY-2024-0001',
                        'amount' => 25000,
                        'payment_method' => 'mpesa',
                        'payment_reference' => 'SFE123ABC456',
                        'payment_date' => now()->subDays(5),
                        'invoice' => (object) ['invoice_number' => 'INV-2024-0001'],
                    ],
                    (object) [
                        'id' => 2,
                        'payment_number' => 'PAY-2024-0002',
                        'amount' => 5500,
                        'payment_method' => 'cash',
                        'payment_reference' => 'CASH-REF-001',
                        'payment_date' => now()->subDays(2),
                        'invoice' => (object) ['invoice_number' => 'INV-2024-0002'],
                    ],
                ]),
                'adjustments' => collect([
                    (object) [
                        'id' => 1,
                        'adjustment_type' => 'scholarship',
                        'amount' => 5000,
                        'reason' => 'Merit scholarship',
                        'status' => 'approved',
                        'created_at' => now()->subDays(15),
                        'requestedBy' => new class { public function fullName(): string { return 'Admin User'; } },
                    ],
                ]),
                'milestones' => collect([]),
                'installmentPlans' => collect([]),
            ];
        } else {
            $account = StudentAccount::with($this->studentAccountRelations())->findOrFail($id);

            $account = $this->financeService->recalculateAccount($account);
        }

        return $this->view($request, 'finance.student-finance.accounts.show', $department, [
            'account' => $account,
        ]);
    }

    public function feeStructures(Request $request, Department $department): View
    {
        if (! Schema::hasTable('fee_structures') || FeeStructure::count() === 0) {
            $mockProgram = new class { public $program_name = 'Bachelor of Science in Computer Science'; };
            $mockYear = new class { public $year_label = '2024/2025'; };

            $structures = collect([
                (object) [
                    'id' => 1,
                    'program' => $mockProgram,
                    'academicYear' => $mockYear,
                    'semester_number' => 1,
                    'total_semester_fee' => 85000,
                    'is_active' => true,
                    'effective_from' => now()->subMonths(2),
                ],
                (object) [
                    'id' => 2,
                    'program' => new class { public $program_name = 'Bachelor of Business Administration'; },
                    'academicYear' => $mockYear,
                    'semester_number' => 1,
                    'total_semester_fee' => 72000,
                    'is_active' => true,
                    'effective_from' => now()->subMonths(2),
                ],
                (object) [
                    'id' => 3,
                    'program' => new class { public $program_name = 'Diploma in Information Technology'; },
                    'academicYear' => $mockYear,
                    'semester_number' => 2,
                    'total_semester_fee' => 45000,
                    'is_active' => false,
                    'effective_from' => now()->subMonths(1),
                ],
            ]);
        } else {
            $structures = FeeStructure::query()
                ->with(['program', 'academicYear'])
                ->orderByDesc('effective_from')
                ->paginate(20);
        }

        return $this->view($request, 'finance.student-finance.fee-structures.index', $department, [
            'structures' => $structures,
        ]);
    }

    public function feeStructureCreate(Request $request, Department $department): View
    {
        return $this->view($request, 'finance.student-finance.fee-structures.create', $department);
    }

    public function feeStructureStore(Request $request, Department $department)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:academic_programs,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'application_fee' => 'nullable|numeric|min:0',
            'tuition_fee' => 'nullable|numeric|min:0',
            'cautions_fee' => 'nullable|numeric|min:0',
            'computer_lab_fee' => 'nullable|numeric|min:0',
            'accommodation_fee' => 'nullable|numeric|min:0',
            'transport_fee' => 'nullable|numeric|min:0',
            'partnership_fee' => 'nullable|numeric|min:0',
            'id_card_fee' => 'nullable|numeric|min:0',
            'student_union_fee' => 'nullable|numeric|min:0',
            'quality_assurance_fee' => 'nullable|numeric|min:0',
            'emergency_fund_fee' => 'nullable|numeric|min:0',
            'library_fee' => 'nullable|numeric|min:0',
            'indexing_nck_fee' => 'nullable|numeric|min:0',
            'examination_external_fee' => 'nullable|numeric|min:0',
            'attachment_fee' => 'nullable|numeric|min:0',
            'graduation_fee' => 'nullable|numeric|min:0',
            'other_fees' => 'nullable|array',
            'effective_from' => 'required|date',
        ]);

        $validated['total_semester_fee'] = collect([
            $validated['application_fee'] ?? 0,
            $validated['tuition_fee'] ?? 0,
            $validated['cautions_fee'] ?? 0,
            $validated['computer_lab_fee'] ?? 0,
            $validated['accommodation_fee'] ?? 0,
            $validated['transport_fee'] ?? 0,
            $validated['partnership_fee'] ?? 0,
            $validated['id_card_fee'] ?? 0,
            $validated['student_union_fee'] ?? 0,
            $validated['quality_assurance_fee'] ?? 0,
            $validated['emergency_fund_fee'] ?? 0,
            $validated['library_fee'] ?? 0,
            $validated['indexing_nck_fee'] ?? 0,
            $validated['examination_external_fee'] ?? 0,
            $validated['attachment_fee'] ?? 0,
            $validated['graduation_fee'] ?? 0,
            ...(collect($validated['other_fees'] ?? [])->map(fn ($v) => (float) $v)->all()),
        ])->sum();

        $validated['is_active'] = true;
        $validated['is_approved'] = true;
        $validated['approved_by'] = $request->user()->id;
        $validated['approved_at'] = now();
        $validated['created_at'] = now();

        FeeStructure::create($validated);

        return redirect()->route('finance.student-finance.fee-structures.index')->with('success', 'Fee structure created successfully.');
    }

    public function invoices(Request $request, Department $department): View
    {
        $search = trim((string) $request->query('search', ''));
        $programId = (int) ($request->query('program_id', 0) ?: 0);
        $programs = $this->activeProgramsForFilter();
        $groupedByStudent = $request->query('view') !== 'invoices';

        // One row per student; null keeps the list of every bill.
        $grouped = null;
        $invoices = collect();

        if (! Schema::hasTable('invoices') || Invoice::count() === 0) {
            $mockStudent1 = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };
            $mockStudent2 = new class {
                public function fullName(): string { return 'Otieno Daniel'; }
                public $registration_number = 'STU/2024/045';
            };
            $mockStudent3 = new class {
                public function fullName(): string { return 'Achieng Faith'; }
                public $registration_number = 'STU/2024/102';
            };
            $mockAccount = new class { public $id = 1; };

            $invoices = collect([
                (object) [
                    'id' => 1,
                    'invoice_number' => 'INV-2024-0001',
                    'student' => $mockStudent1,
                    'studentAccount' => $mockAccount,
                    'invoice_type' => 'tuition',
                    'amount' => 85000,
                    'amount_paid' => 42500,
                    'balance' => 42500,
                    'status' => 'partial',
                    'due_date' => now()->addDays(15),
                    'issue_date' => now()->subDays(10),
                ],
                (object) [
                    'id' => 2,
                    'invoice_number' => 'INV-2024-0002',
                    'student' => $mockStudent2,
                    'studentAccount' => $mockAccount,
                    'invoice_type' => 'application',
                    'amount' => 5000,
                    'amount_paid' => 5000,
                    'balance' => 0,
                    'status' => 'paid',
                    'due_date' => now()->subDays(5),
                    'issue_date' => now()->subDays(20),
                ],
                (object) [
                    'id' => 3,
                    'invoice_number' => 'INV-2024-0003',
                    'student' => $mockStudent3,
                    'studentAccount' => $mockAccount,
                    'invoice_type' => 'hostel',
                    'amount' => 35000,
                    'amount_paid' => 0,
                    'balance' => 35000,
                    'status' => 'issued',
                    'due_date' => now()->addDays(30),
                    'issue_date' => now()->subDays(3),
                ],
            ]);
        } else {
            if ($groupedByStudent) {
                $grouped = $this->studentInvoiceRollup($search, $programId);
            } else {
                $invoiceRelations = ['student', 'studentAccount'];
                if (Schema::hasTable('invoice_items')) {
                    $invoiceRelations[] = 'items';
                }

                $query = Invoice::query()->with(array_merge($invoiceRelations, ['student.program']));

                if ($search !== '') {
                    $query->where(function (Builder $q) use ($search) {
                        $q->where('invoice_number', 'like', "%{$search}%")
                            ->orWhereHas('student', fn (Builder $sq) => $this->applyStudentSearchOnQuery($sq, $search));
                    });
                }

                if ($programId > 0) {
                    $query->whereHas('student', fn (Builder $sq) => $sq->where('program_id', $programId));
                }

                $invoices = $query->orderByDesc('issue_date')->paginate(20)->withQueryString();
            }
        }

        return $this->view($request, 'finance.student-finance.invoices.index', $department, [
            'invoices' => $invoices,
            'grouped' => $grouped,
            'search' => $search,
            'programId' => $programId,
            'programs' => $programs,
        ]);
    }

    /**
     * Every bill a student has folded into one row, so a person no longer spreads
     * down the table. "View" opens the account page, which lists all their
     * invoices and payments together.
     *
     * @return LengthAwarePaginator<int, object>
     */
    private function studentInvoiceRollup(string $search, int $programId): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->selectRaw(
                'student_id, COUNT(*) as bill_count,'
                .'SUM(amount) as billed_total, SUM(amount_paid) as paid_total, SUM(balance) as balance_total,'
                ."SUM(CASE WHEN status IN ('issued','partial','overdue') THEN 1 ELSE 0 END) as open_count,"
                .'MAX(issue_date) as last_billed, MAX(due_date) as next_due'
            )
            ->whereNotNull('student_id')
            ->groupBy('student_id');

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('student', fn (Builder $sq) => $this->applyStudentSearchOnQuery($sq, $search))
                    ->orWhere('invoice_number', 'like', "%{$search}%");
            });
        }

        if ($programId > 0) {
            $query->whereHas('student', fn (Builder $sq) => $sq->where('program_id', $programId));
        }

        $rollup = $query->orderByDesc('last_billed')->orderByDesc('student_id')->paginate(20)->withQueryString();
        $ids = collect($rollup->items())->map(fn (Invoice $row): int => (int) $row->student_id)->all();

        $students = $ids === []
            ? collect()
            : Student::query()->with('program:id,program_name')->whereIn('id', $ids)->get()->keyBy('id');
        // Ascending id so keyBy keeps each student's newest account; that page shows
        // every year's bills anyway, the account id only has to exist.
        $accounts = $ids === []
            ? collect()
            : StudentAccount::query()->whereIn('student_id', $ids)->orderBy('id')->get()->keyBy('student_id');

        return $rollup->through(function (Invoice $row) use ($students, $accounts): object {
            $student = $students->get((int) $row->student_id);
            $account = $accounts->get((int) $row->student_id);
            $lastBilled = $row->getAttribute('last_billed');
            $nextDue = $row->getAttribute('next_due');

            return (object) [
                'student_id' => (int) $row->student_id,
                'account_id' => $account?->id,
                'name' => $student?->fullName() ?? 'Student #'.$row->student_id,
                'registration_number' => $student->registration_number ?? null,
                'program_name' => $student?->program?->program_name ?? null,
                'bill_count' => (int) $row->getAttribute('bill_count'),
                'open_count' => (int) $row->getAttribute('open_count'),
                'billed_total' => (float) $row->getAttribute('billed_total'),
                'paid_total' => (float) $row->getAttribute('paid_total'),
                'balance_total' => (float) $row->getAttribute('balance_total'),
                'last_billed' => $lastBilled === null ? null : Carbon::parse($lastBilled)->format('d M Y'),
                'next_due' => $nextDue === null ? null : Carbon::parse($nextDue)->format('d M Y'),
            ];
        });
    }

    public function invoiceCreate(Request $request, Department $department): View
    {
        // The picker labels students by name, so the name columns have to come back
        // with the record or the list falls back to showing the ID twice.
        $students = Student::query()
            ->select('id', 'first_name', 'middle_name', 'surname', 'registration_number', 'application_id')
            ->with(['applicant:id,first_name,middle_name,surname'])
            ->orderBy('registration_number')
            ->get();

        $feeStructures = FeeStructure::query()
            ->with(['program:id,program_name', 'academicYear:id,year_label'])
            ->orderByDesc('id')
            ->get();

        return $this->view($request, 'finance.student-finance.invoices.create', $department, [
            'students' => $students,
            'feeStructures' => $feeStructures,
            'activeStudents' => $this->invoiceService->activeStudentCount(),
            // What each student already paid in above their bills; the invoice spends it
            // before the student is asked for cash again.
            'studentCredits' => StudentAccount::query()
                ->whereIn('student_id', $students->pluck('id'))
                ->where('credit_balance', '>', 0)
                ->selectRaw('student_id, SUM(credit_balance) as credit_balance')
                ->groupBy('student_id')
                ->pluck('credit_balance', 'student_id'),
        ]);
    }

    public function invoiceStore(Request $request, Department $department)
    {
        $validated = $request->validate($this->billRules() + ['student_id' => 'required|exists:students,id']);

        $student = Student::findOrFail($validated['student_id']);
        $invoice = $this->issueBill($student, $validated, $this->recordingStaffId($request));

        $message = 'Invoice created and posted to the ledger.';
        if ((float) $invoice->amount_paid > 0) {
            $message = 'Invoice created. KES '.number_format((float) $invoice->amount_paid, 2)
                .' of the student\'s excess payment was deducted, leaving KES '
                .number_format((float) $invoice->balance, 2).' to pay.';
        }

        return redirect()->route('finance.student-finance.invoices.index')->with('success', $message);
    }

    /**
     * Raise the same bill for every active student, one batch per click so the host's
     * PHP worker is never held for the whole cohort.
     */
    public function invoiceBulkStore(Request $request, Department $department)
    {
        $validated = $request->validate($this->billRules());

        // A batch is deliberately long work; the default request limit would kill it
        // part-way through and leave the officer without the cursor to carry on.
        set_time_limit(300);

        $staffId = $this->recordingStaffId($request);
        $result = $this->invoiceService->billActiveStudents(
            (int) $request->input('bulk_after_id', 0),
            self::BULK_INVOICE_BATCH,
            fn (Student $student) => $this->issueBill($student, $validated, $staffId),
        );

        $raised = 'Bills raised for '.$result['raised'].' students.';
        if ($result['existing'] > 0) {
            $raised .= ' '.$result['existing'].' already had this bill open and were left alone.';
        }

        if ($result['remaining'] > 0) {
            return redirect()
                ->route('finance.student-finance.invoices.create', [
                    'bill_all' => 1,
                    'bulk_after_id' => $result['after_id'],
                ])
                ->withInput()
                ->with('warning', $raised.' '.$result['remaining'].' students are still to be billed — press "Bill all students" again to carry on.');
        }

        return redirect()
            ->route('finance.student-finance.invoices.index')
            ->with('success', $raised.' Every student has their own invoice.');
    }

    /** @return array<string, string> */
    private function billRules(): array
    {
        return [
            'fee_structure_id' => 'nullable|exists:fee_structures,id',
            'invoice_type' => 'required|string|in:tuition,application,supplementary,graduation,hostel,other',
            'description' => 'required|string|max:500',
            'due_date' => 'required|date',
            'amount' => 'nullable|numeric|min:0.01',
        ];
    }

    private function recordingStaffId(Request $request): int
    {
        return (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id') ?? 1);
    }

    /** @param  array<string, mixed>  $validated */
    private function issueBill(Student $student, array $validated, int $staffId)
    {
        $invoiceTypeMap = [
            'hostel' => 'other',
        ];
        $invoiceType = $invoiceTypeMap[$validated['invoice_type']] ?? $validated['invoice_type'];

        if (! empty($validated['fee_structure_id'])) {
            $structure = FeeStructure::findOrFail($validated['fee_structure_id']);
            $invoice = $this->invoiceService->generateSemesterInvoice($student, $structure, $staffId);
            $invoice->update([
                'due_date' => $validated['due_date'],
                'fee_structure_id' => $structure->id,
            ]);

            return $invoice;
        }

        $amount = (float) ($validated['amount'] ?? 0);
        abort_if($amount <= 0, 422, 'Amount is required when no fee structure is selected.');

        return $this->invoiceService->generateForStudent($student, [
            'invoice_type' => $invoiceType,
            'description' => $validated['description'],
            'amount' => $amount,
            'due_date' => $validated['due_date'],
        ], $staffId);
    }

    public function invoiceShow(Request $request, int $id, Department $department): View
    {
        if (! Schema::hasTable('invoices') || Invoice::count() === 0) {
            $mockStudent = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };
            $mockAccount = new class { public $id = 1; };

            $invoice = (object) [
                'id' => $id,
                'invoice_number' => 'INV-2024-0001',
                'student' => $mockStudent,
                'studentAccount' => $mockAccount,
                'invoice_type' => 'tuition',
                'amount' => 85000,
                'amount_paid' => 42500,
                'balance' => 42500,
                'status' => 'partial',
                'due_date' => now()->addDays(15),
                'issue_date' => now()->subDays(10),
                'description' => 'Semester 1 tuition fees',
                'feeStructure' => (object) [
                    'program' => (object) ['program_name' => 'Bachelor of Science in Computer Science'],
                    'academicYear' => (object) ['year_label' => '2024/2025'],
                ],
                'items' => collect([
                    new InvoiceItem(['fee_item' => 'tuition_fee', 'description' => 'Semester tuition fee', 'amount' => 45000, 'net_amount' => 45000]),
                    new InvoiceItem(['fee_item' => 'examination_external_fee', 'description' => 'Final examination fee', 'amount' => 3000, 'net_amount' => 3000]),
                    new InvoiceItem(['fee_item' => 'library_fee', 'description' => 'Library access and resources', 'amount' => 2000, 'net_amount' => 2000]),
                ]),
                'payments' => collect([
                    (object) [
                        'id' => 1,
                        'payment_number' => 'PAY-2024-0001',
                        'amount' => 42500,
                        'payment_method' => 'mpesa',
                        'payment_reference' => 'SFE123ABC456',
                        'payment_date' => now()->subDays(5),
                        'receipt' => (object) ['receipt_number' => 'RCP-2024-0001'],
                    ],
                ]),
                'receipts' => collect([
                    (object) [
                        'id' => 1,
                        'receipt_number' => 'RCP-2024-0001',
                        'amount' => 42500,
                        'payment_method' => 'mpesa',
                        'issued_at' => now()->subDays(5),
                    ],
                ]),
            ];
        } else {
            $invoiceRelations = ['student', 'studentAccount', 'payments'];
            if (Schema::hasTable('invoice_items')) {
                $invoiceRelations[] = 'items';
            }
            if (Schema::hasTable('receipts')) {
                $invoiceRelations[] = 'receipts';
            }

            $invoice = Invoice::with($invoiceRelations)->findOrFail($id);
        }

        return $this->view($request, 'finance.student-finance.invoices.show', $department, [
            'invoice' => $invoice,
            'invoiceLines' => $this->invoiceLines($invoice),
        ]);
    }

    public function invoiceDownload(Request $request, int $id, Department $department): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $invoice = Schema::hasTable('invoices') && Invoice::count() > 0
            ? Invoice::with(['student', 'items', 'payments'])->findOrFail($id)
            : (object) [
                'id' => $id,
                'invoice_number' => 'INV-2024-0001',
                'student' => new class { public function fullName(): string { return 'Wanjiku Mwangi'; } public $registration_number = 'STU/2024/001'; },
                'invoice_type' => 'tuition',
                'amount' => 85000,
                'amount_paid' => 42500,
                'balance' => 42500,
                'status' => 'partial',
                'due_date' => now()->addDays(15),
                'issue_date' => now()->subDays(10),
                'description' => 'Semester 1 tuition fees',
                'items' => collect([]),
                'payments' => collect([]),
            ];

        return app(\App\Services\PrintDocumentService::class)->downloadPdf(
            'finance.student-finance.invoices.pdf',
            [
                'department' => $department,
                'invoice' => $invoice,
                'invoiceLines' => $this->invoiceLines($invoice),
            ],
            'invoice-'.$invoice->invoice_number.'.pdf'
        );
    }

    /** The charges making up a bill, for the screen table and the printed invoice. */
    private function invoiceLines(object $invoice): Collection
    {
        return $invoice instanceof Invoice
            ? $invoice->lineItems()
            : collect($invoice->items ?? []);
    }

    public function payments(Request $request, Department $department): View
    {
        $search = trim((string) $request->query('search', ''));
        $semesterId = (int) ($request->query('semester_id', 0) ?: 0);
        $academicYearId = (int) ($request->query('academic_year_id', 0) ?: 0);
        $programId = (int) ($request->query('program_id', 0) ?: 0);

        if (! Schema::hasTable('payments') || Payment::count() === 0) {
            $mockProgram1 = new class {
                public $program_code = 'BSC-CS';
                public $program_name = 'Bachelor of Science in Computer Science';
            };
            $mockProgram2 = new class {
                public $program_code = 'BBA';
                public $program_name = 'Bachelor of Business Administration';
            };
            $mockProgram3 = new class {
                public $program_code = 'DIP-IT';
                public $program_name = 'Diploma in Information Technology';
            };
            $mockStudent1 = new class($mockProgram1) {
                public function __construct(public $program) {}
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };
            $mockStudent2 = new class($mockProgram2) {
                public function __construct(public $program) {}
                public function fullName(): string { return 'Otieno Daniel'; }
                public $registration_number = 'STU/2024/045';
            };
            $mockStudent3 = new class($mockProgram3) {
                public function __construct(public $program) {}
                public function fullName(): string { return 'Achieng Faith'; }
                public $registration_number = 'STU/2024/102';
            };

            $payments = collect([
                (object) [
                    'id' => 1,
                    'payment_number' => 'PAY-2024-0001',
                    'student' => $mockStudent1,
                    'invoice' => new class { public $invoice_number = 'INV-2024-0001'; },
                    'payment_date' => now()->subDays(5),
                    'payment_method' => 'mpesa',
                    'amount' => 42500,
                    'payment_reference' => 'SFE123ABC456',
                ],
                (object) [
                    'id' => 2,
                    'payment_number' => 'PAY-2024-0002',
                    'student' => $mockStudent2,
                    'invoice' => new class { public $invoice_number = 'INV-2024-0002'; },
                    'payment_date' => now()->subDays(10),
                    'payment_method' => 'bank_transfer',
                    'amount' => 5000,
                    'payment_reference' => 'BT789XYZ012',
                ],
                (object) [
                    'id' => 3,
                    'payment_number' => 'PAY-2024-0003',
                    'student' => $mockStudent3,
                    'invoice' => new class { public $invoice_number = 'INV-2024-0003'; },
                    'payment_date' => now()->subDays(2),
                    'payment_method' => 'cash',
                    'amount' => 15000,
                    'payment_reference' => 'CASH-REF-001',
                ],
            ]);
        } else {
            $query = Payment::query()
                ->with(['invoice.student.program', 'student.program', 'studentAccount']);

            if ($programId > 0) {
                $query->where(function ($q) use ($programId) {
                    $q->whereHas('student', fn ($sq) => $sq->where('program_id', $programId))
                        ->orWhereHas('invoice.student', fn ($sq) => $sq->where('program_id', $programId));
                });
            }

            if ($search !== '') {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('payment_number', 'like', "%{$search}%")
                        ->orWhere('payment_reference', 'like', "%{$search}%")
                        ->orWhereHas('student', fn (Builder $sq) => $this->applyStudentSearchOnQuery($sq, $search))
                        ->orWhereHas('invoice.student', fn (Builder $sq) => $this->applyStudentSearchOnQuery($sq, $search));
                });
            }

            if ($semesterId > 0) {
                $query->whereHas('invoice', fn ($q) => $q->where('semester_id', $semesterId));
            }

            if ($academicYearId > 0) {
                $query->whereHas('invoice.semester', fn ($q) => $q->where('academic_year_id', $academicYearId));
            }

            $payments = $query->orderByDesc('payment_date')->paginate(20)->withQueryString();
        }

        $allSemesters = Schema::hasTable('semesters')
            ? \App\Models\Semester::query()->orderBy('semester_number')->get()
            : collect();
        $allAcademicYears = Schema::hasTable('academic_years')
            ? AcademicYear::query()->orderByDesc('year_label')->get()
            : collect();
        $allPrograms = Schema::hasTable('academic_programs')
            ? AcademicProgram::query()->where('status', 'active')->orderBy('program_name')->get(['id', 'program_code', 'program_name'])
            : collect();

        return $this->view($request, 'finance.student-finance.payments.index', $department, [
            'payments' => $payments,
            'search' => $search,
            'semesterId' => $semesterId,
            'academicYearId' => $academicYearId,
            'programId' => $programId,
            'allSemesters' => $allSemesters,
            'allAcademicYears' => $allAcademicYears,
            'allPrograms' => $allPrograms,
        ]);
    }

    public function paymentShow(Request $request, int $id, Department $department): View
    {
        if (! Schema::hasTable('payments') || Payment::count() === 0) {
            $mockStudent = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };

            $payment = (object) [
                'id' => $id,
                'payment_number' => 'PAY-2024-0001',
                'student' => $mockStudent,
                'invoice' => new class { public $invoice_number = 'INV-2024-0001'; },
                'payment_date' => now()->subDays(5),
                'payment_method' => 'mpesa',
                'amount' => 42500,
                'payment_reference' => 'SFE123ABC456',
                'transaction_channel_ref' => 'SFE123ABC456',
                'status' => 'SUCCESS',
                'is_reconciled' => true,
                'recordedBy' => new class { public function fullName(): string { return 'Finance Officer'; } },
                'receipt' => (object) [
                    'id' => 1,
                    'receipt_number' => 'RCP-2024-0001',
                    'amount' => 42500,
                    'payment_method' => 'mpesa',
                    'issued_at' => now()->subDays(5),
                ],
                'allocations' => collect([
                    (object) ['invoice_id' => 1, 'allocated_amount' => 42500, 'allocated_at' => now()->subDays(5)],
                ]),
            ];
        } else {
            $payment = Payment::with(['invoice.student', 'studentAccount', 'receipt', 'allocations'])->findOrFail($id);
        }

        return $this->view($request, 'finance.student-finance.payments.show', $department, [
            'payment' => $payment,
        ]);
    }

    public function receipts(Request $request, Department $department): View
    {
        $search = trim((string) $request->query('search', ''));
        $semesterId = (int) ($request->query('semester_id', 0) ?: 0);
        $academicYearId = (int) ($request->query('academic_year_id', 0) ?: 0);

        if (! Schema::hasTable('receipts') || Receipt::count() === 0) {
            $mockStudent1 = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };
            $mockStudent2 = new class {
                public function fullName(): string { return 'Otieno Daniel'; }
                public $registration_number = 'STU/2024/045';
            };
            $mockStudent3 = new class {
                public function fullName(): string { return 'Achieng Faith'; }
                public $registration_number = 'STU/2024/102';
            };

            $receipts = collect([
                (object) [
                    'id' => 1,
                    'receipt_number' => 'RCPT-2024-0001',
                    'student' => $mockStudent1,
                    'invoice' => new class { public $invoice_number = 'INV-2024-0001'; },
                    'amount' => 42500,
                    'payment_method' => 'mpesa',
                    'payment_reference' => 'SFE123ABC456',
                    'issued_at' => now()->subDays(5),
                ],
                (object) [
                    'id' => 2,
                    'receipt_number' => 'RCPT-2024-0002',
                    'student' => $mockStudent2,
                    'invoice' => new class { public $invoice_number = 'INV-2024-0002'; },
                    'amount' => 5000,
                    'payment_method' => 'bank_transfer',
                    'payment_reference' => 'BT789XYZ012',
                    'issued_at' => now()->subDays(10),
                ],
                (object) [
                    'id' => 3,
                    'receipt_number' => 'RCPT-2024-0003',
                    'student' => $mockStudent3,
                    'invoice' => new class { public $invoice_number = 'INV-2024-0003'; },
                    'amount' => 15000,
                    'payment_method' => 'cash',
                    'payment_reference' => 'CASH-REF-001',
                    'issued_at' => now()->subDays(2),
                ],
            ]);
        } else {
            $query = Receipt::query()
                ->with(['student', 'invoice']);

            if ($search !== '') {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('receipt_number', 'like', "%{$search}%")
                        ->orWhere('payment_reference', 'like', "%{$search}%")
                        ->orWhereHas('student', fn (Builder $sq) => $this->applyStudentSearchOnQuery($sq, $search));
                });
            }

            if ($semesterId > 0) {
                $query->whereHas('invoice', fn ($q) => $q->where('semester_id', $semesterId));
            }

            if ($academicYearId > 0) {
                $query->whereHas('invoice.semester', fn ($q) => $q->where('academic_year_id', $academicYearId));
            }

            $receipts = $query->orderByDesc('issued_at')->paginate(20)->withQueryString();
        }

        $allSemesters = Schema::hasTable('semesters')
            ? \App\Models\Semester::query()->orderBy('semester_number')->get()
            : collect();
        $allAcademicYears = Schema::hasTable('academic_years')
            ? AcademicYear::query()->orderByDesc('year_label')->get()
            : collect();

        return $this->view($request, 'finance.student-finance.receipts.index', $department, [
            'receipts' => $receipts,
            'search' => $search,
            'semesterId' => $semesterId,
            'academicYearId' => $academicYearId,
            'allSemesters' => $allSemesters,
            'allAcademicYears' => $allAcademicYears,
        ]);
    }

    public function adjustments(Request $request, Department $department): View
    {
        if (! Schema::hasTable('financial_adjustments') || FinancialAdjustment::count() === 0) {
            $mockStudent1 = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };
            $mockStudent2 = new class {
                public function fullName(): string { return 'Otieno Daniel'; }
                public $registration_number = 'STU/2024/045';
            };
            $mockStudent3 = new class {
                public function fullName(): string { return 'Achieng Faith'; }
                public $registration_number = 'STU/2024/102';
            };

            $adjustments = collect([
                (object) [
                    'id' => 1,
                    'student' => $mockStudent1,
                    'invoice' => new class { public $invoice_number = 'INV-2024-0001'; },
                    'adjustment_type' => 'scholarship',
                    'amount' => 20000,
                    'reason' => 'Merit-based scholarship for top academic performance',
                    'status' => 'approved',
                    'requestedBy' => new class { public function fullName(): string { return 'Admin User'; } },
                    'created_at' => now()->subDays(15),
                ],
                (object) [
                    'id' => 2,
                    'student' => $mockStudent2,
                    'invoice' => new class { public $invoice_number = 'INV-2024-0002'; },
                    'adjustment_type' => 'bursary',
                    'amount' => 15000,
                    'reason' => 'Financial hardship bursary from student welfare fund',
                    'status' => 'pending',
                    'requestedBy' => new class { public function fullName(): string { return 'Jane Doe'; } },
                    'created_at' => now()->subDays(3),
                ],
                (object) [
                    'id' => 3,
                    'student' => $mockStudent3,
                    'invoice' => new class { public $invoice_number = 'INV-2024-0003'; },
                    'adjustment_type' => 'waiver',
                    'amount' => 5000,
                    'reason' => 'Partial fee waiver for student council leadership',
                    'status' => 'approved',
                    'requestedBy' => new class { public function fullName(): string { return 'Admin User'; } },
                    'created_at' => now()->subDays(7),
                ],
            ]);
        } else {
            $adjustments = FinancialAdjustment::query()
                ->with(['student', 'invoice', 'requestedBy', 'approvedBy'])
                ->orderByDesc('created_at')
                ->paginate(20);
        }

        return $this->view($request, 'finance.student-finance.adjustments.index', $department, [
            'adjustments' => $adjustments,
        ]);
    }

    public function adjustmentCreate(Request $request, Department $department): View
    {
        return $this->view($request, 'finance.student-finance.adjustments.create', $department);
    }

    public function adjustmentStore(Request $request, Department $department)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'adjustment_type' => 'required|string|in:scholarship,bursary,waiver',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $student = \App\Models\Student::findOrFail($validated['student_id']);
        $requestedBy = $this->adjustmentStaffId($request);

        SafeWrite::transaction(function () use ($validated, $student, $requestedBy) {
            $account = StudentAccount::where('student_id', $student->id)->lockForUpdate()->first();

            $recent = FinancialAdjustment::query()
                ->where('student_id', $student->id)
                ->where('adjustment_type', $validated['adjustment_type'])
                ->where('amount', $validated['amount'])
                ->where('reason', $validated['reason'])
                ->where('requested_by', $requestedBy)
                ->where('created_at', '>=', now()->subSeconds(90))
                ->orderByDesc('id')
                ->first();

            if ($recent) {
                return $recent;
            }

            $adjustment = FinancialAdjustment::create([
                'student_account_id' => $account?->id,
                'student_id' => $student->id,
                'invoice_id' => $validated['invoice_id'] ?? null,
                'adjustment_type' => $validated['adjustment_type'],
                'reason' => $validated['reason'],
                'amount' => $validated['amount'],
                'status' => 'pending',
                'requested_by' => $requestedBy,
            ]);

            if ($account) {
                $this->financeService->recalculateAccount($account);
            }

            return $adjustment;
        });

        return redirect()->route('finance.student-finance.adjustments.index')->with('success', 'Adjustment request submitted successfully.');
    }

    public function installmentPlans(Request $request, Department $department): View
    {
        $mockStudent1 = new class {
            public function fullName(): string { return 'Wanjiku Mwangi'; }
            public $registration_number = 'STU/2024/001';
        };
        $mockStudent2 = new class {
            public function fullName(): string { return 'Otieno Daniel'; }
            public $registration_number = 'STU/2024/045';
        };

         $search = trim((string) $request->query('search', ''));
        $semesterId = (int) ($request->query('semester_id', 0) ?: 0);
        $academicYearId = (int) ($request->query('academic_year_id', 0) ?: 0);

        if (! Schema::hasTable('installment_plans') || InstallmentPlan::count() === 0) {
            $plans = collect([
                (object) [
                    'id' => 1,
                    'plan_number' => 'INST-STU001-001',
                    'student' => $mockStudent1,
                    'invoice' => new class { public $invoice_number = 'INV-STU001-001'; },
                    'total_amount' => 61000,
                    'paid_amount' => 20333,
                    'remaining_amount' => 40667,
                    'status' => 'active',
                    'milestones' => collect([
                        (object) ['milestone_type' => 'registration', 'percentage' => 50, 'milestone_amount' => 30500, 'paid_amount' => 30500, 'status' => 'paid', 'due_date' => now()->subDays(20)],
                        (object) ['milestone_type' => 'mid_semester', 'percentage' => 75, 'milestone_amount' => 45750, 'paid_amount' => 22875, 'status' => 'partial', 'due_date' => now()->addDays(15)],
                        (object) ['milestone_type' => 'final', 'percentage' => 100, 'milestone_amount' => 61000, 'paid_amount' => 0, 'status' => 'pending', 'due_date' => now()->addDays(60)],
                    ]),
                ],
                (object) [
                    'id' => 2,
                    'plan_number' => 'INST-STU002-001',
                    'student' => $mockStudent2,
                    'invoice' => new class { public $invoice_number = 'INV-STU002-001'; },
                    'total_amount' => 85000,
                    'paid_amount' => 42500,
                    'remaining_amount' => 42500,
                    'status' => 'active',
                    'milestones' => collect([
                        (object) ['milestone_type' => 'registration', 'percentage' => 50, 'milestone_amount' => 42500, 'paid_amount' => 0, 'status' => 'overdue', 'due_date' => now()->subDays(10)],
                    ]),
                ],
                (object) [
                    'id' => 3,
                    'plan_number' => 'INST-STU003-001',
                    'student' => new class {
                        public function fullName(): string { return 'Achieng Faith'; }
                        public $registration_number = 'STU/2024/102';
                    },
                    'invoice' => new class { public $invoice_number = 'INV-STU003-001'; },
                    'total_amount' => 72000,
                    'paid_amount' => 72000,
                    'remaining_amount' => 0,
                    'status' => 'completed',
                    'milestones' => collect([]),
                ],
            ]);
         } else {
            $query = InstallmentPlan::query()
                ->with(['student', 'invoice.semester.academicYear', 'items', 'semester', 'academicYear']);

            if ($search !== '') {
                $query->whereHas('student', fn (Builder $sq) => $this->applyStudentSearchOnQuery($sq, $search));
            }

            if ($semesterId > 0) {
                $query->where(function ($q) use ($semesterId) {
                    $q->where('semester_id', $semesterId)
                      ->orWhereHas('invoice', fn ($iq) => $iq->where('semester_id', $semesterId));
                });
            }

            if ($academicYearId > 0) {
                $query->where(function ($q) use ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId)
                      ->orWhereHas('invoice.semester', fn ($iq) => $iq->where('academic_year_id', $academicYearId));
                });
            }

            $plans = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        }

        $allSemesters = Schema::hasTable('semesters')
            ? \App\Models\Semester::query()->orderBy('semester_number')->get()
            : collect();
        $allAcademicYears = Schema::hasTable('academic_years')
            ? AcademicYear::query()->orderByDesc('year_label')->get()
            : collect();

        return $this->view($request, 'finance.student-finance.installment-plans.index', $department, [
            'plans' => $plans,
            'search' => $search,
            'semesterId' => $semesterId,
            'academicYearId' => $academicYearId,
            'allSemesters' => $allSemesters,
            'allAcademicYears' => $allAcademicYears,
        ]);
    }

    public function installmentPlanCreate(Request $request, Department $department): View
    {
        return $this->view($request, 'finance.student-finance.installment-plans.create', $department);
    }

    public function installmentPlanStore(Request $request, Department $department)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'required|exists:invoices,id',
            'total_amount' => 'required|numeric|min:0',
            'installment_count' => 'required|integer|min:2|max:12',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $invoice = Invoice::findOrFail($validated['invoice_id']);

        SafeWrite::transaction(function () use ($validated, $student, $invoice) {
            $academicYearId = AcademicYear::query()->orderByDesc('start_date')->value('id');
            $account = StudentAccount::firstOrCreate(
                ['student_id' => $student->id],
                ['academic_year_id' => $academicYearId]
            );

            $existing = InstallmentPlan::query()
                ->where('student_id', $student->id)
                ->where('invoice_id', $invoice->id)
                ->whereIn('status', ['active', 'pending'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $invoice->loadMissing('semester');
            $semester = $invoice->semester;
            $semesterId = $invoice->semester_id ?? $semester?->id;
            $academicYearId = $semester?->academic_year_id ?? $account->academic_year_id;

            $plan = InstallmentPlan::create([
                'student_account_id' => $account->id,
                'student_id' => $student->id,
                'invoice_id' => $invoice->id,
                'semester_id' => $semesterId,
                'academic_year_id' => $academicYearId,
                'plan_number' => 'INST-'.now()->format('Ym').'-'.str_pad((string) $account->id, 4, '0', STR_PAD_LEFT),
                'total_amount' => $validated['total_amount'],
                'paid_amount' => 0,
                'remaining_amount' => $validated['total_amount'],
                'status' => 'active',
            ]);

            if ($plan->semester_id && $plan->academic_year_id) {
                $this->financeService->updatePlanProgress($plan, $account);
            }

            $perInstallment = $validated['total_amount'] / $validated['installment_count'];
            $startDate = now()->addDays(30);

            for ($i = 1; $i <= $validated['installment_count']; $i++) {
                InstallmentPlanItem::create([
                    'installment_plan_id' => $plan->id,
                    'installment_number' => $i,
                    'amount' => round($perInstallment, 2),
                    'due_date' => $startDate->copy()->addDays(($i - 1) * 30),
                    'status' => 'pending',
                ]);
            }

            return $plan;
        });

        return redirect()->route('finance.student-finance.installment-plans.index')->with('success', 'Installment plan created successfully.');
    }

    public function milestoneShow(Request $request, int $id, Department $department): View
    {
        if (! Schema::hasTable('payment_milestones') || PaymentMilestone::count() === 0) {
            $student = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };

            $milestone = (object) [
                'id' => $id,
                'student' => $student,
                'invoice' => new class { public $invoice_number = 'INV-2024-0001'; },
                'milestone_type' => 'registration',
                'percentage' => 50,
                'milestone_amount' => 30500,
                'paid_amount' => 30500,
                'status' => 'paid',
                'due_date' => now()->subDays(20),
            ];
        } else {
            $milestone = PaymentMilestone::with(['student', 'invoice', 'studentAccount'])->findOrFail($id);
        }

        return $this->view($request, 'finance.student-finance.milestones.show', $department, [
            'milestone' => $milestone,
        ]);
    }

    public function installmentPlanShow(Request $request, int $id, Department $department): View
    {
        if (! Schema::hasTable('installment_plans') || InstallmentPlan::count() === 0) {
            $mockStudent = new class {
                public $id = 1;
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };

            $plan = (object) [
                'id' => $id,
                'plan_number' => 'INST-STU001-001',
                'student' => $mockStudent,
                'invoice' => new class { public $invoice_number = 'INV-2024-0001'; },
                'total_amount' => 61000,
                'paid_amount' => 20333,
                'remaining_amount' => 40667,
                'status' => 'active',
                'items' => collect([
                    (object) ['installment_number' => 1, 'amount' => 20333, 'due_date' => now()->subDays(20), 'status' => 'paid'],
                    (object) ['installment_number' => 2, 'amount' => 20334, 'due_date' => now()->addDays(10), 'status' => 'pending'],
                    (object) ['installment_number' => 3, 'amount' => 20333, 'due_date' => now()->addDays(40), 'status' => 'pending'],
                ]),
                'milestones' => collect([
                    (object) ['milestone_type' => 'registration', 'percentage' => 50, 'milestone_amount' => 30500, 'paid_amount' => 30500, 'status' => 'paid', 'due_date' => now()->subDays(20)],
                    (object) ['milestone_type' => 'mid_semester', 'percentage' => 75, 'milestone_amount' => 45750, 'paid_amount' => 22875, 'status' => 'partial', 'due_date' => now()->addDays(15)],
                    (object) ['milestone_type' => 'final', 'percentage' => 100, 'milestone_amount' => 61000, 'paid_amount' => 0, 'status' => 'pending', 'due_date' => now()->addDays(60)],
                ]),
            ];
         } else {
            $plan = InstallmentPlan::query()
                ->with(['student', 'invoice.semester.academicYear', 'items', 'milestones', 'semester', 'academicYear'])
                ->findOrFail($id);
        }

        $paymentHistory = collect();
        if (Schema::hasTable('payments') && $plan->student) {
            $payments = Payment::query()
                ->with(['invoice.semester', 'receipt'])
                ->where('student_id', $plan->student->id)
                ->orderByDesc('payment_date')
                ->get();

            $paymentHistory = $payments->groupBy(fn ($p) => $p->invoice->semester?->semester_name ?? ($p->payment_date?->format('Y-m') ?? 'Unknown'));
        }

        if ($plan->milestones) {
            $plan->setRelation('milestones', $plan->milestones->where('invoice_id', $plan->invoice_id)->values());
        }

        return $this->view($request, 'finance.student-finance.installment-plans.show', $department, [
            'plan' => $plan,
            'paymentHistory' => $paymentHistory,
        ]);
    }

    public function clearance(Request $request, Department $department): View
    {
        $mockStudent1 = new class {
            public function fullName(): string { return 'Wanjiku Mwangi'; }
            public $registration_number = 'STU/2024/001';
        };
        $mockStudent2 = new class {
            public function fullName(): string { return 'Otieno Daniel'; }
            public $registration_number = 'STU/2024/045';
        };
        $mockStudent3 = new class {
            public function fullName(): string { return 'Achieng Faith'; }
            public $registration_number = 'STU/2024/102';
        };
        $mockYear = new class { public $year_label = '2024/2025'; };

        if (! Schema::hasTable('student_accounts') || StudentAccount::count() === 0) {
            $accounts = collect([
                (object) [
                    'id' => 1,
                    'student' => $mockStudent1,
                    'academicYear' => $mockYear,
                    'total_chargeable' => 61000,
                    'total_paid' => 30500,
                    'outstanding_balance' => 30500,
                    'credit_balance' => 0,
                    'is_cleared' => false,
                    'last_payment_date' => now()->subDays(5),
                ],
                (object) [
                    'id' => 2,
                    'student' => $mockStudent2,
                    'academicYear' => $mockYear,
                    'total_chargeable' => 85000,
                    'total_paid' => 85000,
                    'outstanding_balance' => 0,
                    'credit_balance' => 5000,
                    'is_cleared' => true,
                    'last_payment_date' => now()->subDays(10),
                ],
                (object) [
                    'id' => 3,
                    'student' => $mockStudent3,
                    'academicYear' => $mockYear,
                    'total_chargeable' => 72000,
                    'total_paid' => 36000,
                    'outstanding_balance' => 36000,
                    'credit_balance' => 0,
                    'is_cleared' => false,
                    'last_payment_date' => now()->subDays(2),
                ],
            ]);
            $clearedCount = 1;
            $notClearedCount = 2;
            $creditCount = 1;
        } else {
            $accounts = StudentAccount::query()
                ->with(['student'])
                ->where('is_cleared', 1)
                ->orWhere('outstanding_balance', '>', 0)
                ->orderByDesc('updated_at')
                ->paginate(20);

            $clearedCount = StudentAccount::where('is_cleared', 1)->count();
            $notClearedCount = StudentAccount::where('is_cleared', 0)->count();
            $creditCount = Schema::hasColumn('student_accounts', 'credit_balance')
                ? StudentAccount::where('credit_balance', '>', 0)->count()
                : 0;
        }

        return $this->view($request, 'finance.student-finance.clearance.index', $department, [
            'accounts' => $accounts,
            'clearedCount' => $clearedCount,
            'notClearedCount' => $notClearedCount,
            'creditCount' => $creditCount,
        ]);
    }

    public function clearanceApprove(Request $request, int $id, Department $department): \Illuminate\Http\RedirectResponse
    {
        $account = Schema::hasTable('student_accounts') && StudentAccount::count() > 0
            ? StudentAccount::findOrFail($id)
            : null;

        if ($account) {
            $account->update([
                'is_cleared' => true,
                'cleared_at' => now(),
            ]);
        }

        return back()->with('success', 'Student cleared successfully.');
    }

    public function clearanceReject(Request $request, int $id, Department $department): \Illuminate\Http\RedirectResponse
    {
        $account = Schema::hasTable('student_accounts') && StudentAccount::count() > 0
            ? StudentAccount::findOrFail($id)
            : null;

        if ($account) {
            $account->update([
                'is_cleared' => false,
                'cleared_at' => null,
            ]);
        }

        return back()->with('success', 'Student clearance revoked.');
    }

    public function apiPrograms(): \Illuminate\Http\JsonResponse
    {
        $programs = AcademicProgram::query()
            ->select('id', 'program_name', 'program_code')
            ->orderBy('program_name')
            ->get();

        return response()->json($programs);
    }

    public function apiAcademicYears(): \Illuminate\Http\JsonResponse
    {
        $years = AcademicYear::query()
            ->select('id', 'year_label')
            ->orderByDesc('start_date')
            ->get();

        return response()->json($years);
    }

    public function apiStudents(): \Illuminate\Http\JsonResponse
    {
        if (! Schema::hasTable('students')) {
            return response()->json([]);
        }

        // Searchable by name: the name columns have to be selected, and the account
        // carries no name column, so it is only asked for by e-mail.
        $students = Student::query()
            ->select('id', 'first_name', 'middle_name', 'surname', 'registration_number', 'application_id')
            ->with(['applicant:id,first_name,middle_name,surname', 'user:id,email'])
            ->orderBy('registration_number')
            ->limit(500)
            ->get()
            ->map(function ($student) {
                $name = $student->displayName();

                return [
                    'id' => $student->id,
                    'text' => $name === trim((string) $student->registration_number)
                        ? $name
                        : $name.' ('.$student->registration_number.')',
                ];
            });

        return response()->json($students);
    }

    public function apiInvoices(Request $request): \Illuminate\Http\JsonResponse
    {
        if (! Schema::hasTable('invoices')) {
            return response()->json([]);
        }

        $invoices = Invoice::query()
            ->select('id', 'invoice_number', 'student_id', 'balance')
            ->with(['student:id,registration_number'])
            ->when($request->query('student_id'), function ($q, $studentId) {
                $q->where('student_id', $studentId);
            })
            ->orderByDesc('issue_date')
            ->limit(100)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'text' => $invoice->invoice_number . ' - ' . ($invoice->student->registration_number ?? 'N/A') . ' (KES ' . number_format($invoice->balance, 2) . ')',
                ];
            });

        return response()->json($invoices);
    }

    public function milestones(Request $request, Department $department): View
    {
        $search = trim((string) $request->query('search', ''));
        $semesterId = (int) ($request->query('semester_id', 0) ?: 0);
        $academicYearId = (int) ($request->query('academic_year_id', 0) ?: 0);

        if (! Schema::hasTable('payment_milestones') || \App\Models\Finance\PaymentMilestone::count() === 0) {
            $student1 = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };
            $student2 = new class {
                public function fullName(): string { return 'Otieno Daniel'; }
                public $registration_number = 'STU/2024/045';
            };
            $milestones = collect([
                (object) [
                    'milestone_type' => 'registration',
                    'percentage' => 50,
                    'milestone_amount' => 30500,
                    'paid_amount' => 30500,
                    'status' => 'paid',
                    'due_date' => now()->subDays(20),
                    'student' => $student1,
                ],
                (object) [
                    'milestone_type' => 'mid_semester',
                    'percentage' => 75,
                    'milestone_amount' => 45750,
                    'paid_amount' => 22875,
                    'status' => 'partial',
                    'due_date' => now()->addDays(15),
                    'student' => $student1,
                ],
                (object) [
                    'milestone_type' => 'final',
                    'percentage' => 100,
                    'milestone_amount' => 61000,
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'due_date' => now()->addDays(60),
                    'student' => $student1,
                ],
                (object) [
                    'milestone_type' => 'registration',
                    'percentage' => 50,
                    'milestone_amount' => 42500,
                    'paid_amount' => 0,
                    'status' => 'overdue',
                    'due_date' => now()->subDays(10),
                    'student' => $student2,
                ],
            ]);
        } else {
            $query = \App\Models\Finance\PaymentMilestone::query()
                ->with(['student', 'invoice', 'invoice.semester.academicYear']);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('milestone_type', 'like', "%{$search}%")
                      ->orWhereHas('student', function ($sq) use ($search) {
                          $sq->where('registration_number', 'like', "%{$search}%")
                             ->orWhereHas('applicant', function ($sq2) use ($search) {
                                 $sq2->where('first_name', 'like', "%{$search}%")
                                     ->orWhere('surname', 'like', "%{$search}%");
                             });
                      });
                });
            }

            if ($semesterId > 0) {
                $query->whereHas('invoice', fn ($q) => $q->where('semester_id', $semesterId));
            }

            if ($academicYearId > 0) {
                $query->whereHas('invoice.semester', fn ($q) => $q->where('academic_year_id', $academicYearId));
            }

            $milestones = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        }

        $allSemesters = Schema::hasTable('semesters')
            ? \App\Models\Semester::query()->orderBy('semester_number')->get()
            : collect();
        $allAcademicYears = Schema::hasTable('academic_years')
            ? AcademicYear::query()->orderByDesc('year_label')->get()
            : collect();

        return $this->view($request, 'finance.student-finance.milestones.index', $department, [
            'milestones' => $milestones,
            'search' => $search,
            'semesterId' => $semesterId,
            'academicYearId' => $academicYearId,
            'allSemesters' => $allSemesters,
            'allAcademicYears' => $allAcademicYears,
        ]);
    }

    public function adjustmentApprove(Request $request, int $id, Department $department)
    {
        if (! Schema::hasTable('financial_adjustments')) {
            abort(404);
        }

        $adjustment = FinancialAdjustment::query()->findOrFail($id);

        if ($refusal = $this->adjustmentDecisionRefusal($request, $adjustment)) {
            return back()->with('error', $refusal);
        }

        $this->financeService->approveAdjustment($adjustment, $this->adjustmentStaffId($request));

        return back()->with('success', 'Adjustment approved successfully.');
    }

    public function adjustmentReject(Request $request, int $id, Department $department)
    {
        if (! Schema::hasTable('financial_adjustments')) {
            abort(404);
        }

        $adjustment = FinancialAdjustment::query()->findOrFail($id);

        if ($refusal = $this->adjustmentDecisionRefusal($request, $adjustment)) {
            return back()->with('error', $refusal);
        }

        $this->financeService->rejectAdjustment($adjustment, $this->adjustmentStaffId($request));

        return back()->with('success', 'Adjustment rejected.');
    }

    /**
     * A waiver, scholarship or bursary is decided by a Finance officer other than the
     * one who requested it, so the requester's own request never closes itself.
     */
    private function adjustmentDecisionRefusal(Request $request, FinancialAdjustment $adjustment): ?string
    {
        if ($adjustment->status !== 'pending') {
            return 'This adjustment was already '.ucfirst($adjustment->status).', so there is nothing left to decide.';
        }

        $deciders = [$request->user()->id, $request->user()->staff_id];
        if (in_array((int) $adjustment->requested_by, array_filter(array_map('intval', $deciders)), true)
            && ! app(\App\Services\RBACService::class)->isPlatformAdministrator($request->user())) {
            return 'You requested this adjustment, so another Finance officer has to decide it.';
        }

        return null;
    }

    private function adjustmentStaffId(Request $request): int
    {
        return (int) ($request->user()->staff_id ?? $request->user()->id);
    }

    public function feeStructureShow(Request $request, int $id, Department $department): View
    {
        if (! Schema::hasTable('fee_structures') || FeeStructure::count() === 0) {
            $feeStructure = (object) [
                'id' => $id,
                'program' => (object) ['program_name' => 'Bachelor of Science in Computer Science'],
                'academicYear' => (object) ['year_label' => '2024/2025'],
                'semester_number' => 1,
                'tuition_fee' => 45000,
                'application_fee' => 1000,
                'accommodation_fee' => 15000,
                'transport_fee' => 3000,
                'library_fee' => 2000,
                'other_fees' => 10000,
                'total_semester_fee' => 81000,
                'is_active' => true,
                'effective_from' => now()->subMonths(2),
                'is_approved' => true,
                'approved_at' => now()->subMonths(2),
                'approvedBy' => new class { public function fullName(): string { return 'Admin User'; } },
                'notes' => 'Approved for 2024/2025 academic year.',
            ];
        } else {
            $feeStructure = FeeStructure::with(['program', 'academicYear'])->findOrFail($id);
        }

        return $this->view($request, 'finance.student-finance.fee-structures.show', $department, [
            'feeStructure' => $feeStructure,
        ]);
    }

    public function receiptShow(Request $request, int $id, Department $department): View
    {
        if (! Schema::hasTable('receipts') || Receipt::count() === 0) {
            $mockStudent = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };

            $receipt = (object) [
                'id' => $id,
                'receipt_number' => 'RCP-2024-0001',
                'student' => $mockStudent,
                'invoice' => new class { public $invoice_number = 'INV-2024-0001'; },
                'amount' => 42500,
                'payment_method' => 'mpesa',
                'payment_reference' => 'SFE123ABC456',
                'issued_at' => now()->subDays(5),
            ];
        } else {
            $receipt = Receipt::with(['student', 'invoice'])->findOrFail($id);
        }

        return $this->view($request, 'finance.student-finance.receipts.show', $department, [
            'receipt' => $receipt,
        ]);
    }

    public function receiptDownload(Request $request, int $id, Department $department): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $receipt = Schema::hasTable('receipts') && Receipt::count() > 0
            ? Receipt::with(['student', 'invoice', 'payment'])->findOrFail($id)
            : (object) [
                'id' => $id,
                'receipt_number' => 'RCP-2024-0001',
                'student' => new class { public function fullName(): string { return 'Wanjiku Mwangi'; } public $registration_number = 'STU/2024/001'; },
                'invoice' => new class { public $invoice_number = 'INV-2024-0001'; },
                'amount' => 42500,
                'payment_method' => 'mpesa',
                'payment_reference' => 'SFE123ABC456',
                'issued_at' => now()->subDays(5),
            ];

        return app(\App\Services\PrintDocumentService::class)->downloadPdf(
            'finance.student-finance.receipts.pdf',
            [
                'department' => $department,
                'receipt' => $receipt,
            ],
            'receipt-'.$receipt->receipt_number.'.pdf'
        );
    }

    public function adjustmentShow(Request $request, int $id, Department $department): View
    {
        if (! Schema::hasTable('financial_adjustments') || FinancialAdjustment::count() === 0) {
            $mockStudent = new class {
                public function fullName(): string { return 'Wanjiku Mwangi'; }
                public $registration_number = 'STU/2024/001';
            };

            $adjustment = (object) [
                'id' => $id,
                'student' => $mockStudent,
                'invoice' => new class { public $invoice_number = 'INV-2024-0001'; },
                'adjustment_type' => 'scholarship',
                'amount' => 5000,
                'reason' => 'Merit-based scholarship for top academic performance',
                'status' => 'approved',
                'requestedBy' => new class { public function fullName(): string { return 'Admin User'; } },
                'approvedBy' => new class { public function fullName(): string { return 'Finance Director'; } },
                'approved_by' => 3,
                'approved_at' => now()->subDays(10),
                'created_at' => now()->subDays(15),
            ];
        } else {
            $adjustment = FinancialAdjustment::with(['student', 'invoice', 'requestedBy', 'approvedBy'])->findOrFail($id);
        }

        return $this->view($request, 'finance.student-finance.adjustments.show', $department, [
            'adjustment' => $adjustment,
            'canDecide' => $adjustment instanceof FinancialAdjustment
                && 'pending' === $adjustment->status
                && $request->user()->can('finance.adjustments.manage'),
        ]);
    }

    /**
     * @return list<string>
     */
    private function studentAccountRelations(): array
    {
        $relations = [
            'student',
            'academicYear',
            'studentInvoices.semester',
            'studentPayments',
        ];

        if (Schema::hasTable('academic_programs')) {
            $relations[] = 'student.program';
        }

        if (Schema::hasTable('invoice_items')) {
            $relations[] = 'studentInvoices.items';
        }

        if (Schema::hasTable('receipts')) {
            $relations[] = 'studentPayments.receipt';
            $relations[] = 'receipts';
        }

        if (Schema::hasTable('financial_adjustments')) {
            $relations[] = 'adjustments.requestedBy';
        }

        if (Schema::hasTable('installment_plans')) {
            $relations[] = 'installmentPlans';
            if (Schema::hasTable('installment_plan_items')) {
                $relations[] = 'installmentPlans.items';
            }
        }

        if (Schema::hasTable('payment_milestones')) {
            $relations[] = 'milestones';
        }

        return $relations;
    }

    protected function activeProgramsForFilter(): Collection
    {
        if (! Schema::hasTable('academic_programs')) {
            return collect();
        }

        return AcademicProgram::query()
            ->where('status', 'active')
            ->orderBy('program_name')
            ->get(['id', 'program_code', 'program_name']);
    }

    protected function applyStudentSearchOnQuery(Builder $query, string $search): void
    {
        $query->where(function (Builder $q) use ($search) {
            $q->where('registration_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('surname', 'like', "%{$search}%")
                ->orWhereHas('program', function (Builder $pq) use ($search) {
                    $pq->where('program_name', 'like', "%{$search}%")
                        ->orWhere('program_code', 'like', "%{$search}%");
                })
                ->orWhereHas('applicant', function (Builder $aq) use ($search) {
                    $aq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('application_number', 'like', "%{$search}%");
                });
        });
    }
}
