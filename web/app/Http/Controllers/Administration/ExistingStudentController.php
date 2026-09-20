<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Campus;
use App\Models\Student;
use App\Services\AcademicRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExistingStudentController extends Controller
{
    public function __construct(
        protected AcademicRecordService $academicService,
        protected \App\Services\Finance\StudentAccountService $accountService,
    ) {}

    public function create(): View
    {
        $programs = AcademicProgram::where('status', 'active')->orderBy('program_name')->get();
        $campuses = Campus::where('is_active', 1)->orderBy('campus_name')->get();

        return view('administration.applications.existing-student', compact('programs', 'campuses'));
    }

    public function index(Request $request): View
    {
        $query = Student::with(['program', 'campus'])
            ->where('is_active', 1)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%");
            });
        }

        if ($request->filled('cohort_intake')) {
            $query->where('cohort_intake', $request->cohort_intake);
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        $students = $query->paginate(25);

        $programs = AcademicProgram::where('status', 'active')->orderBy('program_name')->get();
        $cohorts = Student::where('is_active', 1)->distinct()->pluck('cohort_intake')->sortDesc()->values();

        return view('administration.applications.existing-student-index', compact('students', 'programs', 'cohorts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'registration_number' => 'required|string|max:50|unique:students,registration_number',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'surname' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'program_id' => 'required|exists:academic_programs,id',
            'year_joined' => 'required|date',
            'program_type' => 'required|in:diploma,certificate',
            'duration_months' => 'required|integer|min:1',
            'semester' => 'required|in:first,second,third',
            'campus_id' => 'required|exists:campuses,id',
            'notes' => 'nullable|string',
        ]);

        $yearJoined = \Carbon\Carbon::parse($validated['year_joined']);

        $student = Student::create([
            'registration_number' => $validated['registration_number'],
            'program_id' => $validated['program_id'],
            'application_id' => null,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'],
            'surname' => $validated['surname'],
            'cohort_intake' => $yearJoined->format('Y'),
            'enrollment_campus_id' => $validated['campus_id'],
            'enrollment_status' => 'active',
            'entry_pathway' => $validated['program_type'],
            'date_of_admission' => $yearJoined,
            'is_active' => 1,
            'created_by' => auth()->id(),
        ]);

        $this->academicService->create($student, [
            'program_id' => $validated['program_id'],
            'enrollment_date' => $yearJoined,
            'status' => 'active',
            'entry_pathway' => $validated['program_type'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->accountService->ensureAccount($student);

        return redirect()
            ->route('administration.applications.existing-student.show', $student->id)
            ->with('success', "Student {$student->registration_number} added successfully.");
    }

    public function show(Student $student): View
    {
        $academicRecords = $student->academicRecords()->with('program', 'semester')->get();

        return view('administration.applications.existing-student-show', compact('student', 'academicRecords'));
    }
}
