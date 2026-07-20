<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ProgramsService;
use Illuminate\View\View;

class ProgramsController extends Controller
{
    public function __construct(protected ProgramsService $programsService) {}

    public function index(): View
    {
        return view('programs.index', $this->programsService->getCatalog());
    }
}
