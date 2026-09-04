<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\StudentTranscriptRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TranscriptRequestController extends DepartmentAcademicsController
{
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
