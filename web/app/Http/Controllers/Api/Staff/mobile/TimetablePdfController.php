<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Models\ProgramTimetable;
use App\Models\ProgramTimetableSession;
use App\Services\PrintDocumentService;
use App\Services\TimetableSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TimetablePdfController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(
        protected TimetableSchedulingService $timetableScheduling,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function __invoke(Request $request, ProgramTimetable $timetable): Response
    {
        $staff = $this->portalStaff($request);

        $assigned = ProgramTimetableSession::query()
            ->where('program_timetable_id', $timetable->id)
            ->where('staff_id', $staff->id)
            ->exists();

        abort_unless($assigned, 404);

        $payload = $this->timetableScheduling->documentPayload($timetable);
        $program = $payload['program'];
        $intake = $payload['intake'];
        $kindLabel = $payload['kindLabel'];

        $data = array_merge($payload, [
            'documentTitle' => $kindLabel,
            'documentSubtitle' => trim(($program->program_name ?? '').($intake ? ' · '.$intake->intakeLabel() : '')),
            'documentRef' => $this->printDocuments->documentRef(
                'TT',
                $staff->employee_number ?? $staff->id,
                $timetable->teaching_period,
                $timetable->timetable_kind,
            ),
            'paperOrientation' => 'landscape',
            'metaRows' => [
                ['label' => 'Lecturer', 'value' => e(trim($staff->first_name.' '.$staff->surname))],
                ['label' => 'Employee no.', 'value' => e($staff->employee_number ?? '-')],
                ['label' => 'Programme', 'value' => e($program->program_name ?? '-')],
                ['label' => 'Intake', 'value' => e($intake?->intakeLabel() ?? '-')],
                ['label' => 'Semester', 'value' => e((string) $timetable->teaching_period)],
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
