<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\StudentTranscriptRequest;
use App\Services\AcademicsAccessService;
use App\Services\DepartmentDashboardService;
use App\Services\StudentRecordService;
use App\Services\TranscriptDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranscriptRequestController extends DepartmentAcademicsController
{
    public function __construct(
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
        protected StudentRecordService $studentRecords,
        protected TranscriptDocumentService $documents,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    protected function authorizeRegistrar(Request $request, Department $department): Department
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless(
            $request->user()->hasAnyRole(['Academic Registrar', 'Super Admin', 'Head of Academics']),
            403,
            'Only Academic Registrar / Head of Academics can manage transcript requests.'
        );

        return $hub;
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $status = (string) $request->query('status', 'pending');

        $requests = StudentTranscriptRequest::query()
            ->with(['student.applicant', 'student.program:id,program_code,program_name'])
            ->when($status !== '' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('academics.transcript-requests.index', [
            'department' => $hub,
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Department $department, StudentTranscriptRequest $transcriptRequest): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $transcriptRequest->load(['student.applicant', 'student.program', 'requester', 'reviewer']);

        return view('academics.transcript-requests.show', [
            'department' => $hub,
            'transcriptRequest' => $transcriptRequest,
        ]);
    }

    /**
     * The transcript a request is asking for, on the same official document the student can collect.
     */
    protected function transcriptData(Request $request, StudentTranscriptRequest $transcriptRequest): array
    {
        abort_if($transcriptRequest->student_id === null, 404, 'That request has no student attached.');

        return $this->documents->forStudent($this->studentRecords->findForHub($transcriptRequest->student_id));
    }

    public function transcript(Request $request, Department $department, StudentTranscriptRequest $transcriptRequest): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $backUrl = $this->requestUrl($request, $hub, $transcriptRequest);

        return $this->documents->screen(
            $this->transcriptData($request, $transcriptRequest),
            $backUrl,
            $backUrl.'/transcript/pdf',
        );
    }

    public function transcriptPdf(Request $request, Department $department, StudentTranscriptRequest $transcriptRequest): StreamedResponse
    {
        $this->authorizeRegistrar($request, $department);
        $transcript = $this->transcriptData($request, $transcriptRequest);

        return $this->documents->download(
            $transcript,
            $this->documents->filename($transcript['student']),
        );
    }

    protected function requestUrl(Request $request, Department $hub, StudentTranscriptRequest $transcriptRequest): string
    {
        return route('departments.academics.transcript-requests.show', array_merge(
            \App\Support\AcademicsRouteParams::fromRequest($request),
            ['transcriptRequest' => $transcriptRequest->id]
        ));
    }

    public function issue(Request $request, Department $department, StudentTranscriptRequest $transcriptRequest): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $validated = $request->validate([
            'registrar_notes' => 'nullable|string|max:2000',
        ]);

        abort_unless(in_array($transcriptRequest->status, ['pending', 'processing'], true), 422);

        $transcriptRequest->update([
            'status' => 'issued',
            'registrar_notes' => $validated['registrar_notes'] ?? $transcriptRequest->registrar_notes,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'issued_at' => now(),
        ]);

        return redirect()
            ->route('departments.academics.transcript-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['transcriptRequest' => $transcriptRequest->id]
            ))
            ->with('success', 'Transcript request marked as issued.');
    }

    public function reject(Request $request, Department $department, StudentTranscriptRequest $transcriptRequest): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $validated = $request->validate([
            'registrar_notes' => 'required|string|max:2000',
        ]);

        abort_unless(in_array($transcriptRequest->status, ['pending', 'processing'], true), 422);

        $transcriptRequest->update([
            'status' => 'rejected',
            'registrar_notes' => $validated['registrar_notes'],
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('departments.academics.transcript-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['transcriptRequest' => $transcriptRequest->id]
            ))
            ->with('success', 'Transcript request rejected.');
    }
}
