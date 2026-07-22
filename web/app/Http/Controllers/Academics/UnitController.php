<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\AcademicsAccessService;
use App\Services\UnitCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected UnitCatalogService $units,
    ) {}

    public function index(Request $request): View
    {
        $departmentId = $request->integer('department') ?: null;

        return view('academics.units.index', [
            'units' => $this->units->listForDepartment($request->user(), $departmentId, $request->string('status')->toString() ?: null),
            'departments' => $this->access->learningDepartmentsForUser($request->user()),
            'filters' => [
                'department' => $departmentId,
                'status' => $request->string('status')->toString(),
            ],
            'statusLabels' => UnitCatalogService::statusLabels(),
            'canApproveRegistry' => $this->access->canApproveRegistry($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUnit($request);
        $this->units->create($request->user(), $validated, $request);

        return back()->with('status', 'Unit created as draft.');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $this->validateUnit($request, $unit);
        $this->units->update($request->user(), $unit, $validated, $request);

        return back()->with('status', 'Unit updated.');
    }

    public function submit(Request $request, Unit $unit): RedirectResponse
    {
        $this->units->submitForRegistry($request->user(), $unit, $request);

        return back()->with('status', 'Unit submitted for registry verification.');
    }

    public function approve(Request $request, Unit $unit): RedirectResponse
    {
        $this->units->approveRegistry($request->user(), $unit, $request);

        return back()->with('status', 'Unit approved and activated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUnit(Request $request, ?Unit $unit = null): array
    {
        return $request->validate([
            'department_id' => [$unit ? 'sometimes' : 'required', 'exists:departments,id'],
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
