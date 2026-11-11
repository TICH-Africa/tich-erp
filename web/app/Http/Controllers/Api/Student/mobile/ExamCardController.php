<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Services\StudentExamCardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ExamCardController extends Controller
{
    use ResolvesPortalStudent;

    public function __construct(
        protected StudentExamCardService $examCards,
    ) {}

    public function pdf(Request $request): Response
    {
        $student = $this->portalStudent($request);
        $semesterId = (int) $request->integer('semester', $student->current_semester_id ?? 0);
        abort_unless($semesterId > 0, 404);

        $filename = 'exam-card-'.Str::slug($student->registration_number).'.pdf';

        if ($request->query('disposition') === 'download') {
            return $this->examCards->downloadPdfResponse($student, $semesterId, $filename);
        }

        return $this->examCards->inlinePdfResponse(
            $student,
            $semesterId,
            $filename,
        );
    }
}
