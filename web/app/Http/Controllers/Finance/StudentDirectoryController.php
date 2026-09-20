<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\StudentFinancialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDirectoryController extends Controller
{
    public function __construct(
        protected StudentFinancialService $financialService,
    ) {}

    public function index(Request $request): View
    {
        $students = Student::with(['program', 'campus', 'studentAccounts'])
            ->where('is_active', 1)
            ->orderByDesc('created_at')
            ->paginate(25);

        $programs = AcademicProgram::where('status', 'active')->orderBy('program_name')->get();

        return view('finance.student-directory.index', compact('students', 'programs'));
    }

    public function show(Student $student): View
    {
        $summary = $this->financialService->getSummary($student);
        $financialHistory = $this->financialService->getForStudent($student);
        $academicRecords = $student->academicRecords()->with('program')->get();
        $academicYears = AcademicYear::orderByDesc('id')->get();

        return view('finance.student-directory.show', compact('student', 'summary', 'financialHistory', 'academicRecords', 'academicYears'));
    }

    public function createPastRecord(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'total_chargeable' => 'required|numeric|min:0',
            'total_paid' => 'nullable|numeric|min:0',
            'outstanding_balance' => 'nullable|numeric',
            'payment_method' => 'nullable|in:mpesa,bank_transfer,cheque,cash',
            'payment_reference' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validated['outstanding_balance'] === null && isset($validated['total_chargeable'], $validated['total_paid'])) {
            $validated['outstanding_balance'] = $validated['total_chargeable'] - ($validated['total_paid'] ?? 0);
        }

        $this->financialService->addPastRecord($student, $validated);

        return redirect()
            ->route('finance.students.show', $student->id)
            ->with('success', 'Past financial record added successfully.');
    }
}
