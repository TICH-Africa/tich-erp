<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\ExaminationPaper;
use App\Services\ExaminationPaperService;
use App\Services\StaffPortalService;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExaminationPaperController extends DepartmentAcademicsController
{
    public function __construct(
        \App\Services\AcademicsAccessService $access,
        \App\Services\DepartmentDashboardService $departmentDashboard,
        protected ExaminationPaperService $papers,
        protected StaffPortalService $staffPortal,
        protected StoredFileService $files,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function moderate(Request $request, Department $department, ExaminationPaper $examinationPaper): RedirectResponse
    {
        $this->authorizeHub($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'moderated_file' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->papers->moderate(
            $examinationPaper,
            $staff,
            $request->file('moderated_file'),
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Examination paper marked as moderated.');
    }

    public function approve(Request $request, Department $department, ExaminationPaper $examinationPaper): RedirectResponse
    {
        $this->authorizeHub($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $request->validate([
            'approved_file' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx'],
        ]);

        $this->papers->approve($examinationPaper, $staff, $request->file('approved_file'));

        return back()->with('success', 'Examination paper approved.');
    }

    public function download(Request $request, Department $department, ExaminationPaper $examinationPaper, string $kind): StreamedResponse
    {
        $this->authorizeHub($request, $department);
        abort_unless(in_array($kind, ['draft', 'moderated', 'approved'], true), 404);

        $path = $this->files->relativePath($examinationPaper->filePathFor($kind));
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path, basename($path));
    }
}
