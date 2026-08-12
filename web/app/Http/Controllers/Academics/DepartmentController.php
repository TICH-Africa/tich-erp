<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Services\AcademicsAccessService;
use App\Services\DepartmentDashboardService;
use App\Services\ProgramCurriculumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends DepartmentAcademicsController
{
    public function __construct(
        protected ProgramCurriculumService $curriculum,
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);

        return view('academics.departments.index', [
            'department' => $hub,
            'departments' => $this->access->learningDepartmentsInScope($request->user(), $hub),
            'profiles' => ProgramCurriculumService::curriculumProfiles(),
            'canApproveCeo' => $this->access->canApproveCeo($request->user()),
        ]);
    }

    public function updateProfile(Request $request, Department $department, Department $learningDepartment): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless(in_array($learningDepartment->id, $hub->academicsScopeDepartmentIds(), true), 404);
        abort_unless($learningDepartment->isValidLearningDepartment(), 404);

        $validated = $request->validate([
            'curriculum_profile' => ['required', 'in:'.implode(',', config('tich-academics.curriculum_profiles'))],
        ]);

        $this->curriculum->updateDepartmentProfile($request->user(), $learningDepartment, $validated, $request);

        return back()->with('status', 'Department curriculum profile updated.');
    }
}
