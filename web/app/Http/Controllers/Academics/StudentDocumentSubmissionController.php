<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\StudentApplicationDocumentSubmission;
use App\Services\StudentPortalDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentDocumentSubmissionController extends DepartmentAcademicsController
{
    public function __construct(
        protected StudentPortalDocumentService $documents,
    ) {}

    protected function authorizeRegistrar(Request $request, Department $department): Department
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless(
            $request->user()->hasAnyRole(['Academic Registrar', 'Super Admin', 'Head of Academics']),
            403,
            'Only Academic Registrar / Head of Academics can review document changes.'
        );

        return $hub;
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        abort_unless(Schema::hasTable('student_application_document_submissions'), 404);

        $status = (string) $request->query('status', 'pending');

        $submissions = StudentApplicationDocumentSubmission::query()
            ->with(['student.applicant', 'student.program:id,program_code,program_name', 'applicationDocument'])
            ->when($status !== '' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('academics.document-submissions.index', [
            'department' => $hub,
            'submissions' => $submissions,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Department $department, StudentApplicationDocumentSubmission $submission): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $submission->load(['student.applicant', 'student.program', 'applicationDocument']);

        return view('academics.document-submissions.show', [
            'department' => $hub,
            'submission' => $submission,
        ]);
    }

    public function preview(
        Request $request,
        Department $department,
        StudentApplicationDocumentSubmission $submission,
    ): StreamedResponse {
        $this->authorizeRegistrar($request, $department);

        return $this->documents->inlineSubmissionResponse($submission);
    }

    public function approve(Request $request, Department $department, StudentApplicationDocumentSubmission $submission): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $validated = $request->validate([
            'reviewer_notes' => 'nullable|string|max:2000',
        ]);

        $this->documents->approveSubmission($submission, $request->user(), $validated['reviewer_notes'] ?? null);

        return redirect()
            ->route('departments.academics.document-submissions.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['submission' => $submission->id]
            ))
            ->with('success', 'Document change approved and file updated.');
    }

    public function reject(Request $request, Department $department, StudentApplicationDocumentSubmission $submission): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $validated = $request->validate([
            'reviewer_notes' => 'required|string|max:2000',
        ]);

        $this->documents->rejectSubmission($submission, $request->user(), $validated['reviewer_notes']);

        return redirect()
            ->route('departments.academics.document-submissions.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['submission' => $submission->id]
            ))
            ->with('success', 'Document change rejected.');
    }
}
