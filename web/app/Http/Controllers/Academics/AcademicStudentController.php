<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\Student;
use App\Services\AcademicRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicStudentController extends Controller
{
    public function __construct(
        protected AcademicRecordService $academicService,
    ) {}

    protected function getAcademicsHub(): ?Department
    {
        return Department::findAcademicsHub();
    }

    public function index(Request $request): View
    {
        $department = $this->getAcademicsHub();
        $query = Student::with(['program', 'campus', 'currentSemester', 'academicRecords'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%");
            });
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('status')) {
            $query->where('enrollment_status', $request->status);
        }

        if ($request->filled('cohort')) {
            $query->where('cohort_intake', $request->cohort);
        }

        $students = $query->paginate(25)->appends($request->query());

        $programs = $department
            ? $department->programs()->where('status', 'active')->orderBy('program_name')->get()
            : AcademicProgram::where('status', 'active')->orderBy('program_name')->get();
        $statuses = ['active', 'pending', 'deferred', 'suspended', 'withdrawn', 'graduated'];
        $cohorts = Student::distinct()->pluck('cohort_intake')->filter()->sortDesc()->values();

        return view('academics.students.index', compact('students', 'programs', 'statuses', 'cohorts', 'department'));
    }

    public function show(Student $student): View
    {
        $academicHistory = $student->academicRecords()
            ->with(['program', 'semester', 'academicYear'])
            ->orderByDesc('academic_year_id')
            ->orderByDesc('semester_id')
            ->get();

        $groupedHistory = $academicHistory->groupBy(function ($record) {
            return ($record->academicYear?->year_label ?? 'Unknown') . ' - ' . ($record->semester?->semester_number ?? 'N/A');
        });

        $department = $this->getAcademicsHub();

        return view('academics.students.show', compact('student', 'academicHistory', 'groupedHistory', 'department'));
    }

    public function create(): View
    {
        $department = $this->getAcademicsHub();
        $programs = $department
            ? $department->programs()->where('status', 'active')->orderBy('program_name')->get()
            : AcademicProgram::where('status', 'active')->orderBy('program_name')->get();
        $studentIds = $programs->isNotEmpty()
            ? Student::whereIn('program_id', $programs->pluck('id'))->where('is_active', 1)->pluck('id')
            : collect();
        $students = Student::with(['program', 'campus'])
            ->whereIn('id', $studentIds)
            ->orderBy('registration_number')
            ->get();

        return view('academics.students.create', compact('programs', 'students', 'department'));
    }

    public function store(Request $request, AcademicRecordService $service): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'program_id' => 'required|exists:academic_programs,id',
            'enrollment_date' => 'required|date',
            'status' => 'required|in:active,completed,withdrawn,deferred',
            'gpa' => 'nullable|numeric|min:0|max:4',
            'units_registered' => 'nullable|integer|min:0',
            'units_completed' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $record = $service->create(Student::findOrFail($validated['student_id']), $validated);

        return redirect()
            ->route('academics.students.show', $record->student_id)
            ->with('success', 'Academic record added successfully.');
    }
}
