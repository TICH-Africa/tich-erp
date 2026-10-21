<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Services\PrintDocumentService;
use App\Services\StudentExamCardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ExamCardController extends Controller
{
    use ResolvesPortalStudent;

    public function __construct(
        protected StudentExamCardService $examCards,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function pdf(Request $request): Response
    {
        $student = $this->portalStudent($request);
        $semesterId = (int) $request->integer('semester', $student->current_semester_id ?? 0);
        abort_unless($semesterId > 0, 404);

        $document = $this->examCards->buildDocument($student, $semesterId);
        $student = $document['student'];
        $semester = $document['semester'];
        $examCard = $document['exam_card'];

        $data = array_merge($document, [
            'documentTitle' => 'Examination card',
            'documentSubtitle' => $semester->semester_label,
            'documentRef' => $this->printDocuments->documentRef('EC', $examCard->exam_card_number),
            'paperOrientation' => 'portrait',
            'metaRows' => [
                ['label' => 'Student name', 'value' => e($student->applicant?->fullName() ?? $student->registration_number)],
                ['label' => 'Registration number', 'value' => e($student->registration_number)],
                ['label' => 'Examination number', 'value' => e($examCard->examination_number ?? $student->registration_number)],
                ['label' => 'Programme', 'value' => e($student->program?->program_name ?? '-')],
                ['label' => 'Semester', 'value' => e($semester->semester_label)],
                ['label' => 'Card number', 'value' => e($examCard->exam_card_number)],
            ],
            'backUrl' => null,
            'pdfUrl' => null,
        ]);

        return $this->printDocuments->downloadPdf(
            'portal.exam-card.print',
            $data,
            'exam-card-'.Str::slug($student->registration_number).'.pdf',
        );
    }
}
