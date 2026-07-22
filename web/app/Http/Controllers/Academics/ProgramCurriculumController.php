<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\CurriculumVersion;
use App\Models\NursingBlock;
use App\Models\Unit;
use App\Services\AcademicsAccessService;
use App\Services\CurriculumVersionService;
use App\Services\ProgramCurriculumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramCurriculumController extends Controller
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected ProgramCurriculumService $curriculum,
        protected CurriculumVersionService $versions,
    ) {}

    public function index(Request $request): View
    {
        return view('academics.programs.index', [
            'programs' => $this->access->programsQueryForUser($request->user())->get(),
            'formats' => ProgramCurriculumService::curriculumFormats(),
        ]);
    }

    public function show(Request $request, AcademicProgram $program): View
    {
        $this->access->findProgramForUser($request->user(), $program->id);
        $program->load(['department', 'nursingBlocks', 'curriculumVersions']);

        $availableUnits = Unit::query()
            ->where('department_id', $program->department_id)
            ->where('status', 'active')
            ->orderBy('display_priority')
            ->orderBy('unit_code')
            ->get();

        return view('academics.programs.curriculum', [
            'program' => $program,
            'mappings' => $this->curriculum->mappedUnits($program),
            'availableUnits' => $availableUnits,
            'formats' => ProgramCurriculumService::curriculumFormats(),
            'blocks' => NursingBlock::query()->where('program_id', $program->id)->orderBy('block_order')->get(),
            'versions' => $program->curriculumVersions,
            'publishedVersion' => $this->versions->publishedVersionForProgram($program->id),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'canApproveRegistry' => $this->access->canApproveRegistry($request->user()),
            'canApproveCeo' => $this->access->canApproveCeo($request->user()),
        ]);
    }

    public function updateFormat(Request $request, AcademicProgram $program): RedirectResponse
    {
        $this->access->findProgramForUser($request->user(), $program->id);

        $validated = $request->validate([
            'curriculum_format' => ['required', 'in:'.implode(',', config('tich-academics.curriculum_formats'))],
            'semester_count' => ['nullable', 'integer', 'min:1', 'max:12'],
            'block_count' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        $this->curriculum->updateProgramFormat($request->user(), $program, $validated, $request);

        return back()->with('status', 'Programme curriculum format updated.');
    }

    public function syncUnits(Request $request, AcademicProgram $program): RedirectResponse
    {
        $this->access->findProgramForUser($request->user(), $program->id);

        $validated = $request->validate([
            'mappings' => ['required', 'array'],
            'mappings.*.unit_id' => ['required', 'exists:units,id'],
            'mappings.*.include' => ['nullable', 'boolean'],
            'mappings.*.semester' => ['nullable', 'integer', 'min:1', 'max:12'],
            'mappings.*.block_id' => ['nullable', 'exists:nursing_blocks,id'],
            'mappings.*.is_compulsory' => ['nullable', 'boolean'],
            'mappings.*.display_order' => ['nullable', 'integer', 'min:0'],
            'mappings.*.priority' => ['nullable', 'integer', 'min:0'],
            'mappings.*.contact_hours' => ['nullable', 'integer', 'min:0'],
            'mappings.*.total_learning_hours' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->curriculum->syncProgramUnits($request->user(), $program, $validated['mappings'], $request);

        return back()->with('status', 'Programme unit mapping saved.');
    }

    public function createVersion(Request $request, AcademicProgram $program): RedirectResponse
    {
        $this->access->findProgramForUser($request->user(), $program->id);

        $validated = $request->validate([
            'version_label' => ['nullable', 'string', 'max:100'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $version = $this->versions->createDraft($request->user(), $program, $validated, $request);

        return back()->with('status', "Curriculum version {$version->version_label} created.");
    }

    public function submitVersion(Request $request, CurriculumVersion $version): RedirectResponse
    {
        $this->versions->submit($request->user(), $version, $request);

        return back()->with('status', 'Curriculum version submitted for registry review.');
    }

    public function approveVersionRegistry(Request $request, CurriculumVersion $version): RedirectResponse
    {
        $this->versions->approveRegistry($request->user(), $version, $request);

        return back()->with('status', 'Curriculum version approved by registrar.');
    }

    public function approveVersionCeo(Request $request, CurriculumVersion $version): RedirectResponse
    {
        $this->versions->approveCeo($request->user(), $version, $request);

        return back()->with('status', 'Curriculum version published.');
    }
}
