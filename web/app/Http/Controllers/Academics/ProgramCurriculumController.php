<?php

namespace App\Http\Controllers\Academics;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\NursingBlock;
use App\Services\AcademicsAccessService;
use App\Services\CurriculumVersionService;
use App\Services\DepartmentDashboardService;
use App\Services\ProgramCurriculumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramCurriculumController extends DepartmentAcademicsController
{
    public function __construct(
        protected ProgramCurriculumService $curriculum,
        protected CurriculumVersionService $versions,
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);

        return view('academics.programs.index', [
            'department' => $hub,
            'programs' => $this->access->programsQueryForHub($request->user(), $hub)->get(),
            'formats' => ProgramCurriculumService::curriculumFormats(),
        ]);
    }

    public function show(Request $request, Department $department, AcademicProgram $program): View
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        $program->load(['department', 'nursingBlocks', 'curriculumVersions']);

        $blocks = NursingBlock::query()->where('program_id', $program->id)->orderBy('block_order')->get();
        $mappings = $this->curriculum->mappedUnits($program);
        $availableUnits = $this->access->unitsInScope($hub, $program->department_id)
            ->where('status', 'active');

        $periods = $program->usesBlocks()
            ? $blocks->map(fn (NursingBlock $block) => [
                'key' => 'block-'.$block->id,
                'label' => $block->block_label,
                'semester' => null,
                'block_id' => $block->id,
            ])
            : collect(range(1, $program->totalTeachingPeriods()))->map(fn (int $number) => [
                'key' => 'sem-'.$number,
                'label' => $program->periodLabel($number),
                'semester' => $number,
                'block_id' => null,
            ]);

        return view('academics.programs.curriculum', [
            'department' => $hub,
            'program' => $program,
            'periods' => $periods,
            'totalTeachingPeriods' => $program->totalTeachingPeriods(),
            'termsPerYear' => $program->termsPerYear(),
            'programYears' => $program->programYears(),
            'mappings' => $mappings,
            'mappingsBySemester' => $mappings->groupBy('semester'),
            'mappingsByBlock' => $mappings->groupBy('block_id'),
            'availableUnits' => $availableUnits,
            'formats' => ProgramCurriculumService::curriculumFormats(),
            'blocks' => $blocks,
            'versions' => $program->curriculumVersions,
            'publishedVersion' => $this->versions->publishedVersionForProgram($program->id),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'canApproveRegistry' => $this->access->canApproveRegistry($request->user()),
            'canApproveCeo' => $this->access->canApproveCeo($request->user()),
        ]);
    }

    public function updateFormat(Request $request, Department $department, AcademicProgram $program): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'curriculum_format' => ['required', 'in:'.implode(',', config('tich-academics.curriculum_formats'))],
            'semester_count' => ['required', 'integer', 'min:1', 'max:12'],
            'block_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $this->curriculum->updateProgramFormat($request->user(), $hub, $program, $validated, $request);

        return back()->with('status', 'Programme structure updated.');
    }

    public function syncUnits(Request $request, Department $department, AcademicProgram $program): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

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

        $this->curriculum->syncProgramUnits($request->user(), $hub, $program, $validated['mappings'], $request);

        return back()->with('status', 'Programme unit mapping saved.');
    }

    public function createVersion(Request $request, Department $department, AcademicProgram $program): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'version_label' => ['nullable', 'string', 'max:100'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $version = $this->versions->createDraft($request->user(), $hub, $program, $validated, $request);

        return back()->with('status', "Curriculum version {$version->version_label} created.");
    }

    public function submitVersion(Request $request, Department $department, CurriculumVersion $version): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->versions->submit($request->user(), $hub, $version, $request);

        return back()->with('status', 'Curriculum version submitted for registry review.');
    }

    public function approveVersionRegistry(Request $request, Department $department, CurriculumVersion $version): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->versions->approveRegistry($request->user(), $hub, $version, $request);

        return back()->with('status', 'Curriculum version approved by registrar.');
    }

    public function approveVersionCeo(Request $request, Department $department, CurriculumVersion $version): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->versions->approveCeo($request->user(), $hub, $version, $request);

        return back()->with('status', 'Curriculum version published.');
    }
}
