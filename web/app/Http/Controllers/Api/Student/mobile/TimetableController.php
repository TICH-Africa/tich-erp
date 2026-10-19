<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Services\StudentPortalDashboardService;
use App\Services\StudentRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimetableController extends Controller
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

        return response()->json([
            'student' => $this->serializeStudent($student, $biodata),
            'timetable' => $this->serializeTimetable($portalData),
        ]);
    }
}
