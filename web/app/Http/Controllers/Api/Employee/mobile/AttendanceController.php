<?php

namespace App\Http\Controllers\Api\Employee\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\mobile\Concerns\ResolvesPortalStaff;
use App\Services\StaffAttendanceService;
use App\Services\StaffClockInLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ResolvesPortalStaff;

    public function __construct(
        protected StaffAttendanceService $attendance,
        protected StaffClockInLocationService $locations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $today = $this->attendance->todayRecord($staff);
        $geofence = $this->locations->geofenceForStaff($staff);

        return response()->json([
            'today' => $today ? $this->serializeAttendance($today) : null,
            'recent' => $this->attendance->recentRecords($staff)
                ->map(fn ($r) => $this->serializeAttendance($r))
                ->values(),
            'clock_in_status' => $this->attendance->clockInPromptStatus($staff),
            'require_location' => (bool) config('hr-attendance.require_location', true),
            'max_location_accuracy' => (int) config('hr-attendance.max_accuracy_meters', 2000),
            'geofence' => $geofence,
        ]);
    }

    public function clockIn(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $data = $request->validate([
            'is_off_campus' => ['nullable', 'boolean'],
            'field_project_name' => ['nullable', 'string', 'max:300'],
            'location_lat_long' => ['nullable', 'string', 'max:100'],
            'clock_in_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'clock_in_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'clock_in_accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $data['is_off_campus'] = $request->boolean('is_off_campus');

        try {
            $this->attendance->clockIn($staff, $data);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'location_required' => $exception->getMessage() === StaffClockInLocationService::LOCATION_REQUIRED_MESSAGE,
            ], 422);
        }

        return response()->json([
            'message' => 'Clocked in successfully.',
            'today' => $this->index($request)->getData(true)['today'],
        ]);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);

        try {
            $this->attendance->clockOut($staff, $request->input('notes'));
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Clocked out successfully.',
            'today' => $this->index($request)->getData(true)['today'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAttendance($record): array
    {
        $date = optional($record->attendance_date)?->format('Y-m-d');
        $clockIn = $record->clock_in_time
            ? ($date.' '.$record->clock_in_time)
            : null;
        $clockOut = $record->clock_out_time
            ? ($date.' '.$record->clock_out_time)
            : null;

        return [
            'id' => $record->id,
            'attendance_date' => $date,
            'clock_in_at' => $clockIn,
            'clock_out_at' => $clockOut,
            'is_off_campus' => (bool) $record->is_off_campus,
            'field_project_name' => $record->field_project_name ?? null,
            'location_verified' => method_exists($record, 'hasVerifiedClockInLocation')
                ? $record->hasVerifiedClockInLocation()
                : null,
            'status' => $record->hr_review_status ?? $record->status ?? null,
        ];
    }
}
