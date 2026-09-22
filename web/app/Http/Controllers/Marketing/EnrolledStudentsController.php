<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrolledStudentsController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::query()
            ->where('is_active', true)
            ->with(['program', 'campus', 'currentSemester']);

        if ($intake = (string) $request->string('intake')->trim()) {
            $query->where('cohort_intake', 'like', "%{$intake}%");
        }

        if ($programId = (string) $request->string('program_id')->trim()) {
            $query->where('program_id', (int) $programId);
        }

        if ($status = (string) $request->string('status')->trim()) {
            $query->where('enrollment_status', $status);
        }

        $students = $query->orderBy('registration_number')->paginate(50);
        $intakes = Student::query()->distinct()->pluck('cohort_intake')->filter()->sort();
        $programs = \App\Models\AcademicProgram::query()->orderBy('program_name')->get();
        $statuses = ['enrolled', 'graduated', 'withdrawn', 'suspended', 'graduated'];

        return view('marketing.enrolled-students.index', [
            'students' => $students,
            'intakes' => $intakes,
            'programs' => $programs,
            'statuses' => $statuses,
            'filters' => $request->only(['intake', 'program_id', 'status']),
        ]);
    }

    public function compare(Request $request): View
    {
        $intake1 = (string) $request->string('intake_1')->trim();
        $intake2 = (string) $request->string('intake_2')->trim();
        $intake3 = (string) $request->string('intake_3')->trim();

        $intakes = Student::query()->distinct()->pluck('cohort_intake')->filter()->sort();
        $programs = \App\Models\AcademicProgram::query()->orderBy('program_name')->get();

        $stats = [];
        foreach ([$intake1, $intake2, $intake3] as $key => $intake) {
            if (! $intake) {
                continue;
            }

            $query = Student::query()->where('cohort_intake', $intake)->where('is_active', true);

            $stats[] = [
                'intake' => $intake,
                'total' => (clone $query)->count(),
                'by_program' => $query->select('program_id')
                    ->selectRaw('count(*) as count')
                    ->groupBy('program_id')
                    ->get()
                    ->mapWithKeys(fn ($row) => [$row->program_id => $row->count]),
                'enrollment_status' => $query->select('enrollment_status')
                    ->selectRaw('count(*) as count')
                    ->groupBy('enrollment_status')
                    ->get()
                    ->mapWithKeys(fn ($row) => [$row->enrollment_status => $row->count]),
                'males' => (clone $query)->where('gender', 'male')->count(),
                'females' => (clone $query)->where('gender', 'female')->count(),
            ];
        }

        return view('marketing.enrolled-students.compare', [
            'stats' => $stats,
            'intakes' => $intakes,
            'programs' => $programs,
            'intake_1' => $intake1,
            'intake_2' => $intake2,
            'intake_3' => $intake3,
        ]);
    }
}
