<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Finance\StudentStatementService;
use App\Services\PrintDocumentService;
use App\Services\StudentPortalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PortalFeeStatementController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentStatementService $statements,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function print(Request $request): View
    {
        return $this->printDocuments->render(
            'finance.ar.print-statements',
            $this->documentData($request),
        );
    }

    public function pdf(Request $request): Response
    {
        $student = $this->authorizedStudent($request);

        return $this->printDocuments->downloadPdf(
            'finance.ar.print-statements',
            $this->documentData($request, includeActions: false),
            'fee-statement-'.Str::slug($student->registration_number).'.pdf',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function documentData(Request $request, bool $includeActions = true): array
    {
        $student = $this->authorizedStudent($request);
        $entries = $this->statements->buildStatement($student);
        $outstanding = (float) ($student->overall_balance ?? 0);

        return [
            'statements' => [[
                'student' => $student->loadMissing(['applicant', 'program']),
                'entries' => $entries,
                'outstanding' => $outstanding,
            ]],
            'documentTitle' => 'Student Fee Statement',
            'documentSubtitle' => $student->registration_number,
            'documentRef' => $this->printDocuments->documentRef('FS', $student->registration_number),
            'paperOrientation' => 'portrait',
            'backUrl' => $includeActions ? route('portal.dashboard', ['section' => 'finance']) : null,
            'pdfUrl' => $includeActions ? route('portal.fee-statement.pdf') : null,
        ];
    }

    protected function authorizedStudent(Request $request)
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 404);

        return $student;
    }
}
