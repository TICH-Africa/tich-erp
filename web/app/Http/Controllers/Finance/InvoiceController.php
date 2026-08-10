<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\Finance\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
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
        return view('finance.invoices.create', [
            'students' => Student::query()->with(['applicant', 'program'])->orderBy('registration_number')->limit(200)->get(),
            'feeStructures' => FeeStructure::query()->with(['program', 'academicYear'])->where('is_active', 1)->where('is_approved', 1)->get(),
            'invoiceTypes' => config('finance.invoice_types'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_type' => 'nullable|string|in:'.implode(',', array_keys(config('finance.invoice_types'))),
            'description' => 'nullable|string|max:500',
            'amount' => 'nullable|numeric|min:0.01',
            'fee_structure_id' => 'nullable|exists:fee_structures,id',
            'fee_structure_charge' => 'nullable|string|in:semester,application,qa_annual,indexing_nck,graduation',
            'include_optional_charges' => 'nullable|boolean',
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);
        $staffId = (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id'));

        if (! empty($validated['fee_structure_id'])) {
            $feeStructure = FeeStructure::query()->findOrFail($validated['fee_structure_id']);
            $charge = $validated['fee_structure_charge'] ?? 'semester';
            $includeOptional = (bool) ($validated['include_optional_charges'] ?? false);

            $invoice = match ($charge) {
                'application' => $this->invoices->generateApplicationInvoice($student, $feeStructure, $staffId),
                'qa_annual' => $this->invoices->generateQaAnnualInvoice($student, $feeStructure, $staffId),
                'indexing_nck' => $this->invoices->generateIndexingInvoice($student, $feeStructure, $staffId),
                'graduation' => $this->invoices->generateGraduationInvoice($student, $feeStructure, $staffId),
                default => $this->invoices->generateSemesterInvoice($student, $feeStructure, $staffId, $includeOptional),
            };
        } else {
            $request->validate([
                'invoice_type' => 'required|string|in:'.implode(',', array_keys(config('finance.invoice_types'))),
                'description' => 'required|string|max:500',
                'amount' => 'required|numeric|min:0.01',
            ]);

            $invoice = $this->invoices->generateForStudent($student, [
                'invoice_type' => $validated['invoice_type'],
                'description' => $validated['description'],
                'amount' => (float) $validated['amount'],
            ], $staffId);
        }

        return redirect()->route('finance.invoices.show', $invoice)->with('success', 'Invoice generated and dispatched to the student portal.');
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
