<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\UnitAllocation;
use App\Services\AcademicsAccessService;
use App\Services\DepartmentDashboardService;
use App\Services\UnitAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The desk where Academics signs off the lecturer allocations a department HOD has requested.
 */
class UnitAllocationApprovalController extends DepartmentAcademicsController
{
    public function __construct(
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
        protected UnitAllocationService $allocations,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeApprover($request, $department);

        $status = (string) $request->query('status', UnitAllocation::STATUS_PENDING);

        if (! array_key_exists($status, UnitAllocation::statusLabels())) {
            $status = UnitAllocation::STATUS_PENDING;
        }

        $scopeIds = $this->access->accessibleLearningDepartmentIds($request->user(), $hub);

        $allocations = UnitAllocation::query()
            ->with([
                'unit.department',
                'staff',
                'program',
                'semester.academicYear',
                'campus',
                'requestedBy',
                'decidedBy',
            ])
            ->whereHas('unit', fn ($query) => $query->whereIn('department_id', $scopeIds))
            ->where('status', $status)
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('academics.unit-allocations.index', [
            'department' => $hub,
            'allocations' => $allocations,
            'status' => $status,
            'statusLabels' => UnitAllocation::statusLabels(),
        ]);
    }

    public function approve(Request $request, Department $department, UnitAllocation $unitAllocation): RedirectResponse
    {
        $hub = $this->authorizeApprover($request, $department);
        $this->authorizeAllocation($request, $hub, $unitAllocation);

        $this->allocations->approve($unitAllocation, $request->user());

        return redirect()
            ->route('departments.academics.unit-allocations.index', array_filter([
                'learning_department' => $request->integer('learning_department') ?: null,
                'status' => (string) $request->query('status', UnitAllocation::STATUS_PENDING),
            ]))
            ->with('status', 'Allocation approved. The lecturer now sees the unit.');
    }

    public function reject(Request $request, Department $department, UnitAllocation $unitAllocation): RedirectResponse
    {
        $hub = $this->authorizeApprover($request, $department);
        $this->authorizeAllocation($request, $hub, $unitAllocation);

        $validated = $request->validate([
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        $this->allocations->reject($unitAllocation, $request->user(), $validated['comments'] ?? null);

        return redirect()
            ->route('departments.academics.unit-allocations.index', array_filter([
                'learning_department' => $request->integer('learning_department') ?: null,
                'status' => (string) $request->query('status', UnitAllocation::STATUS_PENDING),
            ]))
            ->with('status', 'Allocation rejected and sent back to the department.');
    }

    /**
     * Approving a lecturer is registry work: an HOD cannot sign off their own request.
     */
    protected function authorizeApprover(Request $request, Department $department): Department
    {
        $hub = $this->authorizeHub($request, $department);

        abort_unless(
            $this->access->canApproveRegistry($request->user()),
            403,
            'Only the Academic Registrar, Head of Academics or CEO can approve a unit allocation.'
        );

        return $hub;
    }

    private function authorizeAllocation(Request $request, Department $hub, UnitAllocation $unitAllocation): void
    {
        $scopeIds = $this->access->accessibleLearningDepartmentIds($request->user(), $hub);

        abort_unless(
            in_array((int) $unitAllocation->unit?->department_id, $scopeIds, true),
            403,
            'That allocation belongs to another department.'
        );
    }
}
