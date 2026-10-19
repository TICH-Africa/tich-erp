<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\Staff;
use App\Models\UnitAllocation;
use App\Services\AttendanceSessionGenerationService;
use App\Services\LessonPlanApprovalService;
use App\Services\StaffMobilePortalSerializer;
use App\Services\StaffTeachingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(
        protected StaffMobilePortalSerializer $serializer,
        protected StaffTeachingService $teaching,
        protected AttendanceSessionGenerationService $attendanceGeneration,
        protected LessonPlanApprovalService $lessonPlanApprovals,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->serializer->attendanceIndex($this->portalStaff($request)));
    }

    public function show(Request $request, AttendanceSession $session): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $this->assertCanViewSession($session, $staff);

        return response()->json($this->serializer->attendanceShow($session));
    }

    public function sync(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $result = $this->attendanceGeneration->syncForStaff($staff);

        return response()->json([
            'message' => ($result['created'] ?? 0) > 0
                ? (($result['created']).' attendance session(s) created from your timetable.')
                : 'Attendance sessions are already up to date with your timetable.',
            'created' => (int) ($result['created'] ?? 0),
            ...$this->serializer->attendanceIndex($staff),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'session_date' => ['required', 'date'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:200'],
        ]);

        $allocation = UnitAllocation::query()->findOrFail((int) $validated['allocation_id']);
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

        if (! $this->lessonPlanApprovals->hasApprovedPlanForSession($allocation, $validated['session_date'])) {
            return response()->json([
                'message' => 'An HOD-approved lesson plan matching this unit and date is required before creating a class session.',
            ], 422);
        }

        $session = $this->teaching->createAttendanceSession($staff, $allocation, $validated);

        return response()->json($this->serializer->attendanceShow($session), 201);
    }

    public function save(Request $request, AttendanceSession $session): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'present' => ['array'],
            'present.*' => ['integer'],
            'lock' => ['sometimes', 'boolean'],
        ]);

        $present = $validated['present'] ?? [];
        $this->teaching->saveAttendance($session, $staff, $present);

        if ($request->boolean('lock')) {
            if (! $session->fresh()->signed_sheet_image_path) {
                return response()->json([
                    'message' => 'Upload a photo of the signed attendance sheet before submitting.',
                ], 422);
            }
            $this->teaching->lockAttendanceSession($session->fresh(), $staff);
        }

        return response()->json($this->serializer->attendanceShow($session->fresh()));
    }

    public function uploadSheet(Request $request, AttendanceSession $session): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $this->assertCanViewSession($session, $staff);

        $request->validate([
            'signed_sheet' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ]);

        $this->teaching->uploadSignedSheet($session, $staff, $request->file('signed_sheet'));

        return response()->json($this->serializer->attendanceShow($session->fresh()));
    }

    public function uploadClassPhoto(Request $request, AttendanceSession $session): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $this->assertCanViewSession($session, $staff);

        $request->validate([
            'class_photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ]);

        $this->teaching->uploadClassPhoto($session, $staff, $request->file('class_photo'));

        return response()->json($this->serializer->attendanceShow($session->fresh()));
    }

    protected function assertCanViewSession(AttendanceSession $session, Staff $staff): void
    {
        $session->loadMissing('allocation');
        abort_unless(
            (int) $session->recorded_by === (int) $staff->id
            || (int) ($session->allocation?->staff_id) === (int) $staff->id,
            403
        );
    }
}
