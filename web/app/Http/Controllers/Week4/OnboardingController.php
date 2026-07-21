<?php

namespace App\Http\Controllers\Week4;

use App\Http\Controllers\Controller;
use App\Services\AdmissionsReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(protected AdmissionsReviewService $reviewService) {}

    public function showDashboard(Request $request): View
    {
        $user = $request->user();

        return view('week4.dashboard', [
            'stats' => $this->reviewService->dashboardStats($user),
            'departmentBreakdown' => $this->reviewService->departmentBreakdown($user),
            'departments' => $this->reviewService->filterDepartmentsForUser($user),
            'canAccessAll' => $this->reviewService->canAccessAllDepartments($user),
            'recentApplications' => $this->reviewService->listApplications($user)->take(8),
        ]);
    }

    public function listApplications(Request $request): View
    {
        $user = $request->user();
        $departmentId = $request->integer('department') ?: null;
        $status = $request->string('status')->toString() ?: null;

        return view('week4.applications.index', [
            'applications' => $this->reviewService->listApplications($user, $departmentId, $status),
            'departments' => $this->reviewService->filterDepartmentsForUser($user),
            'filters' => [
                'department' => $departmentId,
                'status' => $status,
            ],
            'canAccessAll' => $this->reviewService->canAccessAllDepartments($user),
        ]);
    }

    public function reviewApplication(Request $request, int $id): View
    {
        $applicant = $this->reviewService->findForReview($request->user(), $id);

        return view('week4.applications.show', [
            'applicant' => $applicant,
            'handlingDepartment' => $this->reviewService->handlingDepartmentName($applicant),
            'canApprove' => $request->user()->hasPermission('admissions.approve') || $request->user()->hasRole('Super Admin'),
        ]);
    }

    public function shortlistApplication(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $applicant = $this->reviewService->findForReview($request->user(), $id);
        $this->reviewService->shortlist($request->user(), $applicant, $validated['review_notes'] ?? null);

        return redirect()
            ->route('week4.application.review', $id)
            ->with('status', 'Application shortlisted for final approval.');
    }

    public function approveApplication(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $applicant = $this->reviewService->findForReview($request->user(), $id);
        $this->reviewService->approve($request->user(), $applicant, $validated['review_notes'] ?? null);

        return redirect()
            ->route('week4.application.review', $id)
            ->with('status', 'Application accepted. The applicant has been marked as admitted.');
    }

    public function rejectApplication(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $applicant = $this->reviewService->findForReview($request->user(), $id);
        $this->reviewService->reject(
            $request->user(),
            $applicant,
            $validated['rejection_reason'],
            $validated['review_notes'] ?? null
        );

        return redirect()
            ->route('week4.application.review', $id)
            ->with('status', 'Application rejected.');
    }
}
