<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Models\ApplicationDocument;
use App\Models\StudentApplicationDocumentSubmission;
use App\Models\StudentDocumentRequest;
use App\Services\StudentPortalDocumentService;
use App\Services\StudentRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentsController extends Controller
{
    use ResolvesPortalStudent;
    use SerializesStudentPortal;

    public function __construct(
        protected StudentRecordService $studentRecords,
        protected StudentPortalDocumentService $portalDocuments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $biodata = $this->studentRecords->biodata360($student);

        $documents = $this->portalDocuments->serializeDocumentsForPortal($student);
        $pendingChanges = $this->portalDocuments->serializePendingChanges($student);

        $requests = [];
        if (Schema::hasTable('student_document_requests')) {
            $requests = StudentDocumentRequest::query()
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'document_type' => $r->document_type,
                    'document_type_label' => StudentDocumentRequest::TYPES[$r->document_type] ?? $r->document_type,
                    'status' => $r->status,
                    'student_notes' => $r->student_notes,
                    'created_at' => optional($r->created_at)?->toIso8601String(),
                ])
                ->all();
        }

        return response()->json([
            'student' => $this->serializeStudent($student, $biodata),
            'documents' => $documents,
            'pending_changes' => $pendingChanges,
            'document_requests' => $requests,
            'request_types' => StudentDocumentRequest::TYPES,
            'upload_types' => $this->portalDocuments->uploadTypeLabels(),
        ]);
    }

    public function storeUpload(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $request->validate(['file' => 'required|file']);

        $result = $this->portalDocuments->storeUpload(
            $student,
            $request->user(),
            $request->file('file'),
            $request->only(['document_type', 'student_notes']),
        );

        return response()->json($result, $result['kind'] === 'upload' ? 201 : 202);
    }

    public function storeRequest(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);

        $validated = $request->validate([
            'document_type' => 'required|in:'.implode(',', array_keys(StudentDocumentRequest::TYPES)),
            'student_notes' => 'nullable|string|max:2000',
        ]);

        $row = StudentDocumentRequest::query()->create([
            'student_id' => $student->id,
            'requested_by_user_id' => $request->user()->id,
            'document_type' => $validated['document_type'],
            'status' => 'pending',
            'student_notes' => $validated['student_notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Document request submitted.',
            'request' => [
                'id' => $row->id,
                'document_type' => $row->document_type,
                'status' => $row->status,
            ],
        ], 201);
    }

    public function file(Request $request, ApplicationDocument $document): StreamedResponse
    {
        $student = $this->portalStudent($request);
        $document = $this->portalDocuments->resolveDocumentForStudent($student, $document);

        if ($request->query('disposition') === 'download') {
            return $this->portalDocuments->downloadDocumentResponse($document);
        }

        return $this->portalDocuments->inlineDocumentResponse($document);
    }

    public function submissionFile(Request $request, StudentApplicationDocumentSubmission $submission): StreamedResponse
    {
        $student = $this->portalStudent($request);
        $submission = $this->portalDocuments->resolveSubmissionForStudent($student, $submission);

        if ($request->query('disposition') === 'download') {
            return $this->portalDocuments->downloadSubmissionResponse($submission);
        }

        return $this->portalDocuments->inlineSubmissionResponse($submission);
    }
}
