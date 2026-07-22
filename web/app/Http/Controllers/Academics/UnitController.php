<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\Unit;
use App\Services\AcademicsAccessService;
use App\Services\DepartmentDashboardService;
use App\Services\UnitCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends DepartmentAcademicsController
{
    public function __construct(
        protected UnitCatalogService $units,
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

        $learningDepartment = $learningDepartmentId
            ? Department::query()->find($learningDepartmentId)
            : null;

        return view('academics.units.index', [
            'department' => $hub,
            'learningDepartment' => $learningDepartment,
            'units' => $this->units->listForHub(
                $hub,
                $learningDepartmentId,
                $request->string('status')->toString() ?: null
            ),
            'learningDepartments' => $this->access->learningDepartmentsInScope($request->user(), $hub),
            'filters' => [
                'learning_department' => $learningDepartmentId,
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
        $this->units->create($request->user(), $hub, $validated, $request);

        return back()->with('status', 'Unit created as draft.');
    }

    public function update(Request $request, Department $department, Unit $unit): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $validated = $this->validateUnit($request, $unit);
        $this->units->update($request->user(), $hub, $unit, $validated, $request);

        return back()->with('status', 'Unit updated.');
    }

    public function submit(Request $request, Department $department, Unit $unit): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->units->submitForRegistry($request->user(), $hub, $unit, $request);

        return back()->with('status', 'Unit submitted for registry verification.');
    }

    public function approve(Request $request, Department $department, Unit $unit): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->units->approveRegistry($request->user(), $hub, $unit, $request);

        return back()->with('status', 'Unit approved and activated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUnit(Request $request, ?Unit $unit = null): array
    {
        return $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'unit_code' => ['required', 'string', 'max:30', 'unique:units,unit_code,'.($unit?->id ?? 'NULL')],
            'unit_name' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:2000'],
            'program_id' => ['nullable', 'exists:academic_programs,id'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:12'],
            'block' => ['nullable', 'integer', 'min:0', 'max:10'],
            'credit_hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'contact_hours' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'total_learning_hours' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'display_priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_core' => ['nullable', 'boolean'],
            'is_practical' => ['nullable', 'boolean'],
            'prerequisite_unit_id' => ['nullable', 'exists:units,id'],
            'co_requisite_unit_id' => ['nullable', 'exists:units,id'],
        ]);
    }
}
