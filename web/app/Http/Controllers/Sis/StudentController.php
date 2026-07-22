<?php

namespace App\Http\Controllers\Sis;

use App\Http\Controllers\Controller;
use App\Services\StudentRecordService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(protected StudentRecordService $studentRecords) {}

    public function index(Request $request): View
    {
        return view('sis.students.index', [
            'students' => $this->studentRecords->paginate($request->only(['search', 'status', 'program_id'])),
            'filters' => $request->only(['search', 'status', 'program_id']),
        ]);
    }

    public function show(int $student): View
    {
        $record = $this->studentRecords->findForHub($student);

        return view('sis.students.show', [
            'student' => $record,
            'biodata' => $this->studentRecords->biodata360($record),
        ]);
    }
}
