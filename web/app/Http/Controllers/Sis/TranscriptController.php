<?php

namespace App\Http\Controllers\Sis;

use App\Http\Controllers\Controller;
use App\Services\StudentRecordService;
use App\Services\TranscriptDocumentService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TranscriptController extends Controller
{
    public function __construct(
        protected StudentRecordService $studentRecords,
        protected TranscriptDocumentService $documents,
    ) {}

    public function show(int $student): View
    {
        $record = $this->studentRecords->findForHub($student);

        return $this->documents->screen(
            $this->documents->forStudent($record),
            route('sis.students.show', $record->id).'#academic-record',
            route('sis.students.transcript.pdf', $record->id),
        );
    }

    public function pdf(int $student): SymfonyResponse
    {
        $record = $this->studentRecords->findForHub($student);
        $transcript = $this->documents->forStudent($record);

        return $this->documents->download($transcript, $this->documents->filename($record));
    }

    public function termPdf(int $student, int $semester): SymfonyResponse
    {
        $record = $this->studentRecords->findForHub($student);
        $transcript = $this->documents->forSemester($record, $semester);

        return $this->documents->download($transcript, $this->documents->filename($record, $semester));
    }
}
