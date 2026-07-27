<?php

namespace App\Http\Controllers\Academics;

use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\Unit;
use App\Services\AcademicsAccessService;
use App\Services\CurriculumVersionService;
use App\Services\DepartmentDashboardService;
use App\Services\UnitCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends DepartmentAcademicsController
{
    public function __construct(
        protected UnitCatalogService $units,
        protected CurriculumVersionService $versions,
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View|RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $learningDepartmentId = $request->integer('learning_department') ?: null;

        if ($learningDepartmentId) {
            abort_unless(in_array($learningDepartmentId, $hub->academicsScopeDepartmentIds(), true), 404);

            return redirect()
                ->route('departments.academics.programs.index', [
                    'department' => $hub,
                    'learning_department' => $learningDepartmentId,
                ])
                ->with('status', 'Open a programme curriculum to create and manage units.');
        }

        return view('academics.units.index', [
            'department' => $hub,
            'learningDepartment' => null,
            'units' => $this->units->listForHub(
                $hub,
                null,
                $request->string('status')->toString() ?: null
            ),
            'learningDepartments' => $this->access->learningDepartmentsInScope($request->user(), $hub),
            'filters' => [
                'learning_department' => null,
                'status' => $request->string('status')->toString(),
            ],
            'statusLabels' => UnitCatalogService::statusLabels(),
            'canApproveRegistry' => $this->access->canApproveRegistry($request->user()),
        ]);
    }

    public function store(Request $request, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $validated = $this->validateUnit($request);
        $unit = $this->units->create($request->user(), $hub, $validated, $request);

        $this->assignToIntakeIfRequested($request, $hub, $unit);

        return $this->redirectAfterUnitAction($request, $hub, 'Unit created as draft.');
    }

    public function update(Request $request, Department $department, Unit $unit): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $validated = $this->validateUnit($request, $unit);
        $this->units->update($request->user(), $hub, $unit, $validated, $request);

        return $this->redirectAfterUnitAction($request, $hub, 'Unit updated.');
    }

    public function submit(Request $request, Department $department, Unit $unit): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->units->submitForRegistry($request->user(), $hub, $unit, $request);

        return $this->redirectAfterUnitAction($request, $hub, 'Unit submitted for registry verification.');
    }

    public function approve(Request $request, Department $department, Unit $unit): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->units->approveRegistry($request->user(), $hub, $unit, $request);

        return $this->redirectAfterUnitAction($request, $hub, 'Unit approved and activated.');
    }

    public function pendingRegistry(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);

        return view('academics.units.pending-registry', [
            'department' => $hub,
            'units' => $this->units->pendingUnitsForApproval($hub),
            'learningDepartments' => $this->access->learningDepartmentsInScope($request->user(), $hub),
            'statusLabels' => UnitCatalogService::statusLabels(),
        ]);
    }

    private function assignToIntakeIfRequested(Request $request, Department $hub, Unit $unit): void
    {
        if (! $request->filled('assign_intake') || ! $request->filled('assign_semester')) {
            return;
        }

        $version = CurriculumVersion::query()->find($request->integer('assign_intake'));

        if (! $version || (int) $version->program_id !== (int) $request->integer('return_program')) {
            return;
        }

        if ($version->status !== 'draft') {
            return;
        }

        try {
            $this->versions->addUnitToPeriod(
                $request->user(),
                $hub,
                $version,
                $unit->id,
                $request->integer('assign_semester'),
                $request->integer('block_id') ?: null,
                $request
            );
        } catch (\Throwable) {
            // Unit was created; assignment can be done manually from the catalog table.
        }
    }

    private function redirectAfterUnitAction(Request $request, Department $hub, string $message): RedirectResponse
    {
        if ($request->filled('return_program')) {
            return redirect()->route('departments.academics.programs.curriculum', array_filter([
                'department' => $hub,
                'program' => $request->integer('return_program'),
                'learning_department' => $request->integer('return_learning_department') ?: null,
                'intake' => $request->integer('return_intake') ?: null,
                'section' => $request->string('return_section')->toString() ?: 'catalog',
            ]))->with('status', $message);
        }

        return back()->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUnit(Request $request, ?Unit $unit = null): array
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'unit_code' => ['required', 'string', 'max:30', 'unique:units,unit_code,'.($unit?->id ?? 'NULL')],
            'unit_name' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:2000'],
            'program_id' => ['nullable', 'exists:academic_programs,id'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:24'],
            'assign_semester' => ['nullable', 'integer', 'min:1', 'max:24'],
            'block' => ['nullable', 'integer', 'min:0', 'max:10'],
            'credit_hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'contact_hours' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'total_learning_hours' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'display_priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_core' => ['nullable', 'boolean'],
            'is_practical' => ['nullable', 'boolean'],
            'prerequisite_unit_id' => ['nullable', 'exists:units,id'],
            'co_requisite_unit_id' => ['nullable', 'exists:units,id'],
            'return_program' => ['nullable', 'integer'],
            'return_learning_department' => ['nullable', 'integer'],
            'return_intake' => ['nullable', 'integer'],
            'return_section' => ['nullable', 'string', 'max:50'],
            'assign_intake' => ['nullable', 'integer'],
        ]);

        if (! empty($validated['assign_semester']) && empty($validated['semester'])) {
            $validated['semester'] = $validated['assign_semester'];
        }

        return $validated;
    }
}
