<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\UnitAllocation;
use App\Services\HodUnitAllocationService;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffUnitAllocationController extends Controller
{
    public function __construct(
        protected StaffPortalService $portalService,
        protected HodUnitAllocationService $desk,
    ) {}

    public function mapUnit(Request $request): RedirectResponse
    {
        $staff = $this->hod($request);

        $request->validate([
            'program' => ['required', 'integer'],
            'intake' => ['required', 'integer'],
            'unit_id' => ['required', 'integer'],
            'period' => ['nullable', 'integer', 'min:1'],
        ]);

        $intake = $this->desk->mapUnit($request->user(), $staff, $request);

        return $this->back($request, $intake->program_id, $intake->id)
            ->with('status', 'Unit added to the programme. Assign a lecturer when you are ready.');
    }

    public function store(Request $request): RedirectResponse
    {
        $staff = $this->hod($request);

        $request->validate([
            'program' => ['required', 'integer'],
            'intake' => ['required', 'integer'],
            'unit_id' => ['required', 'integer'],
            'staff_id' => ['required', 'integer'],
            'campus_id' => ['required', 'integer'],
            'period' => ['nullable', 'integer', 'min:1'],
            'contact_hours_assigned' => ['nullable', 'integer', 'min:0'],
        ]);

        $allocation = $this->desk->requestAllocation($request->user(), $staff, $request);

        return $this->back($request, $allocation->program_id, null)
            ->with('status', $allocation->awaitingApproval()
                ? 'Allocation sent to Academics for approval. The lecturer sees the unit once it is approved.'
                : 'This allocation is already awaiting Academics.');
    }

    public function withdraw(Request $request, UnitAllocation $unitAllocation): RedirectResponse
    {
        $staff = $this->hod($request);

        $this->desk->withdrawAllocation($request->user(), $staff, $unitAllocation);

        return $this->back($request, $unitAllocation->program_id, null)
            ->with('status', 'Allocation request withdrawn.');
    }

    private function hod(Request $request): Staff
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_if(! $staff, 404);
        abort_unless($request->user()->hasAnyRole(['HOD', 'Super Admin']), 403, 'Only a department HOD can allocate units.');

        return $staff;
    }

    private function back(Request $request, ?int $programId, ?int $intakeId): RedirectResponse
    {
        return redirect()->route('staff.dashboard', array_filter([
            'section' => 'hod-unit-allocations',
            'program' => $programId ?: $request->integer('program') ?: null,
            'intake' => $intakeId ?: $request->integer('intake') ?: null,
        ]));
    }
}
