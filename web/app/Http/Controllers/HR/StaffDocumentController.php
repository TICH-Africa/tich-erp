<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Services\StaffLifecycleService;
use App\Services\StoredFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffDocumentController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $lifecycleService,
        protected \App\Services\PlatformNotificationService $notifications,
        protected StoredFileService $files,
    ) {}

    public function index(): View
    {
        $staff = Staff::withCount('documents')
            ->with(['documents' => fn ($q) => $q->orderByDesc('created_at')])
            ->orderBy('first_name')
            ->get(['id', 'employee_number', 'first_name', 'surname', 'job_title', 'department_id']);

        return view('hr.documents.index', ['staff' => $staff]);
    }

    public function show(int $staffId): View
    {
        $staff = Staff::with(['documents', 'department'])->findOrFail($staffId);

        return view('hr.documents.show', ['staff' => $staff]);
    }

    public function create(int $staffId): View
    {
        $staff = Staff::findOrFail($staffId);

        return view('hr.staff.documents.create', ['staff' => $staff]);
    }

    public function store(Request $request, int $staffId)
    {
        $staff = Staff::findOrFail($staffId);

        $validated = $request->validate([
            'document_type' => 'required|string|in:cv,academic_certificate,professional_license,kra_pin,nssf,sha,national_id,good_conduct,passport_photo,bank_confirmation,training_certification,other',
            'document_name' => 'required|string|max:300',
            'file' => 'required|file|max:10240',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $file = $validated['file'];
        $path = $this->files->store($file, "staff/{$staff->employee_number}/documents", 'public', time().'_'.$file->getClientOriginalName());

        $this->lifecycleService->addDocument($staffId, [
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

        return redirect()->route('hr.staff.show', $staff)->with('success', 'Document uploaded successfully.');
    }

    public function staffCreate(): View
    {
        $staff = auth()->user()->staff;

        abort_unless($staff, 403);

        return view('hr.staff.documents.staff-create', ['staff' => $staff]);
    }

    public function staffStore(Request $request)
    {
        $staff = $request->user()->staff;

        if (! $staff) {
            abort(403, 'No staff profile linked to your account.');
        }

$validated = $request->validate([
            'document_type' => 'required|string|in:cv,academic_certificate,professional_license,kra_pin,nssf,sha,national_id,good_conduct,passport_photo,bank_confirmation,training_certification,other',
            'document_name' => 'required|string|max:300',
            'file' => 'required|file|max:10240',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $file = $request->file('file');
        $path = $this->files->store($file, "staff/{$staff->employee_number}/documents", 'public', time().'_'.$file->getClientOriginalName());

        $this->lifecycleService->addDocument($staff->id, [
            'document_type' => $validated['document_type'],
            'document_name' => $validated['document_name'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'issue_date' => $validated['issue_date'],
            'expiry_date' => $validated['expiry_date'],
            'notes' => $validated['notes'],
        ], $request->user()->staff_id ?? $staff->id);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function destroy(Request $request, int $staffId, int $documentId)
    {
        $document = StaffDocument::where('staff_id', $staffId)->findOrFail($documentId);

        $document->delete();

        return redirect()->route('hr.staff.show', $staffId)->with('success', 'Document deleted successfully.');
    }

    public function staffDownload(int $documentId): StreamedResponse
    {
        $staff = request()->user()->staff;

        if (! $staff) {
            abort(403);
        }

        $document = StaffDocument::where('staff_id', $staff->id)->findOrFail($documentId);

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }

    public function employeeIndex(): View
    {
        $staff = auth()->user()->staff;

        abort_unless($staff, 403);

        $staffDocuments = $staff->documents()->orderByDesc('created_at')->get();

        return view('employee.documents.index', ['staff' => $staff, 'staffDocuments' => $staffDocuments]);
    }

    public function employeeCreate(): View
    {
        $staff = auth()->user()->staff;

        abort_unless($staff, 403);

        return view('employee.documents.create', ['staff' => $staff]);
    }

    public function employeeStore(Request $request)
    {
        $staff = $request->user()->staff;

        if (! $staff) {
            abort(403, 'No staff profile linked to your account.');
        }

        $validated = $request->validate([
            'document_type' => 'required|string|in:cv,academic_certificate,professional_license,kra_pin,nssf,sha,national_id,good_conduct,passport_photo,bank_confirmation,training_certification,other',
            'document_name' => 'required|string|max:300',
            'file' => 'required|file|max:10240',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $file = $request->file('file');
        $path = $this->files->store($file, "staff/{$staff->employee_number}/documents", 'public', time().'_'.$file->getClientOriginalName());

        $this->lifecycleService->addDocument($staff->id, [
            'document_type' => $validated['document_type'],
            'document_name' => $validated['document_name'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'issue_date' => $validated['issue_date'],
            'expiry_date' => $validated['expiry_date'],
            'notes' => $validated['notes'],
        ], $request->user()->staff_id ?? $staff->id);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function employeeDownload(int $documentId): StreamedResponse
    {
        $staff = request()->user()->staff;

        if (! $staff) {
            abort(403);
        }

        $document = StaffDocument::where('staff_id', $staff->id)->findOrFail($documentId);

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }

    public function download(int $staffId, int $documentId): StreamedResponse
    {
        $document = StaffDocument::where('staff_id', $staffId)->findOrFail($documentId);

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }

    public function read(int $staffId, int $documentId): View
    {
        $document = StaffDocument::where('staff_id', $staffId)->findOrFail($documentId);

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        $fileUrl = Storage::disk('public')->url($document->file_path);

        return view('hr.documents.read', [
            'document' => $document,
            'fileUrl' => $fileUrl,
        ]);
    }

    public function approve(Request $request, int $staffId, int $documentId)
    {
        $document = StaffDocument::where('staff_id', $staffId)->findOrFail($documentId);

        $document->update([
            'status' => 'approved',
            'approved_by' => $request->user()->staff_id,
            'approved_at' => now(),
            'is_verified' => true,
        ]);

        if ($document->staff && $document->staff->user_id) {
            $this->notifications->notifyUser(
                $document->staff->user_id,
                'Document Approved',
                "Your document '{$document->document_name}' has been approved by HR.",
                'staff_document',
                $document->id,
                'normal',
                route('employee.documents.index'),
            );
        }

        return back()->with('success', 'Document approved successfully.');
    }

    public function reject(Request $request, int $staffId, int $documentId)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $document = StaffDocument::where('staff_id', $staffId)->findOrFail($documentId);

        $document->update([
            'status' => 'rejected',
            'rejected_by' => $request->user()->staff_id,
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
            'is_verified' => false,
        ]);

        if ($document->staff && $document->staff->user_id) {
            $this->notifications->notifyUser(
                $document->staff->user_id,
                'Document Rejected',
                "Your document '{$document->document_name}' has been rejected. Reason: {$validated['rejection_reason']}",
                'staff_document',
                $document->id,
                'normal',
                route('employee.documents.index'),
            );
        }

        return back()->with('success', 'Document rejected successfully.');
    }
}
