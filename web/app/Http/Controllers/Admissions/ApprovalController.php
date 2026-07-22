<?php

namespace App\Http\Controllers\Admissions;

use App\Http\Controllers\Controller;
use App\Services\AdmissionsReviewService;
use App\Services\ApplicationMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct(
        protected AdmissionsReviewService $reviewService,
        protected ApplicationMailService $mailService,
    ) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        return view('admissions.dashboard', [
            'stats' => $this->reviewService->dashboardStats($user),
            'departmentBreakdown' => $this->reviewService->departmentBreakdown($user),
            'departments' => $this->reviewService->filterDepartmentsForUser($user),
            'canAccessAll' => $this->reviewService->canAccessAllDepartments($user),
            'recentApplications' => $this->reviewService->listApplications($user)->take(8),
        ]);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $departmentId = $request->integer('department') ?: null;
        $status = $request->string('status')->toString() ?: null;

        return view('admissions.applications.index', [
            'applications' => $this->reviewService->listApplications($user, $departmentId, $status),
            'departments' => $this->reviewService->filterDepartmentsForUser($user),
            'filters' => [
                'department' => $departmentId,
                'status' => $status,
            ],
            'canAccessAll' => $this->reviewService->canAccessAllDepartments($user),
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $applicant = $this->reviewService->findForReview($request->user(), $id);

        return view('admissions.applications.show', [
            'applicant' => $applicant,
            'handlingDepartment' => $this->reviewService->handlingDepartmentName($applicant),
            'canApprove' => $request->user()->hasPermission('admissions.approve') || $request->user()->hasRole('Super Admin'),
            'portalSignupEmail' => $applicant->status === 'admitted'
                ? $this->mailService->portalSignupEmailStatus($applicant)
                : null,
        ]);
    }

    public function resendPortalSignup(Request $request, int $id): RedirectResponse
    {
        $applicant = $this->reviewService->findForReview($request->user(), $id);
        $result = $this->mailService->resendPortalSignupEmail($applicant, $request);

        if ($result['sent']) {
            return redirect()
                ->route('admissions.applications.show', $id)
                ->with('status', 'Student portal signup email sent to '.$applicant->email.'.');
        }

        return redirect()
            ->route('admissions.applications.show', $id)
            ->with('application_mail_error', $result['error'] ?? 'Unable to send student portal signup email.');
    }

    public function shortlist(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $applicant = $this->reviewService->findForReview($request->user(), $id);
        $this->reviewService->shortlist($request->user(), $applicant, $validated['review_notes'] ?? null);

        return redirect()
            ->route('admissions.applications.show', $id)
            ->with('status', 'Application shortlisted. The applicant has been emailed about the admission fee requirement.');
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $applicant = $this->reviewService->findForReview($request->user(), $id);
        $this->reviewService->approve($request->user(), $applicant, $validated['review_notes'] ?? null);

        return redirect()
            ->route('admissions.applications.show', $id)
            ->with('status', 'Application accepted. The applicant has been marked as admitted.');
    }

    public function reject(Request $request, int $id): RedirectResponse
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
            ->route('admissions.applications.show', $id)
            ->with('status', 'Application rejected.');
    }
}
