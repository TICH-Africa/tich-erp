<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Models\LessonPlan;
use App\Services\LessonPlanDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonPlanUploadFileController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(
        protected LessonPlanDocumentService $documents,
    ) {}

    public function __invoke(Request $request, LessonPlan $plan): StreamedResponse
    {
        $staff = $this->portalStaff($request);
        $this->documents->assertCanView($plan, $request->user(), $staff);
        abort_unless($plan->isUploadBased() && filled($plan->uploaded_file_path), 404);
        abort_unless(Storage::disk('local')->exists($plan->uploaded_file_path), 404);

        $mime = Storage::disk('local')->mimeType($plan->uploaded_file_path) ?: 'application/octet-stream';
        $filename = preg_replace('/[^\w.\-() ]+/u', '_', $plan->uploaded_file_name ?: 'lesson-plan') ?: 'lesson-plan';

        return Storage::disk('local')->response(
            $plan->uploaded_file_path,
            $filename,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }
}
