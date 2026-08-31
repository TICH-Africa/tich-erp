<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Services\AdmissionsReviewService;
use App\Services\ApplicationMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(
        protected AdmissionsReviewService $reviewService,
        protected ApplicationMailService $mailService,
    ) {}

    public function show(Request $request, int $id): View
    {
        $applicant = $this->reviewService->findForAdministration($id);

        return view('administration.applications.show', [
            'applicant' => $applicant,
            'handlingDepartment' => $this->reviewService->handlingDepartmentName($applicant),
            'approvalPackageEmail' => $applicant->academic_review_status === 'approved'
                ? $this->mailService->approvalPackageEmailStatus($applicant)
                : null,
            'documentRoutePrefix' => 'administration.applications',
        ]);
    }

    public function handoffToAcademics(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $applicant = $this->reviewService->findForAdministration($id);
        $this->reviewService->handoffFromAdministration(
            $request->user(),
            $applicant,
            $validated['review_notes'] ?? null
        );

        return redirect()
            ->route('administration.applications.show', $id)
            ->with('status', 'Application forwarded to academics for review.');
    }

    public function resendApprovalPackage(Request $request, int $id): RedirectResponse
    {
        $applicant = $this->reviewService->findForAdministration($id);
        $result = $this->mailService->resendApprovalPackageEmail($applicant, $request);

        if ($result['sent']) {
            return redirect()
                ->route('administration.applications.show', $id)
                ->with('status', 'Approval package email sent to '.$applicant->email.'.');
        }

        return redirect()
            ->route('administration.applications.show', $id)
            ->with('application_mail_error', $result['error'] ?? 'Unable to send approval package email.');
    }
}
