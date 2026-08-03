<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Services\AuditService;
use App\Services\StaffLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $lifecycleService,
        protected AuditService $auditService,
    ) {}

    public function index(Request $request, int $staffId)
    {
        $staff = Staff::findOrFail($staffId);

        $query = $staff->documents()
            ->with('verifiedBy')
            ->when($request->document_type, fn ($q, $type) => $q->where('document_type', $type))
            ->when($request->is_verified, fn ($q, $verified) => $q->where('is_verified', $verified))
            ->when($request->is_missing, fn ($q, $missing) => $q->where('is_missing', $missing))
            ->when($request->expiring_soon, function ($q) {
                $q->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now()->addDays(30))
                    ->where('expiry_date', '>=', now());
            })
            ->orderByDesc('created_at');

        $perPage = (int) ($request->per_page ?? 25);
        $documents = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $documents->items(),
            'meta' => [
                'total' => $documents->total(),
                'per_page' => $documents->perPage(),
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, int $staffId)
    {
        $staff = Staff::findOrFail($staffId);

        $validated = $request->validate([
            'document_type' => 'required|string|in:cv,academic_certificate,professional_license,kra_pin,nssf,sha,national_id,good_conduct,passport_photo,bank_confirmation,other',
            'document_name' => 'required|string|max:300',
            'file' => 'required|file|max:10240',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $file = $validated['file'];
        $path = $file->storeAs("staff/{$staff->employee_number}/documents", time() . '_' . $file->getClientOriginalName(), 'public');

        $document = $this->lifecycleService->addDocument($staffId, [
            'document_type' => $validated['document_type'],
            'document_name' => $validated['document_name'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'issue_date' => $validated['issue_date'],
            'expiry_date' => $validated['expiry_date'],
            'notes' => $validated['notes'],
        ], $request->user()->id);

        return response()->json(['data' => $document], 201);
    }

    public function show(int $staffId, int $id)
    {
        $document = StaffDocument::where('staff_id', $staffId)->with('verifiedBy')->findOrFail($id);

        return response()->json(['data' => $document]);
    }

    public function verify(Request $request, int $staffId, int $id)
    {
        $document = StaffDocument::where('staff_id', $staffId)->findOrFail($id);

        $document->update([
            'is_verified' => 1,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $this->auditService->log(
            'staff.document.verified',
            'staff_documents',
            $document->id,
            ['is_verified' => 0],
            ['is_verified' => 1],
            'Document verified',
            'success',
            $request->user()->id,
            $request
        );

        return response()->json(['data' => $document->fresh()]);
    }

    public function destroy(Request $request, int $staffId, int $id)
    {
        $document = StaffDocument::where('staff_id', $staffId)->findOrFail($id);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $this->auditService->log(
            'staff.document.deleted',
            'staff_documents',
            $document->id,
            $document->toArray(),
            null,
            'Document deleted',
            'success',
            $request->user()->id,
            $request
        );

        $document->delete();

        return response()->json(null, 204);
    }
}
