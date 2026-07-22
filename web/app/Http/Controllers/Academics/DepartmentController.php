<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Services\AcademicsAccessService;
use App\Services\ProgramCurriculumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected ProgramCurriculumService $curriculum,
    ) {}

    public function index(Request $request): View
    {
        $departments = $this->access->learningDepartmentsForUser($request->user());

        return view('academics.departments.index', [
            'departments' => $departments,
            'profiles' => ProgramCurriculumService::curriculumProfiles(),
            'canInitialize' => $this->access->canAccessAll($request->user()),
            'canApproveCeo' => $this->access->canApproveCeo($request->user()),
            'campuses' => Campus::query()->where('is_active', 1)->orderBy('campus_name')->get(['id', 'campus_name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dept_code' => ['required', 'string', 'max:20', 'unique:departments,dept_code'],
            'dept_name' => ['required', 'string', 'max:200'],
            'curriculum_profile' => ['required', 'in:'.implode(',', config('tich-academics.curriculum_profiles'))],
            'campus_id' => ['nullable', 'exists:campuses,id'],
        ]);

        $this->curriculum->initializeDepartment($request->user(), $validated, $request);

        return back()->with('status', 'Department initialized. Pending CEO sign-off before activation.');
    }

    public function updateProfile(Request $request, Department $department): RedirectResponse
    {
        $this->access->findDepartmentForUser($request->user(), $department->id);

        $validated = $request->validate([
            'curriculum_profile' => ['required', 'in:'.implode(',', config('tich-academics.curriculum_profiles'))],
        ]);

        $this->curriculum->updateDepartmentProfile($request->user(), $department, $validated, $request);

        return back()->with('status', 'Department curriculum profile updated.');
    }

    public function approveCeo(Request $request, Department $department): RedirectResponse
    {
        $this->curriculum->approveDepartmentCeo($request->user(), $department, $request);

        return back()->with('status', 'Department activated.');
    }
}
