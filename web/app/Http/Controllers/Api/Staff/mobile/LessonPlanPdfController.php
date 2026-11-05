<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Models\LessonPlan;
use App\Services\LessonPlanDocumentService;
use App\Services\PrintDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LessonPlanPdfController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(
        protected LessonPlanDocumentService $documents,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function __invoke(Request $request, LessonPlan $plan): Response
    {
        $staff = $this->portalStaff($request);
        $this->documents->assertCanView($plan, $request->user(), $staff);
        abort_if($plan->isUploadBased(), 404, 'This lesson plan uses an uploaded document.');

        $data = $this->documents->documentPayload($plan);

        return $this->printDocuments->inlinePdf(
            'staff.lesson-plans.print',
            array_merge($data, [
                'backUrl' => null,
                'pdfUrl' => null,
            ]),
            sprintf('lesson-plan-%s.pdf', $plan->plan_number),
            'portrait',
        );
    }
}
