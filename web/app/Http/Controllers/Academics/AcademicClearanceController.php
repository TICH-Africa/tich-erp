<?php

namespace App\Http\Controllers\Academics;

use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AcademicClearanceController extends DepartmentAcademicsController
{
    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        $programId = (int) ($request->query('program_id', 0) ?: 0);
        $search = trim((string) $request->query('search', ''));

        $query = Student::query()
            ->with(['program:id,program_code,program_name', 'applicant:id,first_name,surname,middle_name'])
            ->where('is_active', 1);

        // An HOD clears their own department's students; registry roles see the whole institution.
        $programIds = $this->access->managedProgramIds($request->user(), $hub);

        if ($programIds !== null) {
            $query->whereIn('program_id', $programIds);
        }

        if ($programId > 0) {
            $query->where('program_id', $programId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                    ->orWhereHas('applicant', function ($sq) use ($search) {
                        $sq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%");
                    });
            });
        }

        $students = Schema::hasTable('students')
            ? $query->orderBy('registration_number')->paginate(20)->withQueryString()
            : collect();

        $statusCounts = Student::query();

        if ($programIds !== null) {
            $statusCounts->whereIn('program_id', $programIds);
        }

        $clearedCount = Schema::hasTable('students') && Schema::hasColumn('students', 'academic_clearance_status')
            ? (clone $statusCounts)->where('academic_clearance_status', 'cleared')->count()
            : 0;
        $pendingCount = Schema::hasTable('students') && Schema::hasColumn('students', 'academic_clearance_status')
            ? (clone $statusCounts)->where('academic_clearance_status', '!=', 'cleared')->count()
            : 0;

        $programQuery = AcademicProgram::query()->where('status', 'active');

        if ($programIds !== null) {
            $programQuery->whereIn('department_id', $this->access->accessibleLearningDepartmentIds($request->user(), $hub));
        }

        $programs = Schema::hasTable('academic_programs')
            ? $programQuery->orderBy('program_name')->get(['id', 'program_code', 'program_name'])
            : collect();

        return view('academics.clearance.index', [
            'department' => $hub,
            'students' => $students,
            'programs' => $programs,
            'programId' => $programId,
            'search' => $search,
            'clearedCount' => $clearedCount,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function approve(Request $request, Department $department, Student $student): RedirectResponse
    {
        $this->authorizeHub($request, $department);
        $this->assertManagesStudent($request->user(), $student);

        if (Schema::hasColumn('students', 'academic_clearance_status')) {
            $student->update([
                'academic_clearance_status' => 'cleared',
                'academically_cleared_at' => now(),
                'academically_cleared_by' => $request->user()->id,
            ]);
        }

        return back()->with('status', 'Student cleared academically.');
    }

    public function reject(Request $request, Department $department, Student $student): RedirectResponse
    {
        $this->authorizeHub($request, $department);
        $this->assertManagesStudent($request->user(), $student);

        if (Schema::hasColumn('students', 'academic_clearance_status')) {
            $student->update([
                'academic_clearance_status' => 'pending',
                'academically_cleared_at' => null,
                'academically_cleared_by' => null,
            ]);
        }

        return back()->with('status', 'Academic clearance revoked.');
    }

    private function assertManagesStudent(User $user, Student $student): void
    {
        abort_unless($this->access->managesStudent($user, $student), 403, 'That student belongs to another department.');
    }
}
