<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\PrintDocumentService;
use App\Services\StudentPortalService;
use App\Services\TranscriptService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PortalTranscriptController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected TranscriptService $transcripts,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function print(Request $request): View
    {
        return $this->printDocuments->render(
            'sis.transcript.print',
            $this->documentData($request),
        );
    }

    public function pdf(Request $request): Response
    {
        $transcript = $this->buildTranscript($request);
        $registration = $transcript['student']->registration_number;

        return $this->printDocuments->downloadPdf(
            'sis.transcript.print',
            $this->documentData($request, includeActions: false),
            'transcript-'.Str::slug($registration).'.pdf',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTranscript(Request $request): array
    {
        $student = $this->authorizedStudent($request);

        return $this->transcripts->build($student);
    }

    /**
     * @return array<string, mixed>
     */
    protected function documentData(Request $request, bool $includeActions = true): array
    {
        $transcript = $this->buildTranscript($request);
        $student = $transcript['student'];
        $program = $transcript['program'];

        return [
            'transcript' => $transcript,
            'documentTitle' => 'Official Academic Transcript',
            'documentSubtitle' => $student->registration_number,
            'documentRef' => $this->printDocuments->documentRef('TR', $student->registration_number),
            'paperOrientation' => 'portrait',
            'metaRows' => [
                ['label' => 'Student name', 'value' => e($student->applicant?->fullName() ?? $student->registration_number)],
                ['label' => 'Registration number', 'value' => e($student->registration_number)],
                ['label' => 'Programme', 'value' => e($program?->program_name ?? '-')],
                ['label' => 'Campus', 'value' => e($student->campus?->campus_name ?? '-')],
                ['label' => 'Enrollment status', 'value' => e(ucfirst($student->enrollment_status))],
                ['label' => 'Department', 'value' => e($program?->department?->dept_name ?? '-')],
            ],
            'backUrl' => $includeActions ? route('portal.dashboard', ['section' => 'academics']) : null,
            'pdfUrl' => $includeActions ? route('portal.transcript.pdf') : null,
        ];
    }

    protected function authorizedStudent(Request $request)
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 404);

        $hasApprovedGrades = DB::table('grade_records')
            ->where('student_id', $student->id)
            ->exists();

        abort_unless($hasApprovedGrades, 404);

        return $student;
    }
}
