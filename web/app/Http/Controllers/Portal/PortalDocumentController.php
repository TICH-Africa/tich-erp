<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Services\StudentPortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalDocumentController extends Controller
{
    public function __construct(protected StudentPortalService $portalService) {}

    public function show(Request $request, ApplicationDocument $document): StreamedResponse
    {
        $document = $this->resolveDocument($request, $document);

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

    public function download(Request $request, ApplicationDocument $document): StreamedResponse
    {
        $document = $this->resolveDocument($request, $document);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->safeFilename(),
            ['Content-Type' => $document->mime_type]
        );
    }

    private function resolveDocument(Request $request, ApplicationDocument $document): ApplicationDocument
    {
        $student = $this->portalService->studentForUser($request->user());

        abort_if(! $student || ! $student->application_id, 404);
        abort_unless((int) $document->applicant_id === (int) $student->application_id, 403);

        return $document;
    }
}
