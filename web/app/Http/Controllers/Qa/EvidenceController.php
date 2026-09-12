<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Qa\QaDepartmentSubmission;
use App\Models\Qa\QaEvidenceAttachment;
use App\Services\Qa\QaAssessmentService;
use App\Services\StoredFileService;
use App\Support\SupportingDocumentResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function __construct(
        protected QaAssessmentService $qa,
        protected StoredFileService $files,
    ) {}

    public function viewer(Request $request, QaEvidenceAttachment $attachment): View
    {
        $this->authorizeAttachment($request, $attachment);
        $relative = $this->relativePath($attachment);
        abort_unless($relative && Storage::disk('public')->exists($relative), 404);

        $filename = $attachment->description ?: basename($relative);
        $mime = Storage::disk('public')->mimeType($relative) ?: 'application/octet-stream';

        return view('qa.evidence.viewer', [
            'attachment' => $attachment,
            'filename' => $filename,
            'mime' => $mime,
            'isPreviewable' => SupportingDocumentResponse::isPreviewable($mime, $filename),
            'backUrl' => url()->previous() !== url()->current() ? url()->previous() : route('qa.assessments.index'),
        ]);
    }

    public function file(Request $request, QaEvidenceAttachment $attachment): StreamedResponse
    {
        $this->authorizeAttachment($request, $attachment);
        $relative = $this->relativePath($attachment);
        abort_unless($relative && Storage::disk('public')->exists($relative), 404);

        $filename = $this->safeFilename($attachment->description ?: basename($relative));
        $mime = Storage::disk('public')->mimeType($relative) ?: 'application/octet-stream';

        return Storage::disk('public')->response(
            $relative,
            $filename,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    public function download(Request $request, QaEvidenceAttachment $attachment): StreamedResponse
    {
        $this->authorizeAttachment($request, $attachment);
        $relative = $this->relativePath($attachment);
        abort_unless($relative && Storage::disk('public')->exists($relative), 404);

        $filename = $this->safeFilename($attachment->description ?: basename($relative));
        $mime = Storage::disk('public')->mimeType($relative) ?: 'application/octet-stream';

        return Storage::disk('public')->download($relative, $filename, ['Content-Type' => $mime]);
    }

    private function authorizeAttachment(Request $request, QaEvidenceAttachment $attachment): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->hasPermission('qa.read')) {
            return;
        }

        if ($attachment->evidence_type === 'checklist_submission') {
            $submission = QaDepartmentSubmission::query()->find($attachment->linked_id);
            if ($submission) {
                $department = $submission->department;
                if ($department && $this->qa->userCanRespondForDepartment($user, $department)) {
                    return;
                }
            }
        }

        abort(403);
    }

    private function relativePath(QaEvidenceAttachment $attachment): ?string
    {
        return $this->files->relativePath($attachment->file_path);
    }

    private function safeFilename(string $filename): string
    {
        return preg_replace('/[^\w.\-() ]+/u', '_', $filename) ?: 'document';
    }
}
