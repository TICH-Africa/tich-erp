<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CreditMemo;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\Finance\AccountsReceivableService;
use App\Services\Finance\CreditMemoService;
use App\Services\Finance\FinanceNavigationService;
use App\Services\Finance\StudentStatementService;
use App\Services\PrintDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AccountsReceivableController extends Controller
{
    public function __construct(
        protected AccountsReceivableService $ar,
        protected CreditMemoService $creditMemos,
        protected StudentStatementService $statements,
        protected PrintDocumentService $printDocuments,
        protected FinanceNavigationService $navigation,
    ) {}

    public function index(Request $request, Department $department): View
    {
        $aging = $this->ar->agingReport();

        $invoices = $this->ar->openInvoicesQuery()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($student) => $student->where('registration_number', 'like', "%{$search}%"));
                });
            })
            ->orderBy('due_date')
            ->paginate(25)
            ->withQueryString();

        $rows = collect($this->ar->invoiceRows($invoices->getCollection()));

        if ($request->filled('bucket')) {
            $bucket = $request->string('bucket')->toString();
            $rows = $rows->filter(fn (array $row) => $row['bucket'] === $bucket)->values();
        }

        return view('finance.ar.index', [
            'department' => $department,
            'aging' => $aging,
            'invoices' => $invoices,
            'rows' => $rows,
            'departmentParams' => ['department' => $department->id],
        ]);
    }

    public function indexGlobal(Request $request): RedirectResponse
    {
        $dept = $this->navigation->financeDepartment();
        abort_if(! $dept, 404, 'Finance department is not configured.');

        return redirect()->route('finance.ar.index', ['department' => $dept->id]);
    }

    public function sendReminder(Request $request, Department $department, Invoice $invoice): RedirectResponse
    {
        abort_unless((float) $invoice->balance > 0, 422, 'Invoice has no balance due.');

        $sent = $this->ar->sendReminder($invoice);

        return back()->with($sent ? 'success' : 'error', $sent
            ? 'Payment reminder sent.'
            : 'Could not send reminder - no email or phone on file.');
    }

    public function sendBulkReminders(Request $request, Department $department): RedirectResponse
    {
        $result = $this->ar->sendDueReminders();

        return back()->with('success', sprintf(
            'Sent %d reminder(s). %d failed.',
            $result['sent'],
            $result['failed'],
        ));
    }

    public function exportAgingPdf(Request $request, Department $department): Response
    {
        $aging = $this->ar->agingReport();

        return $this->printDocuments->inlinePdf(
            'finance.ar.print-aging',
            [
                'aging' => $aging,
                'documentTitle' => 'Accounts Receivable Ageing',
                'documentSubtitle' => 'As at '.now()->format('d F Y'),
                'documentRef' => $this->printDocuments->documentRef('AR', 'AGEING'),
            ],
            'ar-ageing-'.now()->format('Ymd').'.pdf',
        );
    }

    public function creditMemosIndex(Request $request, Department $department): View
    {
        $memos = CreditMemo::query()
            ->with(['student.applicant', 'invoice', 'issuer'])
            ->orderByDesc('issued_at')
            ->paginate(25);

        return view('finance.ar.credit-memos.index', [
            'department' => $department,
            'memos' => $memos,
            'departmentParams' => ['department' => $department->id],
        ]);
    }

    public function creditMemoCreate(Request $request, Department $department): View
    {
        $invoices = Invoice::query()
            ->with('student.applicant')
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->where('balance', '>', 0)
            ->orderByDesc('issue_date')
            ->limit(200)
            ->get();

        return view('finance.ar.credit-memos.create', [
            'department' => $department,
            'invoices' => $invoices,
            'departmentParams' => ['department' => $department->id],
        ]);
    }

    public function creditMemoStore(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        $invoice = Invoice::query()->findOrFail($validated['invoice_id']);
        $staffId = (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id') ?? 1);

        $memo = $this->creditMemos->issue(
            $invoice,
            (float) $validated['amount'],
            $validated['reason'],
            $staffId,
        );

        return redirect()
            ->route('finance.ar.credit-memos.index', ['department' => $department->id])
            ->with('success', 'Credit memo '.$memo->credit_memo_number.' issued.');
    }

    public function creditMemoShow(Request $request, Department $department, CreditMemo $creditMemo): View
    {
        $creditMemo->load(['student.applicant', 'student.program', 'invoice', 'issuer']);

        return view('finance.ar.credit-memos.show', [
            'department' => $department,
            'memo' => $creditMemo,
            'departmentParams' => ['department' => $department->id],
        ]);
    }

    public function exportStatementsPdf(Request $request, Department $department): Response
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
        ]);

        $students = Student::query()
            ->with(['applicant', 'program'])
            ->whereIn('id', $validated['student_ids'])
            ->orderBy('registration_number')
            ->get();

        $statements = $this->statements->bulkStatements($students);

        return $this->printDocuments->inlinePdf(
            'finance.ar.print-statements',
            [
                'statements' => $statements,
                'documentTitle' => 'Student Account Statements',
                'documentSubtitle' => 'Generated '.now()->format('d F Y'),
                'documentRef' => $this->printDocuments->documentRef('AR', 'STATEMENTS'),
            ],
            'student-statements-'.now()->format('Ymd').'.pdf',
            'landscape',
        );
    }
}
