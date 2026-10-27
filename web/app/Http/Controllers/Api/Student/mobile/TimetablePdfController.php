<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Controller;
use App\Models\ProgramTimetable;
use App\Services\PrintDocumentService;
use App\Services\TimetableSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TimetablePdfController extends Controller
{
    use ResolvesPortalStudent;

    public function __construct(
        protected TimetableSchedulingService $timetableScheduling,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function __invoke(Request $request, ProgramTimetable $timetable): Response
    {
        $student = $this->portalStudent($request);
        abort_unless((int) $timetable->program_id === (int) $student->program_id, 404);
        abort_unless($timetable->status === 'published', 404);

        $payload = $this->timetableScheduling->documentPayload($timetable);
        $program = $payload['program'];
        $intake = $payload['intake'];
        $kindLabel = $payload['kindLabel'];

        $data = array_merge($payload, [
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
                ['label' => 'Programme', 'value' => e($program->program_name ?? '-')],
                ['label' => 'Intake', 'value' => e($intake?->intakeLabel() ?? '-')],
                ['label' => 'Semester', 'value' => e((string) $timetable->teaching_period)],
                ['label' => 'Campus', 'value' => e($timetable->campus?->campus_name ?? $student->campus?->campus_name ?? '-')],
                ['label' => 'Timetable', 'value' => e($timetable->displayTitle()), 'full' => true],
            ],
            'backUrl' => null,
            'pdfUrl' => null,
        ]);

        return $this->printDocuments->inlinePdf(
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
}
