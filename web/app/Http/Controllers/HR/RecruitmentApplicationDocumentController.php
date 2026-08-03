<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentApplication;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruitmentApplicationDocumentController extends Controller
{
    public function show(RecruitmentApplication $application, string $documentKey): StreamedResponse
    {
        $document = $this->resolveDocument($application, $documentKey);

        abort_unless(Storage::disk('public')->exists($document['file_path']), 404);

        return Storage::disk('public')->response(
            $document['file_path'],
            $document['filename'],
            [
                'Content-Type' => $document['mime_type'],
                'Content-Disposition' => 'inline; filename="'.$this->safeFilename($document['filename']).'"',
            ]
        );
    }

    public function download(RecruitmentApplication $application, string $documentKey): StreamedResponse
    {
        $document = $this->resolveDocument($application, $documentKey);

        abort_unless(Storage::disk('public')->exists($document['file_path']), 404);

        return Storage::disk('public')->download(
            $document['file_path'],
            $this->safeFilename($document['filename']),
            ['Content-Type' => $document['mime_type']]
        );
    }

    public function viewer(RecruitmentApplication $application, string $documentKey): View
    {
        $document = $this->resolveDocument($application, $documentKey);

        abort_unless(Storage::disk('public')->exists($document['file_path']), 404);

        return view('hr.recruitment.document-external', [
            'application' => $application,
            'document' => $document,
        ]);
    }

    /**
     * @return array{key: string, label: string, filename: string, file_path: string, mime_type: string, is_previewable: bool}
     */
    private function resolveDocument(RecruitmentApplication $application, string $documentKey): array
    {
        $document = $application->findUploadedDocument($documentKey);

        abort_unless($document !== null, 404);

        return $document;
    }

    private function safeFilename(string $filename): string
    {
        return preg_replace('/[^\w.\-() ]+/u', '_', $filename) ?: 'document';
    }
}
