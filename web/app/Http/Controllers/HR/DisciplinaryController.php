<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryDocument;
use App\Models\Staff;
use App\Services\AuditService;
use App\Services\DisciplinaryCorrespondenceService;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisciplinaryController extends Controller
{
    private const DOCUMENT_EXTENSIONS = 'pdf,jpg,jpeg,png,webp,doc,docx';

    private const MAX_DOCUMENTS = 10;

    public function __construct(
        protected AuditService $auditService,
        protected StoredFileService $files,
        protected DisciplinaryCorrespondenceService $correspondence,
    ) {}

    public function index(Request $request): View
    {
        $query = DisciplinaryCase::query()
            ->with(['staff', 'assignedTo'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('staff', function ($sq) use ($request) {
                    $sq->where('first_name', 'like', "%{$request->search}%")
                        ->orWhere('surname', 'like', "%{$request->search}%")
                        ->orWhere('employee_number', 'like', "%{$request->search}%");
                })
                ->orWhere('case_number', 'like', "%{$request->search}%");
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('incident_date');

        return view('hr.disciplinary.index', [
            'cases' => $query->paginate(20),
            'staffList' => $this->staffList(),
            'openCreateModal' => $request->session()->get('open_disciplinary_create_modal', false),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()
            ->route('hr.employee-relations.disciplinary.index')
            ->with('open_disciplinary_create_modal', true);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staff,id',
            'assigned_to' => 'nullable|exists:staff,id',
            'incident_date' => 'required|date',
            'incident_description' => 'required|string|max:5000',
            'investigation_notes' => 'nullable|string|max:5000',
            'witness_information' => 'nullable|string|max:3000',
            'hearing_date' => 'nullable|date',
            'committee_members' => 'nullable|string|max:2000',
            'documents' => 'nullable|array|max:'.self::MAX_DOCUMENTS,
            'documents.*' => 'file|extensions:'.self::DOCUMENT_EXTENSIONS.'|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('hr.employee-relations.disciplinary.index')
                ->withInput()
                ->withErrors($validator)
                ->with('open_disciplinary_create_modal', true);
        }

        $validated = $validator->validated();
        $caseNumber = 'DISC-'.now()->format('Y').'-'.strtoupper(Str::random(6));

        $case = DB::transaction(function () use ($validated, $caseNumber, $request) {
            $case = DisciplinaryCase::create([
                'case_number' => $caseNumber,
                'staff_id' => $validated['staff_id'],
                'assigned_to' => $validated['assigned_to'],
                'incident_date' => $validated['incident_date'],
                'incident_description' => $validated['incident_description'],
                'investigation_notes' => $validated['investigation_notes'],
                'witness_information' => $validated['witness_information'],
                'hearing_date' => $validated['hearing_date'],
                'committee_members' => $validated['committee_members'],
                'status' => 'open',
            ]);

            $this->storeUploadedDocuments($case, $request->file('documents', []));

            return $case;
        });

        $this->auditService->log(
            'disciplinary.case.created',
            'disciplinary_cases',
            $case->id,
            null,
            $case->toArray(),
            'Disciplinary case created',
            'success',
            $request->user()->id
        );

        return redirect()
            ->route('hr.employee-relations.disciplinary.index')
            ->with('success', 'Disciplinary case created successfully.');
    }

    public function show(DisciplinaryCase $disciplinaryCase): View
    {
        $case = $disciplinaryCase->load([
            'staff',
            'assignedTo',
            'documents',
            'correspondences.sentBy',
        ]);

        return view('hr.disciplinary.show', [
            'case' => $case,
            'recipientEmail' => $case->staff?->resolveErpEmail($case->staff?->user?->email),
        ]);
    }

    public function sendCorrespondence(Request $request, DisciplinaryCase $disciplinaryCase): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:200',
            'body' => 'required|string|max:10000',
        ]);

        try {
            $this->correspondence->send(
                $disciplinaryCase,
                $request->user(),
                $validated['subject'],
                $validated['body'],
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('hr.employee-relations.disciplinary.show', $disciplinaryCase)
                ->withFragment('disciplinary-correspondence')
                ->withInput()
                ->withErrors(['correspondence' => $e->getMessage()]);
        }

        return redirect()
            ->route('hr.employee-relations.disciplinary.show', $disciplinaryCase)
            ->withFragment('disciplinary-correspondence')
            ->with('success', 'Message sent to '.$disciplinaryCase->staff?->fullName().' by HR email.');
    }

    public function downloadDocument(DisciplinaryCase $disciplinaryCase, DisciplinaryDocument $document): StreamedResponse
    {
        if ((int) $document->disciplinary_case_id !== (int) $disciplinaryCase->id) {
            abort(404);
        }

        $path = ltrim(str_replace('\\', '/', (string) $document->document_path), '/');

        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Document file is missing from storage.');
        }

        $filename = preg_replace('/[^\w.\-() ]+/u', '_', (string) $document->document_name) ?: 'document';

        $mime = $document->mime_type
            ?: (Storage::disk('local')->mimeType($path) ?: 'application/octet-stream');

        return Storage::disk('local')->download($path, $filename, ['Content-Type' => $mime]);
    }

    public function edit(DisciplinaryCase $disciplinaryCase): View
    {
        $case = $disciplinaryCase->load('staff', 'assignedTo');

        return view('hr.disciplinary.edit', [
            'case' => $case,
            'staffList' => $this->staffList(),
        ]);
    }

    public function update(Request $request, DisciplinaryCase $disciplinaryCase): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'nullable|exists:staff,id',
            'investigation_notes' => 'nullable|string|max:5000',
            'witness_information' => 'nullable|string|max:3000',
            'hearing_date' => 'nullable|date',
            'committee_members' => 'nullable|string|max:2000',
            'decision' => 'nullable|string|max:5000',
            'action_type' => 'nullable|in:warning,suspension,termination,appeal,other',
            'action_details' => 'nullable|string|max:5000',
            'action_start_date' => 'nullable|date',
            'action_end_date' => 'nullable|date',
            'status' => 'required|in:open,under_investigation,hearing_scheduled,decided,appealed,closed',
            'hr_comments' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('hr.employee-relations.disciplinary.edit', $disciplinaryCase)
                ->withInput()
                ->withErrors($validator);
        }

        $validated = $validator->validated();
        $oldSnapshot = $disciplinaryCase->toArray();

        DB::transaction(function () use ($disciplinaryCase, $validated) {
            $disciplinaryCase->update($validated);
        });

        $this->auditService->log(
            'disciplinary.case.updated',
            'disciplinary_cases',
            $disciplinaryCase->id,
            $oldSnapshot,
            $disciplinaryCase->fresh()->toArray(),
            'Disciplinary case updated',
            'success',
            $request->user()->id
        );

        return redirect()
            ->route('hr.employee-relations.disciplinary.index')
            ->with('success', 'Disciplinary case updated successfully.');
    }

    public function destroy(Request $request, DisciplinaryCase $disciplinaryCase): RedirectResponse
    {
        DB::transaction(function () use ($disciplinaryCase, $request) {
            $this->auditService->log(
                'disciplinary.case.deleted',
                'disciplinary_cases',
                $disciplinaryCase->id,
                $disciplinaryCase->toArray(),
                null,
                'Disciplinary case deleted',
                'success',
                $request->user()->id
            );

            $disciplinaryCase->delete();
        });

        return redirect()
            ->route('hr.employee-relations.disciplinary.index')
            ->with('success', 'Disciplinary case deleted successfully.');
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $uploads
     */
    private function storeUploadedDocuments(DisciplinaryCase $case, array|UploadedFile|null $uploads): void
    {
        if ($uploads === null) {
            return;
        }

        $files = is_array($uploads) ? $uploads : [$uploads];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $original = $file->getClientOriginalName();
            $displayName = pathinfo($original, PATHINFO_FILENAME) ?: 'Attachment';
            $safeName = time().'_'.Str::random(6).'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $original);
            $path = $this->files->store($file, "disciplinary/{$case->id}", 'local', $safeName);

            DisciplinaryDocument::create([
                'disciplinary_case_id' => $case->id,
                'document_name' => $displayName,
                'document_path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            ]);
        }
    }

    private function staffList()
    {
        return Staff::query()
            ->excludePlatformOperators()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title']);
    }
}
