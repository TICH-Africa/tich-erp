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
            'today' => $today ? [
                'id' => $today->id,
                'attendance_date' => optional($today->attendance_date)?->format('Y-m-d'),
                'clock_in_at' => optional($today->clock_in_at)?->toIso8601String(),
                'clock_out_at' => optional($today->clock_out_at)?->toIso8601String(),
                'is_off_campus' => (bool) $today->is_off_campus,
                'field_project_name' => $today->field_project_name,
                'location_verified' => method_exists($today, 'hasVerifiedClockInLocation')
                    ? $today->hasVerifiedClockInLocation()
                    : null,
                'status' => $today->status ?? null,
            ] : null,
            'recent' => $this->attendance->recentRecords($staff)->map(fn ($r) => [
                'id' => $r->id,
                'attendance_date' => optional($r->attendance_date)?->format('Y-m-d'),
                'clock_in_at' => optional($r->clock_in_at)?->toIso8601String(),
                'clock_out_at' => optional($r->clock_out_at)?->toIso8601String(),
                'is_off_campus' => (bool) $r->is_off_campus,
                'status' => $r->status ?? null,
            ])->values(),
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
}
