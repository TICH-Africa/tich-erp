<?php

namespace App\Http\Controllers\Sis;

use App\Http\Controllers\Controller;
use App\Services\PrintDocumentService;
use App\Services\StudentRecordService;
use App\Services\TranscriptService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TranscriptController extends Controller
{
    public function __construct(
        protected StudentRecordService $studentRecords,
        protected TranscriptService $transcripts,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function show(int $student): View
    {
        return $this->printDocuments->render(
            'sis.transcript.print',
            $this->documentData($this->buildTranscript($student)),
        );
    }

    public function pdf(int $student): Response
    {
        $transcript = $this->buildTranscript($student);
        $registration = $transcript['student']->registration_number;

        return $this->printDocuments->downloadPdf(
            'sis.transcript.print',
            $this->documentData($transcript, includeActions: false),
            'transcript-'.Str::slug($registration).'.pdf',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTranscript(int $student): array
    {
        $record = $this->studentRecords->findForHub($student);

        return $this->transcripts->build($record);
    }

    /**
     * @param  array<string, mixed>  $transcript
     * @return array<string, mixed>
     */
    protected function documentData(array $transcript, bool $includeActions = true): array
    {
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
                ['label' => 'Programme', 'value' => e($program?->program_name ?? '—')],
                ['label' => 'Campus', 'value' => e($student->campus?->campus_name ?? '—')],
                ['label' => 'Enrollment status', 'value' => e(ucfirst($student->enrollment_status))],
                ['label' => 'Department', 'value' => e($program?->department?->dept_name ?? '—')],
            ],
            'backUrl' => $includeActions ? route('sis.students.show', $student->id).'#academic-record' : null,
            'pdfUrl' => $includeActions ? route('sis.students.transcript.pdf', $student->id) : null,
        ];
    }
}
