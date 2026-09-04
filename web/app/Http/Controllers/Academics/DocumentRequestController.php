<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\StudentDocumentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentRequestController extends DepartmentAcademicsController
{
    protected function authorizeRegistrar(Request $request, Department $department): Department
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless(
            $request->user()->hasAnyRole(['Academic Registrar', 'Super Admin', 'Head of Academics']),
            403,
            'Only Academic Registrar / Head of Academics can manage document requests.'
        );

        return $hub;
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $status = (string) $request->query('status', 'pending');

        $requests = StudentDocumentRequest::query()
            ->with(['student.applicant', 'student.program:id,program_code,program_name'])
            ->when($status !== '' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('academics.document-requests.index', [
            'department' => $hub,
            'requests' => $requests,
            'status' => $status,
            'types' => StudentDocumentRequest::TYPES,
        ]);
    }

    public function show(Request $request, Department $department, StudentDocumentRequest $documentRequest): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $documentRequest->load(['student.applicant', 'student.program']);

        return view('academics.document-requests.show', [
            'department' => $hub,
            'documentRequest' => $documentRequest,
            'types' => StudentDocumentRequest::TYPES,
        ]);
    }

    public function issue(Request $request, Department $department, StudentDocumentRequest $documentRequest): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $validated = $request->validate([
            'reviewer_notes' => 'nullable|string|max:2000',
        ]);

        abort_unless($documentRequest->status === 'pending', 422);

        $documentRequest->update([
            'status' => 'issued',
            'reviewer_notes' => $validated['reviewer_notes'] ?? null,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'issued_at' => now(),
        ]);

        return redirect()
            ->route('departments.academics.document-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['documentRequest' => $documentRequest->id]
            ))
            ->with('success', 'Document request marked as issued.');
    }

    public function reject(Request $request, Department $department, StudentDocumentRequest $documentRequest): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $validated = $request->validate([
            'reviewer_notes' => 'required|string|max:2000',
        ]);

        abort_unless($documentRequest->status === 'pending', 422);

        $documentRequest->update([
            'status' => 'rejected',
            'reviewer_notes' => $validated['reviewer_notes'],
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('departments.academics.document-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['documentRequest' => $documentRequest->id]
            ))
            ->with('success', 'Document request rejected.');
    }
}
