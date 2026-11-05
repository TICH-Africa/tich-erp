<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\PrintDocumentService;
use App\Services\StudentExamCardService;
use App\Services\StudentPortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function pdf(Request $request): StreamedResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 404);

        $semesterId = (int) $request->integer('semester', $student->current_semester_id ?? 0);
        abort_unless($semesterId > 0, 404);

        return $this->examCards->downloadPdfResponse(
            $student,
            $semesterId,
            'exam-card-'.Str::slug($student->registration_number).'.pdf',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function documentData(Request $request): array
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 404);

        $semesterId = (int) $request->integer('semester', $student->current_semester_id ?? 0);
        abort_unless($semesterId > 0, 404);

        $document = $this->examCards->buildDocument($student, $semesterId);

        return $this->examCards->printViewData($document, includeActions: true, semesterId: $semesterId);
    }
}
