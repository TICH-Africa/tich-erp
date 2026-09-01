<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\PrintDocumentService;
use App\Services\StudentExamCardService;
use App\Services\StudentPortalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PortalExamCardController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentExamCardService $examCards,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function print(Request $request): View
    {
        return $this->printDocuments->render(
            'portal.exam-card.print',
            $this->documentData($request),
        );
    }

    public function pdf(Request $request): Response
    {
        $data = $this->documentData($request, includeActions: false);
        $student = $data['student'];

        return $this->printDocuments->downloadPdf(
            'portal.exam-card.print',
            $data,
            'exam-card-'.Str::slug($student->registration_number).'.pdf',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function documentData(Request $request, bool $includeActions = true): array
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 404);

        $semesterId = (int) $request->integer('semester', $student->current_semester_id ?? 0);
        abort_unless($semesterId > 0, 404);

        $document = $this->examCards->buildDocument($student, $semesterId);
        $student = $document['student'];
        $semester = $document['semester'];
        $examCard = $document['exam_card'];

        return array_merge($document, [
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
            'backUrl' => $includeActions
                ? route('portal.dashboard', ['section' => 'academics', 'tab' => 'exams'])
                : null,
            'pdfUrl' => $includeActions
                ? route('portal.exam-card.pdf', ['semester' => $semesterId])
                : null,
        ]);
    }
}
