<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\StudentPortalDocumentService;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalDocumentUploadController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentPortalDocumentService $documents,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 404);

        $request->validate([
            'file' => 'required|file',
        ]);

        $result = $this->documents->storeUpload(
            $student,
            $request->user(),
            $request->file('file'),
            $request->only(['document_type', 'student_notes']),
        );

        return redirect()
            ->route('portal.dashboard', ['section' => 'documents'])
            ->with('success', $result['message']);
    }
}
