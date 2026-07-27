<?php

namespace App\Http\Controllers\Academics;

use App\Models\AcademicProgram;
use App\Services\NursingBlockProgressionService;
use Illuminate\Http\Request;

class NursingBlockController extends DepartmentAcademicsController
{
    public function index(Request $request, AcademicProgram $program, NursingBlockProgressionService $blockProgression)
    {
        $hub = $this->authorizeHub($request, $request->route('department'));

        $blocks = $blockProgression->getAllBlocksWithProgress($program->id);

        return view('academics.nursing-blocks.index', [
            'department' => $hub,
            'program' => $program,
            'blocks' => $blocks,
        ]);
    }
}