<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicsAccessService;
use App\Services\AcademicRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AcademicStudentController extends Controller
{
    public function __construct(
        protected AcademicRecordService $academicService,
        protected AcademicsAccessService $access,
    ) {}

    protected function getAcademicsHub(): ?Department
    {
        return Department::findAcademicsHub();
    }

    public function index(Request $request): View
    {
        $department = $this->getAcademicsHub();
        $programIds = $this->access->managedProgramIds($request->user(), $department);

        $query = Student::with(['program', 'campus', 'currentSemester', 'academicRecords'])
            ->orderByDesc('created_at');

        if ($programIds !== null) {
            $query->whereIn('program_id', $programIds);
        }

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

        if ($request->filled('study_mode')) {
            $query->where('study_mode', $request->string('study_mode')->toString() === 'online' ? 'online' : 'physical');
        }

        $students = $query->paginate(25)->appends($request->query());

        $programs = $this->programsForUser($request->user(), $department);
        $statuses = ['active', 'pending', 'deferred', 'suspended', 'withdrawn', 'graduated', 'alumni'];

        $cohortsQuery = Student::distinct();

        if ($programIds !== null) {
            $cohortsQuery->whereIn('program_id', $programIds);
        }

        $cohorts = $cohortsQuery->pluck('cohort_intake')->filter()->sortDesc()->values();
        $studyModes = Student::studyModes();

        return view('academics.students.index', compact('students', 'programs', 'statuses', 'cohorts', 'department', 'studyModes'));
    }

    public function show(Request $request, Student $student): View
    {
        $this->assertManagesStudent($request->user(), $student);

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

    public function create(Request $request): View
    {
        $department = $this->getAcademicsHub();
        $programs = $this->programsForUser($request->user(), $department);
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

        $student = Student::findOrFail($validated['student_id']);
        $this->assertManagesStudent($request->user(), $student);

        $programIds = $this->access->managedProgramIds($request->user());

        abort_unless(
            $programIds === null || in_array((int) $validated['program_id'], $programIds, true),
            403,
            'That programme belongs to another department.'
        );

        $record = $service->create($student, $validated);

        return redirect()
            ->route('academics.students.show', $record->student_id)
            ->with('success', 'Academic record added successfully.');
    }

    /**
     * Programmes the signed-in user runs; every active programme for registry roles.
     */
    private function programsForUser(User $user, ?Department $department): Collection
    {
        $query = $department
            ? $department->programs()->where('status', 'active')
            : AcademicProgram::query()->where('status', 'active');

        $programIds = $this->access->managedProgramIds($user, $department);

        if ($programIds !== null) {
            $query->whereIn('id', $programIds);
        }

        return $query->orderBy('program_name')->get();
    }

    private function assertManagesStudent(User $user, Student $student): void
    {
        abort_unless($this->access->managesStudent($user, $student), 403, 'That student belongs to another department.');
    }
}
