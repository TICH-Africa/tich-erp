<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\StudentApplicationDocumentSubmission;
use App\Services\StudentPortalDocumentService;
use App\Services\StudentPortalService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalDocumentSubmissionController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentPortalDocumentService $documents,
    ) {}

    public function show(Request $request, StudentApplicationDocumentSubmission $submission): StreamedResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 404);

        $submission = $this->documents->resolveSubmissionForStudent($student, $submission);

        return $this->documents->inlineSubmissionResponse($submission);
    }
}
