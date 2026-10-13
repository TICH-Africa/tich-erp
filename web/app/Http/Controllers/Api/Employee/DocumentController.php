<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\Concerns\ResolvesPortalStaff;
use App\Models\StaffDocument;
use App\Services\EmployeeProfileCompletenessService;
use App\Services\EmployeeProfileUpdatePromptService;
use App\Services\StaffLifecycleService;
use App\Services\StoredFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use ResolvesPortalStaff;

    private const DOCUMENT_TYPES = 'cv,academic_certificate,professional_license,kra_pin,nssf,sha,national_id,good_conduct,passport_photo,bank_confirmation,training_certification,other';

    public function __construct(
        protected StaffLifecycleService $lifecycleService,
        protected StoredFileService $files,
        protected EmployeeProfileUpdatePromptService $profilePrompts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $docs = $staff->documents()->orderByDesc('created_at')->get();
        $prompt = $this->profilePrompts->findActiveForStaff($staff);
        $requested = EmployeeProfileCompletenessService::requestedDocumentTypes($prompt?->requested_fields);

        return response()->json([
            'document_types' => EmployeeProfileCompletenessService::DOCUMENT_TYPE_LABELS,
            'requested_types' => $requested,
            'documents' => $docs->map(fn (StaffDocument $d) => [
                'id' => $d->id,
                'document_type' => $d->document_type,
                'document_name' => $d->document_name,
                'original_filename' => $d->original_filename,
                'issue_date' => optional($d->issue_date)?->format('Y-m-d'),
                'expiry_date' => optional($d->expiry_date)?->format('Y-m-d'),
                'is_verified' => (bool) $d->is_verified,
                'status' => $d->status,
                'notes' => $d->notes,
                'created_at' => optional($d->created_at)?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'document_type' => 'required|string|in:'.self::DOCUMENT_TYPES,
            'document_name' => 'required|string|max:300',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:10240',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $file = $validated['file'];
        $employeeNumber = trim((string) $staff->employee_number);
        abort_if($employeeNumber === '', 422, 'Employee number missing; cannot store document.');

        $safeName = time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $path = $this->files->store($file, "staff/{$employeeNumber}/documents", 'local', $safeName);

        $document = $this->lifecycleService->addDocument($staff->id, [
            'document_type' => $validated['document_type'],
            'document_name' => $validated['document_name'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'status' => 'pending',
            'issue_date' => $validated['issue_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], $request->user()->id);

        $this->profilePrompts->fulfillDocumentTypeForStaff($staff, $validated['document_type']);

        return response()->json([
            'message' => 'Document uploaded.',
            'document' => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'document_name' => $document->document_name,
            ],
        ], 201);
    }

    public function download(Request $request, int $document): StreamedResponse
    {
        $staff = $this->portalStaff($request);
        $doc = StaffDocument::where('staff_id', $staff->id)->findOrFail($document);
        $path = $doc->file_path ? ltrim(str_replace('\\', '/', $doc->file_path), '/') : null;
        abort_unless($path, 404, 'Document file missing.');

        $disk = Storage::disk('local')->exists($path) ? 'local' : (Storage::disk('public')->exists($path) ? 'public' : null);
        abort_unless($disk, 404, 'Document file missing from storage.');

        $filename = $doc->original_filename ?: basename($path);

        return Storage::disk($disk)->download($path, $filename);
    }
}
