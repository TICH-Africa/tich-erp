<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\AcademicsAccessService;
use App\Services\StudentRecordService;
use App\Services\TranscriptDocumentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcademicsTranscriptController extends Controller
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected StudentRecordService $studentRecords,
        protected TranscriptDocumentService $documents,
    ) {}

    public function show(Request $request, Student $student): View
    {
        $record = $this->managedStudent($request, $student);

        return $this->documents->screen(
            $this->documents->forStudent($record),
            route('academics.students.show', $record->id).'#academic-record',
            route('academics.students.transcript.pdf', $record->id),
        );
    }

    public function pdf(Request $request, Student $student): StreamedResponse
    {
        $record = $this->managedStudent($request, $student);
        $transcript = $this->documents->forStudent($record);

        return $this->documents->download($transcript, $this->documents->filename($record));
    }

    public function termPdf(Request $request, Student $student, int $semester): StreamedResponse
    {
        $record = $this->managedStudent($request, $student);
        $transcript = $this->documents->forSemester($record, $semester);

        return $this->documents->download($transcript, $this->documents->filename($record, $semester));
    }

    private function managedStudent(Request $request, Student $student): Student
    {
        abort_unless(
            $this->access->managesStudent($request->user(), $student),
            403,
            'That student belongs to another department.'
        );

        return $this->studentRecords->findForHub($student->id);
    }
}
