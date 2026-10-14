<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Models\SpecialExamRequest;
use App\Models\StudentTranscriptRequest;
use App\Models\SupplementaryExamRequest;
use App\Services\StudentPortalDashboardService;
use App\Services\StudentRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AcademicsController extends Controller
{
    use ResolvesPortalStudent;
    use SerializesStudentPortal;

    public function __construct(
        protected StudentPortalDashboardService $dashboard,
        protected StudentRecordService $studentRecords,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $biodata = $this->studentRecords->biodata360($student);
        $portalData = $this->dashboard->forStudent($student, $biodata);

        $transcriptRequests = [];
        $specialExamRequests = [];
        $supplementaryExamRequests = [];

        if (Schema::hasTable('student_transcript_requests')) {
            $transcriptRequests = StudentTranscriptRequest::query()
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'status' => $r->status,
                    'delivery_method' => $r->delivery_method,
                    'student_notes' => $r->student_notes,
                    'created_at' => optional($r->created_at)?->toIso8601String(),
                ])
                ->all();
        }

        if (Schema::hasTable('special_exam_requests')) {
            $specialExamRequests = SpecialExamRequest::query()
                ->with(['unit', 'semester'])
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(30)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'status' => $r->status,
                    'reason' => $r->reason,
                    'unit_code' => $r->unit?->unit_code,
                    'unit_name' => $r->unit?->unit_name,
                    'semester_label' => $r->semester?->semester_label,
                    'created_at' => optional($r->created_at)?->toIso8601String(),
                ])
                ->all();
        }

        if (Schema::hasTable('supplementary_requests')) {
            $supplementaryExamRequests = SupplementaryExamRequest::query()
                ->with(['unit', 'semester'])
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(30)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'status' => $r->status,
                    'reason' => $r->reason,
                    'unit_code' => $r->unit?->unit_code,
                    'unit_name' => $r->unit?->unit_name,
                    'semester_label' => $r->semester?->semester_label,
                    'created_at' => optional($r->created_at)?->toIso8601String(),
                ])
                ->all();
        }

        return response()->json([
            'student' => $this->serializeStudent($student, $biodata),
            'academics' => $this->serializeAcademics($portalData),
            'transcript_requests' => $transcriptRequests,
            'special_exam_requests' => $specialExamRequests,
            'supplementary_exam_requests' => $supplementaryExamRequests,
        ]);
    }
}
