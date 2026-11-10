<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Services\Finance\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /** Students billed per click; the screen keeps a cursor for the rest. */
    private const BULK_INVOICE_BATCH = 50;

    public function __construct(
        protected InvoiceService $invoices,
    ) {}

    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->with(['student.applicant', 'student.program'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('registration_number', 'like', "%{$search}%"));
            })
            ->orderByDesc('issue_date')
            ->paginate(25)
            ->withQueryString();

        return view('finance.invoices.index', [
            'invoices' => $invoices,
            'invoiceTypes' => config('finance.invoice_types'),
        ]);
    }

    public function create(): View
    {
        // Every student is searchable by name, so the list is not capped.
        $students = Student::query()->with(['applicant', 'program'])->orderBy('registration_number')->get();

        return view('finance.invoices.create', [
            'students' => $students,
            'activeStudents' => $this->invoices->activeStudentCount(),
            // Money the student already paid in, which the invoice spends before asking
            // for cash, so Finance sees the deduction coming.
            'studentCredits' => StudentAccount::query()
                ->whereIn('student_id', $students->pluck('id'))
                ->where('credit_balance', '>', 0)
                ->selectRaw('student_id, SUM(credit_balance) as credit_balance')
                ->groupBy('student_id')
                ->pluck('credit_balance', 'student_id'),
            'feeStructures' => FeeStructure::query()->with(['program', 'academicYear'])->where('is_active', 1)->where('is_approved', 1)->get(),
            'invoiceTypes' => config('finance.invoice_types'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['student_id' => 'required|exists:students,id']);
        $validated = $this->billData($request);

        $student = Student::query()->findOrFail($validated['student_id']);
        $invoice = $this->issueBill($student, $validated, $this->recordingStaffId($request));

        return redirect()->route('finance.invoices.show', $invoice)->with('success', $this->successMessage($invoice));
    }

    /**
     * Raise the same bill for every active student, one batch per click so the host's
     * PHP worker is never held for the whole cohort.
     */
    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $this->billData($request);

        // Fail before the first invoice rather than half-way through the school when
        // the chosen charge does not exist for that programme.
        if (($validated['fee_structure_charge'] ?? 'semester') === 'indexing_nck') {
            $structure = FeeStructure::query()->findOrFail($validated['fee_structure_id']);
            abort_unless(
                $structure->requires_indexing_nck && $structure->indexing_nck_fee,
                422,
                'Indexing (NCK) is not configured for this programme, so it cannot be billed to every student.'
            );
        }

        // A batch is deliberately long work; the default request limit would kill it
        // part-way through and leave the officer without the cursor to carry on.
        set_time_limit(300);

        $staffId = $this->recordingStaffId($request);
        $result = $this->invoices->billActiveStudents(
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
                ->route('finance.invoices.create', [
                    'bill_all' => 1,
                    'bulk_after_id' => $result['after_id'],
                ])
                ->withInput()
                ->with('warning', $raised.' '.$result['remaining'].' students are still to be billed — press "Bill all students" again to carry on.');
        }

        return redirect()
            ->route('finance.invoices.index')
            ->with('success', $raised.' Every student has their own invoice.');
    }

    /** @return array<string, string> */
    private function billRules(): array
    {
        return [
            'invoice_type' => 'nullable|string|in:'.implode(',', array_keys(config('finance.invoice_types'))),
            'description' => 'nullable|string|max:500',
            'amount' => 'nullable|numeric|min:0.01',
            'fee_structure_id' => 'nullable|exists:fee_structures,id',
            'fee_structure_charge' => 'nullable|string|in:semester,application,qa_annual,indexing_nck,graduation',
            'include_optional_charges' => 'nullable|boolean',
        ];
    }

    /** @return array<string, mixed> */
    private function billData(Request $request): array
    {
        $validated = $request->validate($this->billRules());

        if (empty($validated['fee_structure_id'])) {
            $validated += $request->validate([
                'invoice_type' => 'required|string|in:'.implode(',', array_keys(config('finance.invoice_types'))),
                'description' => 'required|string|max:500',
                'amount' => 'required|numeric|min:0.01',
            ]);
        }

        return $validated;
    }

    /** @param  array<string, mixed>  $validated */
    private function issueBill(Student $student, array $validated, int $staffId)
    {
        if (! empty($validated['fee_structure_id'])) {
            $feeStructure = FeeStructure::query()->findOrFail($validated['fee_structure_id']);
            $charge = $validated['fee_structure_charge'] ?? 'semester';
            $includeOptional = (bool) ($validated['include_optional_charges'] ?? false);

            return match ($charge) {
                'application' => $this->invoices->generateApplicationInvoice($student, $feeStructure, $staffId),
                'qa_annual' => $this->invoices->generateQaAnnualInvoice($student, $feeStructure, $staffId),
                'indexing_nck' => $this->invoices->generateIndexingInvoice($student, $feeStructure, $staffId),
                'graduation' => $this->invoices->generateGraduationInvoice($student, $feeStructure, $staffId),
                default => $this->invoices->generateSemesterInvoice($student, $feeStructure, $staffId, $includeOptional),
            };
        }

        return $this->invoices->generateForStudent($student, [
            'invoice_type' => $validated['invoice_type'],
            'description' => $validated['description'],
            'amount' => (float) $validated['amount'],
        ], $staffId);
    }

    private function recordingStaffId(Request $request): int
    {
        return (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id'));
    }

    private function successMessage($invoice): string
    {
        if ((float) $invoice->amount_paid > 0) {
            return 'Invoice generated. KES '.number_format((float) $invoice->amount_paid, 2)
                .' of the student\'s excess payment was deducted, leaving KES '
                .number_format((float) $invoice->balance, 2).' to pay.';
        }

        return 'Invoice generated and dispatched to the student portal.';
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['student.applicant', 'student.program', 'studentAccount.academicYear', 'payments']);

        return view('finance.invoices.show', compact('invoice'));
    }

    public function resend(Invoice $invoice): RedirectResponse
    {
        $this->invoices->dispatchToChannels($invoice);

        return back()->with('success', 'Invoice resent to student portal and email.');
    }
}
