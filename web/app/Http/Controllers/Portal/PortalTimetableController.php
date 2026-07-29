<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ProgramTimetable;
use App\Services\PrintDocumentService;
use App\Services\StudentPortalService;
use App\Services\TimetableSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PortalTimetableController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected TimetableSchedulingService $timetableScheduling,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function print(Request $request, ProgramTimetable $timetable): View
    {
        return $this->printDocuments->render(
            'academics.programs.timetable-print',
            $this->documentData($request, $timetable),
        );
    }

    public function pdf(Request $request, ProgramTimetable $timetable): Response
    {
        $data = $this->documentData($request, $timetable, includeActions: false);
        $program = $data['program'];

        return $this->printDocuments->downloadPdf(
            'academics.programs.timetable-print',
            $data,
            sprintf(
                'timetable-%s-sem%d-%s.pdf',
                $timetable->timetable_kind,
                $timetable->teaching_period,
                Str::slug($program->program_code ?? (string) $program->id)
            ),
            'landscape',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function documentData(Request $request, ProgramTimetable $timetable, bool $includeActions = true): array
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 404);
        abort_unless((int) $timetable->program_id === (int) $student->program_id, 404);

        if ($timetable->status !== 'published') {
            abort_unless(
                $student->portal_activated_at || $student->enrollment_status === 'active',
                403,
            );
        }

        $payload = $this->timetableScheduling->documentPayload($timetable);
        $program = $payload['program'];
        $intake = $payload['intake'];
        $kindLabel = $payload['kindLabel'];

        return array_merge($payload, [
            'documentTitle' => $kindLabel,
            'documentSubtitle' => trim(($program->program_name ?? '').($intake ? ' · '.$intake->intakeLabel() : '')),
            'documentRef' => $this->printDocuments->documentRef(
                'TT',
                $student->registration_number,
                $timetable->teaching_period,
                $timetable->timetable_kind,
            ),
            'paperOrientation' => 'landscape',
            'metaRows' => [
                ['label' => 'Student', 'value' => e($student->registration_number)],
                ['label' => 'Programme', 'value' => e($program->program_name ?? '—')],
                ['label' => 'Intake', 'value' => e($intake?->intakeLabel() ?? '—')],
                ['label' => 'Semester', 'value' => e((string) $timetable->teaching_period)],
                ['label' => 'Campus', 'value' => e($timetable->campus?->campus_name ?? $student->campus?->campus_name ?? '—')],
                ['label' => 'Timetable', 'value' => e($timetable->displayTitle()), 'full' => true],
            ],
            'backUrl' => $includeActions ? route('portal.dashboard', ['section' => 'timetable']) : null,
            'pdfUrl' => $includeActions ? route('portal.timetable.pdf', $timetable) : null,
        ]);
    }
}
