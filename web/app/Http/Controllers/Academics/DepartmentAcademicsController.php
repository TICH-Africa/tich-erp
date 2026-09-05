<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\AcademicsAccessService;
use App\Services\DepartmentDashboardService;
use Illuminate\Http\Request;

abstract class DepartmentAcademicsController extends Controller
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected DepartmentDashboardService $departmentDashboard,
    ) {}

    protected function authorizeHub(Request $request, Department $department, bool $allowSuggestionsOnly = false): Department
    {
        if (! $department->is_active) {
            abort(404);
        }

        abort_unless(
            $this->departmentDashboard->userCanAccessDepartment($request->user(), $department),
            403,
            'You do not have access to this department.'
        );

        if ($department->isLearningDepartment()) {
            $department = Department::query()->find($department->parent_dept_id) ?? abort(404);
        }

        abort_unless($department->isAcademicsHub(), 404);

        if ($this->access->isSuggestionsOnly($request->user()) && ! $allowSuggestionsOnly) {
            abort(403, 'Dean of Students access in Academics is limited to the suggestion box and deferment requests.');
        }

        if ($this->access->isTeachingOnly($request->user())) {
            abort(403, 'Teaching staff use the Staff portal for academics work.');
        }

        return $department;
    }
}
