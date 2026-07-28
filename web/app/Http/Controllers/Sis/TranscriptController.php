<?php

namespace App\Http\Controllers\Sis;

use App\Http\Controllers\Controller;
use App\Services\StudentRecordService;
use App\Services\TranscriptService;
use Illuminate\View\View;

class TranscriptController extends Controller
{
    public function __construct(
        protected StudentRecordService $studentRecords,
        protected TranscriptService $transcripts,
    ) {}

    public function show(int $student): View
    {
        $record = $this->studentRecords->findForHub($student);
        $transcript = $this->transcripts->build($record);

        return view('sis.transcript.show', [
            'transcript' => $transcript,
            'printable' => true,
        ]);
    }
}
