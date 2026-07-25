<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Semester;
use App\Models\Staff;
use App\Models\UnitAllocation;
use App\Services\AcademicsAccessService;
use App\Services\DepartmentDashboardService;
use App\Services\UnitAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkloadController extends DepartmentAcademicsController
{
    public function __construct(
        protected UnitAllocationService $allocations,
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        $learningDepartmentId = $request->integer('learning_department') ?: null;
        $semesterId = $request->integer('semester') ?: null;

        if ($learningDepartmentId) {
            abort_unless(in_array($learningDepartmentId, $hub->academicsScopeDepartmentIds(), true), 404);
        }

        $learningDepartment = $learningDepartmentId
            ? Department::query()->find($learningDepartmentId)
            : null;

        $departmentId = $learningDepartmentId ?: (int) ($hub->academicsScopeDepartmentIds()[0] ?? $hub->id);

        return view('academics.workload.index', [
            'department' => $hub,
            'learningDepartment' => $learningDepartment,
            'learningDepartments' => $this->access->learningDepartmentsInScope($request->user(), $hub),
            'allocations' => $this->allocations->forDepartment($departmentId, $semesterId),
            'workloadSummary' => $this->allocations->workloadSummary($departmentId, $semesterId),
            'semesters' => Semester::query()->with('academicYear')->orderByDesc('id')->get(),
            'selectedSemesterId' => $semesterId,
            'staffList' => Staff::query()->where('is_teaching_staff', 1)->where('employment_status', 'active')->orderBy('surname')->get(),
            'units' => $this->access->unitsInScope($hub, $departmentId)->where('status', 'active')->values(),
            'campuses' => Campus::query()->where('is_active', 1)->orderBy('campus_name')->get(),
        ]);
    }

    public function store(Request $request, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);

        $validated = $request->validate([
            'unit_id' => ['required', 'integer'],
            'staff_id' => ['required', 'integer'],
            'semester_id' => ['required', 'integer'],
            'campus_id' => ['required', 'integer'],
            'contact_hours_assigned' => ['nullable', 'integer', 'min:0'],
            'is_coordinator' => ['nullable', 'boolean'],
            'learning_department' => ['nullable', 'integer'],
        ]);

        $this->allocations->assign($validated);

        return redirect()
            ->route('departments.academics.workload.index', array_filter([
                'department' => $hub->id,
                'learning_department' => $validated['learning_department'] ?? null,
                'semester' => $validated['semester_id'],
            ]))
            ->with('status', 'Lecturer assigned to unit.');
    }

    public function destroy(Request $request, Department $department, UnitAllocation $allocation): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);

        $this->allocations->remove($allocation);

        return back()->with('status', 'Allocation removed.');
    }
}
