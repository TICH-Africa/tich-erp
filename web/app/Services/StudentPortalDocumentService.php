<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use App\Models\Student;
use App\Models\StudentApplicationDocumentSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentPortalDocumentService
{
    public function __construct(
        protected StoredFileService $files,
    ) {}

    /**
     * @return array<string, string>
     */
    public function uploadTypeLabels(): array
    {
        return config('tich-application.document_types', []);
    }

    public function applicantForStudent(Student $student): ?\App\Models\Applicant
    {
        $student->loadMissing('applicant');

        return $student->applicant;
    }

    /**
     * @return Collection<int, ApplicationDocument>
     */
    public function onFileDocuments(Student $student): Collection
    {
        $applicant = $this->applicantForStudent($student);
        if (! $applicant) {
            return collect();
        }

        return $applicant->documents()->orderBy('document_type')->orderByDesc('id')->get();
    }

    /**
     * @return Collection<int, StudentApplicationDocumentSubmission>
     */
    public function pendingChangeSubmissions(Student $student): Collection
    {
        if (! Schema::hasTable('student_application_document_submissions')) {
            return collect();
        }

        return StudentApplicationDocumentSubmission::query()
            ->where('student_id', $student->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function serializeDocumentsForPortal(Student $student): array
    {
        return $this->onFileDocuments($student)
            ->map(fn (ApplicationDocument $doc) => $this->serializeDocument($doc))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function serializePendingChanges(Student $student): array
    {
        return $this->pendingChangeSubmissions($student)
            ->map(fn (StudentApplicationDocumentSubmission $row) => [
                'id' => $row->id,
                'document_type' => $row->document_type,
                'document_type_label' => $row->typeLabel(),
                'original_filename' => $row->original_filename,
                'status' => $row->status,
                'student_notes' => $row->student_notes,
                'replaces_document_id' => $row->application_document_id,
                'submitted_at' => optional($row->created_at)?->toIso8601String(),
                'can_view' => true,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeDocument(ApplicationDocument $doc): array
    {
        return [
            'id' => $doc->id,
            'document_type' => $doc->document_type,
            'document_type_label' => $doc->displayLabel(),
            'document_name' => $doc->original_filename,
            'original_filename' => $doc->original_filename,
            'mime_type' => $doc->mime_type,
            'is_verified' => (bool) $doc->is_verified,
            'status' => (bool) $doc->is_verified ? 'verified' : 'on_file',
            'uploaded_at' => optional($doc->created_at)?->toIso8601String(),
            'can_view' => Storage::disk('local')->exists($doc->file_path),
            'is_previewable' => $doc->isPreviewable(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{message: string, kind: string, document?: array<string, mixed>, submission?: array<string, mixed>}
     */
    public function storeUpload(Student $student, User $user, UploadedFile $file, array $input): array
    {
        $applicant = $this->applicantForStudent($student);
        abort_unless($applicant, 422, 'Your student record is not linked to an application profile. Contact the admissions office.');

        $types = array_keys($this->uploadTypeLabels());
        abort_if($types === [], 422, 'Document uploads are not configured.');

        $validated = Validator::make($input, [
            'document_type' => 'required|in:'.implode(',', $types),
            'student_notes' => 'nullable|string|max:2000',
        ])->validate();

        $documentType = (string) $validated['document_type'];
        $this->validateUploadFile($file, $documentType);

        $existing = ApplicationDocument::query()
            ->where('applicant_id', $applicant->id)
            ->where('document_type', $documentType)
            ->orderByDesc('id')
            ->first();

        $safeName = time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $path = $this->files->store($file, "applications/{$applicant->id}/portal", 'local', $safeName);
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        if ($existing) {
            abort_unless(
                Schema::hasTable('student_application_document_submissions'),
                422,
                'Document change requests are not available yet. Contact the admissions office.'
            );

            $pending = StudentApplicationDocumentSubmission::query()
                ->where('student_id', $student->id)
                ->where('document_type', $documentType)
                ->where('status', 'pending')
                ->exists();

            if ($pending) {
                throw ValidationException::withMessages([
                    'document_type' => 'You already have a pending change for this document type. Wait for review or contact the office.',
                ]);
            }

            $submission = StudentApplicationDocumentSubmission::query()->create([
                'student_id' => $student->id,
                'applicant_id' => $applicant->id,
                'application_document_id' => $existing->id,
                'document_type' => $documentType,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'status' => 'pending',
                'student_notes' => $validated['student_notes'] ?? null,
                'uploaded_by_user_id' => $user->id,
            ]);

            return [
                'message' => 'Change submitted for approval. Your current file stays on record until the registrar approves the replacement.',
                'kind' => 'change_request',
                'submission' => [
                    'id' => $submission->id,
                    'document_type' => $submission->document_type,
                    'status' => $submission->status,
                ],
            ];
        }

        $document = ApplicationDocument::query()->create([
            'applicant_id' => $applicant->id,
            'document_type' => $documentType,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'is_verified' => 0,
            'created_by' => $user->id,
        ]);

        return [
            'message' => 'Document uploaded and saved to your file.',
            'kind' => 'upload',
            'document' => $this->serializeDocument($document),
        ];
    }

    public function resolveDocumentForStudent(Student $student, ApplicationDocument $document): ApplicationDocument
    {
        $applicant = $this->applicantForStudent($student);
        abort_unless($applicant, 404);
        abort_unless((int) $document->applicant_id === (int) $applicant->id, 403);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return $document;
    }

    public function resolveSubmissionForStudent(
        Student $student,
        StudentApplicationDocumentSubmission $submission,
    ): StudentApplicationDocumentSubmission {
        abort_unless((int) $submission->student_id === (int) $student->id, 403);
        abort_unless(Storage::disk('local')->exists($submission->file_path), 404);

        return $submission;
    }

    public function inlineDocumentResponse(ApplicationDocument $document): StreamedResponse
    {
        return Storage::disk('local')->response(
            $document->file_path,
            $document->safeFilename(),
            [
                'Content-Type' => $document->mime_type,
                'Content-Disposition' => 'inline; filename="'.$document->safeFilename().'"',
            ]
        );
    }

    public function downloadDocumentResponse(ApplicationDocument $document): StreamedResponse
    {
        return Storage::disk('local')->download(
            $document->file_path,
            $document->safeFilename(),
            ['Content-Type' => $document->mime_type]
        );
    }

    public function inlineSubmissionResponse(StudentApplicationDocumentSubmission $submission): StreamedResponse
    {
        return Storage::disk('local')->response(
            $submission->file_path,
            $submission->safeFilename(),
            [
                'Content-Type' => $submission->mime_type,
                'Content-Disposition' => 'inline; filename="'.$submission->safeFilename().'"',
            ]
        );
    }

    public function downloadSubmissionResponse(StudentApplicationDocumentSubmission $submission): StreamedResponse
    {
        return Storage::disk('local')->download(
            $submission->file_path,
            $submission->safeFilename(),
            ['Content-Type' => $submission->mime_type]
        );
    }

    public function approveSubmission(StudentApplicationDocumentSubmission $submission, User $reviewer, ?string $notes = null): void
    {
        abort_unless($submission->isPending(), 422);

        DB::transaction(function () use ($submission, $reviewer, $notes) {
            $document = $submission->applicationDocument;
            abort_unless($document, 422, 'The original document is missing.');

            $oldPath = $document->file_path;
            $document->update([
                'file_path' => $submission->file_path,
                'original_filename' => $submission->original_filename,
                'mime_type' => $submission->mime_type,
                'is_verified' => 0,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            $submission->update([
                'status' => 'approved',
                'reviewer_notes' => $notes,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            if ($oldPath && $oldPath !== $submission->file_path && Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
        });
    }

    public function rejectSubmission(StudentApplicationDocumentSubmission $submission, User $reviewer, string $notes): void
    {
        abort_unless($submission->isPending(), 422);

        DB::transaction(function () use ($submission, $reviewer, $notes) {
            $path = $submission->file_path;
            $submission->update([
                'status' => 'rejected',
                'reviewer_notes' => $notes,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            if ($path && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        });
    }

    private function validateUploadFile(UploadedFile $file, string $documentType): void
    {
        $rules = config('tich-application.document_upload_rules', []);
        $rule = $rules[$documentType] ?? $rules['default'] ?? [
            'mimes' => 'pdf,jpg,jpeg,png',
            'max_kb' => 5120,
        ];

        Validator::make(
            ['file' => $file],
            [
                'file' => [
                    'required',
                    'file',
                    'mimes:'.($rule['mimes'] ?? 'pdf,jpg,jpeg,png'),
                    'max:'.(int) ($rule['max_kb'] ?? 5120),
                ],
            ],
            [],
            ['file' => 'document file']
        )->validate();
    }
}
