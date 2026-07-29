<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ProgramTimetable;
use App\Models\ProgramTimetableSession;
use App\Services\PrintDocumentService;
use App\Services\StaffPortalService;
use App\Services\TimetableSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffPortalTimetableController extends Controller
{
    public function __construct(
        protected StaffPortalService $portalService,
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
        $staff = $this->authorizedStaff($request, $timetable);

        $payload = $this->timetableScheduling->documentPayload($timetable);
        $program = $payload['program'];
        $intake = $payload['intake'];
        $kindLabel = $payload['kindLabel'];

        return array_merge($payload, [
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
            'backUrl' => $includeActions ? route('staff.dashboard', ['section' => 'timetable']) : null,
            'pdfUrl' => $includeActions ? route('staff.timetable.pdf', $timetable) : null,
        ]);
    }

    protected function authorizedStaff(Request $request, ProgramTimetable $timetable)
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_unless($staff, 404);

        $assigned = ProgramTimetableSession::query()
            ->where('program_timetable_id', $timetable->id)
            ->where('staff_id', $staff->id)
            ->exists();

        abort_unless($assigned, 404);

        return $staff;
    }
}
