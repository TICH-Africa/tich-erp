<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\DepartmentDashboardService;
use App\Services\ProgramCurriculumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DepartmentDashboardController extends Controller
{
    public function show(Request $request, Department $department, DepartmentDashboardService $departmentDashboard): View|RedirectResponse
    {
        if (! $department->is_active) {
            throw new NotFoundHttpException();
        }

        $user = $request->user();

        if (! $departmentDashboard->userCanAccessDepartment($user, $department)) {
            abort(403, 'You do not have access to this department.');
        }

        $requestedKey = (string) $request->route()->originalParameter('department');

        if ($requestedKey !== $department->getRouteKey()) {
            return redirect()->route('departments.show', array_filter([
                'department' => $department->getRouteKey(),
                'section' => $request->query('section'),
            ]));
        }

        $section = $departmentDashboard->resolveSection($request, $user, $department);
        $department->load(['group', 'campus', 'parent']);

        return view('departments.show', [
            'department' => $department,
            'academicsHub' => $department->isLearningDepartment() ? $department->academicsHub() : null,
            'section' => $section,
            'childDepartments' => $departmentDashboard->accessibleChildDepartments($user, $department),
            'modules' => $departmentDashboard->modulesForDepartment($user, $department),
            'sidebarNavigation' => $departmentDashboard->sidebarNavigation($user, $department),
            'dashboardViewType' => $departmentDashboard->dashboardViewType($user, $department),
            'overviewStats' => $departmentDashboard->overviewStats($user, $department),
            'programs' => $department->isLearningDepartment()
                ? $departmentDashboard->programsForDepartment($department)
                : collect(),
            'curriculumFormats' => ProgramCurriculumService::curriculumFormats(),
            'categoryLabel' => fn (Department $dept) => $departmentDashboard->categoryLabel($dept),
            'cardDescription' => fn (Department $dept) => $departmentDashboard->cardDescription(
                $dept->loadCount(['children' => fn ($query) => $query->where('is_active', true)])
            ),
            'entryUrl' => fn (Department $dept) => $departmentDashboard->entryUrlForDepartment($user, $dept),
        ]);
    }
}
