<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ProgramsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProgramsController extends Controller
{
    public function __construct(protected ProgramsService $programsService) {}

    public function index(): View
    {
        $data = $this->programsService->getCatalog(null, null);

        return view('programs.index', $data);
    }

    public function show(string $code): View|RedirectResponse
    {
        $program = $this->programsService->findActiveProgramByCode($code);

        abort_if(! $program, 404);

        if ((string) $program->program_code !== $code) {
            return redirect()->route('programs.show', $program->program_code, 301);
        }

        return view('programs.show', [
            'program' => $program,
        ]);
    }
}
