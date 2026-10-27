<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\Student;
use App\Services\AlumniService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Academics reads the alumni register Admissions keeps. Nothing here writes - opening a graduate
 * goes to the same student record Academics already uses.
 */
class AlumniController extends Controller
{
    public function __construct(
        protected AlumniService $alumni,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'graduation_year' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'program_id' => ['nullable', 'integer'],
        ]);

        return view('academics.alumni.index', [
            // The Academics layout draws its sidebar from the hub department, as every other
            // screen in this prefix does.
            'department' => Department::findAcademicsHub(),
            'students' => $this->alumni->list($filters),
            'filters' => $filters,
            'years' => $this->alumni->graduationYears(),
            'programs' => AcademicProgram::query()->orderBy('program_name')->get(['id', 'program_name', 'program_code']),
            'total' => Student::query()->alumni()->count(),
        ]);
    }

    /** A graduate's own file, read only: Admissions keeps the register, Academics looks it up here. */
    public function show(Student $student): View
    {
        abort_unless($this->alumni->isAlumnus($student), 404);

        $student->load([
            'program:id,program_name,program_code',
            'campus:id,campus_name,campus_type',
            'academicRecords' => fn ($query) => $query->with('program:id,program_name,program_code')->orderBy('id'),
        ]);

        return view('academics.alumni.show', [
            'department' => Department::findAcademicsHub(),
            'student' => $student,
        ]);
    }
}
