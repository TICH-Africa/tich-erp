<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\Semester;
use App\Services\DepartmentPerformanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceTerminalController extends DepartmentAcademicsController
{
    public function __construct(
        protected DepartmentPerformanceService $performance,
        \App\Services\AcademicsAccessService $access,
        \App\Services\DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasAnyRole(['HOD', 'Dean', 'Academic Registrar', 'Super Admin'])
            || $request->user()->hasPermission('academics.approve'), 403);

        $learningDepartmentId = $request->integer('learning_department') ?: null;
        $departmentId = $learningDepartmentId ?: (int) ($hub->academicsScopeDepartmentIds()[0] ?? $hub->id);
        $semesterId = $request->integer('semester') ?: null;

        return view('academics.performance.index', [
            'department' => $hub,
            'learningDepartment' => $learningDepartmentId ? Department::query()->find($learningDepartmentId) : null,
            'learningDepartments' => $this->access->learningDepartmentsInScope($request->user(), $hub),
            'semesters' => Semester::query()->with('academicYear')->orderByDesc('id')->get(),
            'selectedSemesterId' => $semesterId,
            'performance' => $this->performance->dashboard($departmentId, $semesterId),
        ]);
    }
}
