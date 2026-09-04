<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\StudentDocumentRequest;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalDocumentRequestController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);

        $validated = $request->validate([
            'document_type' => 'required|in:'.implode(',', array_keys(StudentDocumentRequest::TYPES)),
            'student_notes' => 'nullable|string|max:2000',
        ]);

        StudentDocumentRequest::query()->create([
            'student_id' => $student->id,
            'requested_by_user_id' => $request->user()->id,
            'document_type' => $validated['document_type'],
            'status' => 'pending',
            'student_notes' => $validated['student_notes'] ?? null,
        ]);

        return redirect()
            ->route('portal.dashboard', ['section' => 'documents'])
            ->with('success', 'Document request submitted.');
    }
}
