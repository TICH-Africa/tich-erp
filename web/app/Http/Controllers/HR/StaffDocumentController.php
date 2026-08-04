<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\StaffDocumentTemplate;
use App\Services\DocumentGenerationService;
use App\Services\StaffLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffDocumentController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $lifecycleService,
        protected DocumentGenerationService $documentService,
    ) {}

    public function index(): View
    {
        $staff = Staff::withCount('documents')
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
            'document_type' => 'required|string|in:cv,academic_certificate,professional_license,kra_pin,nssf,sha,national_id,good_conduct,passport_photo,bank_confirmation,other',
            'document_name' => 'required|string|max:300',
            'file' => 'required|file|max:10240',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $file = $validated['file'];
        $path = $file->storeAs("staff/{$staff->employee_number}/documents", time() . '_' . $file->getClientOriginalName(), 'public');

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

    public function staffStore(Request $request)
    {
        $staff = $request->user()->staff;

        if (! $staff) {
            abort(403, 'No staff profile linked to your account.');
        }

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

    public function sendForm(int $staffId): View
    {
        $staff = Staff::findOrFail($staffId);
        $templates = StaffDocumentTemplate::where('is_active', 1)->get(['id', 'name', 'type', 'content']);

        return view('hr.documents.send', ['staff' => $staff, 'templates' => $templates]);
    }

    public function sendToStaff(Request $request, int $staffId)
    {
        $staff = Staff::findOrFail($staffId);
        $template = StaffDocumentTemplate::findOrFail($request->integer('template_id'));

        $content = $this->documentService->populateTemplate($template, $staff);
        $html = $this->documentService->renderDocument($content, $template->name, strtoupper($template->name));

        $filename = $template->name . ' - ' . $staff->fullName() . '.pdf';
        $path = 'staff/' . $staff->employee_number . '/documents/' . time() . '_' . preg_replace('/[^a-z0-9_-]/i', '_', $filename);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->WriteHTML($html);
        $pdfContent = $mpdf->Output('', 'S');

        Storage::disk('public')->put($path, $pdfContent);

        $this->lifecycleService->addDocument($staffId, [
            'document_type' => 'other',
            'document_name' => $template->name . ' - ' . $staff->fullName(),
            'file_path' => $path,
            'original_filename' => $filename,
            'mime_type' => 'application/pdf',
            'file_size' => strlen($pdfContent),
            'notes' => 'Generated from template: ' . $template->name,
        ], $request->user()->staff_id ?? $staff->id);

        return redirect()->route('hr.documents.show', $staff)->with('success', 'Document sent to staff successfully.');
    }

    private function populateTemplate(StaffDocumentTemplate $template, Staff $staff): string
    {
        $data = [
            'staff_full_name' => $staff->fullName(),
            'staff_first_name' => $staff->first_name,
            'staff_middle_name' => $staff->middle_name ?? '',
            'staff_surname' => $staff->surname,
            'staff_employee_number' => $staff->employee_number,
            'staff_job_title' => $staff->job_title,
            'staff_department' => $staff->department->dept_name ?? '',
            'staff_campus' => $staff->campus->campus_name ?? '',
            'staff_employment_category' => ucfirst($staff->employment_category),
            'staff_employment_start_date' => $staff->employment_start_date?->format('F j, Y') ?? '',
            'staff_contract_end_date' => $staff->contract_end_date?->format('F j, Y') ?? 'Ongoing',
            'staff_gross_monthly_salary' => number_format($staff->gross_monthly_salary, 2),
            'staff_kra_pin' => $staff->kra_pin ?? '',
            'staff_nssf_number' => $staff->nssf_number ?? '',
            'staff_sha_number' => $staff->sha_number ?? '',
            'staff_helb_number' => $staff->helb_number ?? '',
            'staff_phone_number' => $staff->phone_number,
            'staff_primary_email' => $staff->primary_email,
            'staff_organisation_email' => $staff->organisation_email,
            'staff_postal_address' => $staff->postal_address ?? '',
            'staff_physical_address' => $staff->physical_address ?? '',
            'staff_date_of_birth' => $staff->date_of_birth?->format('F j, Y') ?? '',
            'staff_gender' => $staff->gender,
            'staff_marital_status' => $staff->marital_status ?? '',
            'staff_national_id' => $staff->national_id_number ?? '',
            'staff_line_manager' => $staff->lineManager?->fullName() ?? '',
            'institution_name' => config('app.name', 'TICH ERP'),
            'current_date' => now()->format('F j, Y'),
            'current_year' => now()->format('Y'),
        ];

        $content = $template->content;

        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value ?? '', $content);
            $content = str_replace('{{ ' . $key . ' }}', $value ?? '', $content);
        }

        return $content;
    }

    public function destroy(Request $request, int $staffId, int $documentId)
    {
        $document = StaffDocument::where('staff_id', $staffId)->findOrFail($documentId);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

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

    public function download(int $staffId, int $documentId): StreamedResponse
    {
        $document = StaffDocument::where('staff_id', $staffId)->findOrFail($documentId);

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }
}
