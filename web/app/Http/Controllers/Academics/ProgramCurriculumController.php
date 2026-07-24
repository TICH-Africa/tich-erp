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
use App\Services\UnitCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramCurriculumController extends DepartmentAcademicsController
{
    public function __construct(
        protected ProgramCurriculumService $curriculum,
        protected CurriculumVersionService $versions,
        protected UnitCatalogService $unitCatalog,
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        $learningDepartmentId = $request->integer('learning_department') ?: null;

        if ($learningDepartmentId) {
            abort_unless(in_array($learningDepartmentId, $hub->academicsScopeDepartmentIds(), true), 404);
        }

        $programsQuery = $this->access->programsQueryForHub($request->user(), $hub);

        if ($learningDepartmentId) {
            $programsQuery->where('department_id', $learningDepartmentId);
        }

        $learningDepartment = $learningDepartmentId
            ? Department::query()->find($learningDepartmentId)
            : null;

        return view('academics.programs.index', [
            'department' => $hub,
            'learningDepartment' => $learningDepartment,
            'programs' => $programsQuery->get(),
            'formats' => ProgramCurriculumService::curriculumFormats(),
        ]);
    }

    public function show(Request $request, Department $department, AcademicProgram $program): View
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        $program->load(['department', 'nursingBlocks']);

        $blocks = NursingBlock::query()->where('program_id', $program->id)->orderBy('block_order')->get();
        $availableUnits = $this->access->unitsInScope($hub, $program->department_id)
            ->whereIn('status', CurriculumVersionService::mappableUnitStatuses())
            ->values();

        $catalogUnitCounts = $this->access->unitsInScope($hub, $program->department_id)
            ->groupBy('status')
            ->map->count();

        $learningDepartmentId = $request->integer('learning_department') ?: (int) $program->department_id;
        $learningDepartment = Department::query()->find($learningDepartmentId);

        $intakes = $this->versions->intakesForProgram($program->id);
        $selectedIntake = $this->versions->resolveSelectedIntake($program, $request->integer('intake') ?: null);
        $mappings = $selectedIntake
            ? $this->versions->mappedUnits($selectedIntake)
            : collect();

        $periods = $program->usesBlocks()
            ? $blocks->map(fn (NursingBlock $block) => [
                'key' => 'block-'.$block->id,
                'label' => $block->block_label,
                'semester' => $block->block_order,
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
            'learningDepartment' => $learningDepartment,
            'program' => $program,
            'periods' => $periods,
            'totalTeachingPeriods' => $program->totalTeachingPeriods(),
            'termsPerYear' => $program->termsPerYear(),
            'programYears' => $program->programYears(),
            'intakes' => $intakes,
            'selectedIntake' => $selectedIntake,
            'mappings' => $mappings,
            'mappingsBySemester' => $mappings->groupBy('semester'),
            'mappingsByBlock' => $mappings->groupBy('block_id'),
            'availableUnits' => $availableUnits,
            'catalogUnitCounts' => $catalogUnitCounts,
            'formats' => ProgramCurriculumService::curriculumFormats(),
            'intakeMonths' => CurriculumVersion::intakeMonths(),
            'blocks' => $blocks,
            'publishedVersion' => $this->versions->publishedVersionForProgram($program->id),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'canApproveRegistry' => $this->access->canApproveRegistry($request->user()),
            'canApproveCeo' => $this->access->canApproveCeo($request->user()),
            'catalogUnits' => $this->unitCatalog->listForHub($hub, (int) $program->department_id),
            'statusLabels' => UnitCatalogService::statusLabels(),
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
            'mappings' => ['nullable', 'array'],
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

        $this->curriculum->syncProgramUnits(
            $request->user(),
            $hub,
            $program,
            $validated['mappings'] ?? [],
            $request
        );

        return back()->with('status', 'Programme unit mapping saved.');
    }

    public function createVersion(Request $request, Department $department, AcademicProgram $program): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'intake_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'intake_month' => ['required', 'integer', 'min:1', 'max:12'],
            'version_label' => ['nullable', 'string', 'max:100'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'copy_from_version_id' => ['nullable', 'exists:curriculum_versions,id'],
            'copy_from_program_template' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $version = $this->versions->createDraft($request->user(), $hub, $program, $validated, $request);

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'department' => $hub,
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $version->id,
            ]))
            ->with('status', "Intake {$version->intakeLabel()} created.");
    }

    public function syncIntakeUnits(Request $request, Department $department, AcademicProgram $program, CurriculumVersion $version): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $version->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'mappings' => ['nullable', 'array'],
            'mappings.*.unit_id' => ['required', 'exists:units,id'],
            'mappings.*.include' => ['nullable', 'boolean'],
            'mappings.*.semester' => ['nullable', 'integer', 'min:1', 'max:24'],
            'mappings.*.block_id' => ['nullable', 'exists:nursing_blocks,id'],
            'mappings.*.is_compulsory' => ['nullable', 'boolean'],
            'mappings.*.display_order' => ['nullable', 'integer', 'min:0'],
            'mappings.*.priority' => ['nullable', 'integer', 'min:0'],
            'mappings.*.contact_hours' => ['nullable', 'integer', 'min:0'],
            'mappings.*.total_learning_hours' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->versions->syncVersionUnits(
            $request->user(),
            $hub,
            $version,
            $validated['mappings'] ?? [],
            $request
        );

        return back()->with('status', 'Intake unit mapping saved.');
    }

    public function addIntakeUnit(Request $request, Department $department, AcademicProgram $program, CurriculumVersion $version, int $semester): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $version->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'block_id' => ['nullable', 'exists:nursing_blocks,id'],
        ]);

        $this->versions->addUnitToPeriod(
            $request->user(),
            $hub,
            $version,
            (int) $validated['unit_id'],
            $semester,
            isset($validated['block_id']) ? (int) $validated['block_id'] : null,
            $request
        );

        return back()->with('status', 'Unit added to semester.');
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
