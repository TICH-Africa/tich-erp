<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LessonPlan;
use App\Services\LessonPlanDocumentService;
use App\Services\PrintDocumentService;
use App\Services\StaffPortalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffLessonPlanDocumentController extends Controller
{
    public function __construct(
        protected StaffPortalService $portalService,
        protected LessonPlanDocumentService $documents,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function print(Request $request, LessonPlan $plan): View
    {
        $this->authorizePlan($request, $plan);
        abort_if($plan->isUploadBased(), 404, 'This lesson plan uses an uploaded document.');

        $data = $this->documents->documentPayload($plan);

        return $this->printDocuments->render('staff.lesson-plans.print', array_merge($data, [
            'backUrl' => route('staff.dashboard', ['section' => 'lesson-plans', 'edit_plan' => $plan->id]),
            'pdfUrl' => route('lesson-plans.pdf', $plan),
        ]));
    }

    public function pdf(Request $request, LessonPlan $plan): Response
    {
        $this->authorizePlan($request, $plan);
        abort_if($plan->isUploadBased(), 404, 'This lesson plan uses an uploaded document.');

        $data = $this->documents->documentPayload($plan);

        return $this->printDocuments->downloadPdf(
            'staff.lesson-plans.print',
            $data,
            sprintf('lesson-plan-%s.pdf', $plan->plan_number),
        );
    }

    public function showUpload(Request $request, LessonPlan $plan): StreamedResponse
    {
        $this->authorizePlan($request, $plan);
        abort_unless($plan->isUploadBased() && filled($plan->uploaded_file_path), 404);

        abort_unless(Storage::disk('local')->exists($plan->uploaded_file_path), 404);

        $mime = Storage::disk('local')->mimeType($plan->uploaded_file_path) ?: 'application/octet-stream';
        $filename = $this->safeFilename($plan->uploaded_file_name ?: basename($plan->uploaded_file_path));

        return Storage::disk('local')->response(
            $plan->uploaded_file_path,
            $filename,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    public function downloadUpload(Request $request, LessonPlan $plan): StreamedResponse
    {
        $this->authorizePlan($request, $plan);
        abort_unless($plan->isUploadBased() && filled($plan->uploaded_file_path), 404);

        abort_unless(Storage::disk('local')->exists($plan->uploaded_file_path), 404);

        $filename = $this->safeFilename($plan->uploaded_file_name ?: basename($plan->uploaded_file_path));

        return Storage::disk('local')->download(
            $plan->uploaded_file_path,
            $filename,
        );
    }

    private function authorizePlan(Request $request, LessonPlan $plan): void
    {
        $staff = $this->portalService->staffForUser($request->user());
        $this->documents->assertCanView($plan, $request->user(), $staff);
    }

    private function safeFilename(string $filename): string
    {
        return preg_replace('/[^\w.\-() ]+/u', '_', $filename) ?: 'lesson-plan';
    }
}
