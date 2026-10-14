<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Models\StudentDocumentRequest;
use App\Services\StudentRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DocumentsController extends Controller
{
    use ResolvesPortalStudent;
    use SerializesStudentPortal;

    public function __construct(
        protected StudentRecordService $studentRecords,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $biodata = $this->studentRecords->biodata360($student);
        $documents = collect($biodata['documents'] ?? [])->map(function ($doc) {
            if (is_array($doc)) {
                return $doc;
            }

            return [
                'id' => $doc->id ?? null,
                'document_type' => $doc->document_type ?? $doc->type ?? null,
                'document_name' => $doc->document_name ?? $doc->original_name ?? $doc->file_name ?? null,
                'status' => $doc->status ?? null,
                'uploaded_at' => optional($doc->created_at ?? null)?->toIso8601String(),
            ];
        })->values()->all();

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
            'document_requests' => $requests,
            'request_types' => StudentDocumentRequest::TYPES,
        ]);
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
}
