<?php

namespace App\Http\Controllers\Admissions;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Services\AdmissionsReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationDocumentController extends Controller
{
    public function __construct(protected AdmissionsReviewService $reviewService) {}

    public function show(Request $request, int $applicationId, int $documentId): StreamedResponse
    {
        $document = $this->resolveDocument($request, $applicationId, $documentId);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response(
            $document->file_path,
            $document->original_filename,
            [
                'Content-Type' => $document->mime_type,
                'Content-Disposition' => 'inline; filename="'.$document->safeFilename().'"',
            ]
        );
    }

    public function download(Request $request, int $applicationId, int $documentId): StreamedResponse
    {
        $document = $this->resolveDocument($request, $applicationId, $documentId);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->safeFilename(),
            ['Content-Type' => $document->mime_type]
        );
    }

    private function resolveDocument(Request $request, int $applicationId, int $documentId): ApplicationDocument
    {
        $applicant = $this->reviewService->findForReview($request->user(), $applicationId);

        return $applicant->documents()->findOrFail($documentId);
    }
}
