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
}
