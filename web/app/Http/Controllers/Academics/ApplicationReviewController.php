<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Services\AdmissionsReviewService;
use App\Services\AcademicsAccessService;
use App\Services\DepartmentDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationReviewController extends DepartmentAcademicsController
{
    public function __construct(
        protected AdmissionsReviewService $reviewService,
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        $departmentId = $request->integer('learning_department') ?: $request->integer('department') ?: null;
        $status = $request->string('status')->toString() ?: 'pending';
        $programId = $request->integer('program') ?: null;

        return view('academics.applications.index', [
            'department' => $hub,
            'learningDepartment' => $departmentId ? Department::query()->find($departmentId) : null,
            'applications' => $this->reviewService->listApplications($request->user(), $departmentId, $status, $programId),
            'departments' => $this->reviewService->filterDepartmentsForUser($request->user()),
            'filters' => [
                'department' => $departmentId,
                'status' => $status,
                'program' => $programId,
            ],
            'canAccessAll' => $this->reviewService->canAccessAllDepartments($request->user()),
            'canApprove' => $request->user()->hasPermission('academics.approve') || $request->user()->hasRole('Super Admin'),
        ]);
    }

    public function show(Request $request, Department $department, int $id): View
    {
        $hub = $this->authorizeHub($request, $department);
        $applicant = $this->reviewService->findForReview($request->user(), $id);

        return view('academics.applications.show', [
            'department' => $hub,
            'learningDepartment' => $request->integer('learning_department')
                ? Department::query()->find($request->integer('learning_department'))
                : null,
            'applicant' => $applicant,
            'handlingDepartment' => $this->reviewService->handlingDepartmentName($applicant),
            'canApprove' => $request->user()->hasPermission('academics.approve') || $request->user()->hasRole('Super Admin'),
            'documentRoutePrefix' => 'departments.academics.applications',
        ]);
    }

    public function approve(Request $request, Department $department, int $id): RedirectResponse
    {
        $this->authorizeHub($request, $department);

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $applicant = $this->reviewService->findForReview($request->user(), $id);
        $this->reviewService->approveAcademically($request->user(), $applicant, $validated['review_notes'] ?? null);

        return redirect()
            ->route('departments.academics.applications.show', ['id' => $id])
            ->with('status', 'Application academically approved. The applicant has been notified to pay the application fee.');
    }

    public function reject(Request $request, Department $department, int $id): RedirectResponse
    {
        $this->authorizeHub($request, $department);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $applicant = $this->reviewService->findForReview($request->user(), $id);
        $this->reviewService->rejectAcademically(
            $request->user(),
            $applicant,
            $validated['rejection_reason'],
            $validated['review_notes'] ?? null
        );

        return redirect()
            ->route('departments.academics.applications.show', ['id' => $id])
            ->with('status', 'Application rejected. The applicant has been notified.');
    }
}
