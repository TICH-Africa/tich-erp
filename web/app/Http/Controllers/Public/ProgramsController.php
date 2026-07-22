<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ProgramsService;
use Illuminate\View\View;

class ProgramsController extends Controller
{
    public function __construct(protected ProgramsService $programsService) {}

    public function index(\Illuminate\Http\Request $request): View
    {
        $search = $request->query('search');
        $departmentCode = $request->query('department');

        $data = $this->programsService->getCatalog($search, $departmentCode);

        return view('programs.index', $data);
    }
}
