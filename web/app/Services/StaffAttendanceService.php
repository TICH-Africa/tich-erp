<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StaffAttendanceService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function todayRecord(Staff $staff): ?StaffAttendance
    {
        return StaffAttendance::query()
            ->where('staff_id', $staff->id)
            ->whereDate('attendance_date', today())
            ->first();
    }

    /**
     * @return Collection<int, StaffAttendance>
     */
    public function recentRecords(Staff $staff, int $limit = 14): Collection
    {
        return StaffAttendance::query()
            ->where('staff_id', $staff->id)
            ->orderByDesc('attendance_date')
            ->limit($limit)
            ->get();
    }

    public function clockIn(Staff $staff, array $data = []): StaffAttendance
    {
        $existing = $this->todayRecord($staff);

        if ($existing && $existing->clock_in_time && ! $existing->clock_out_time) {
            throw new \RuntimeException('You are already clocked in for today.');
        }

        if ($existing && $existing->clock_out_time) {
            throw new \RuntimeException('You have already completed today\'s attendance.');
        }

        $record = StaffAttendance::query()->updateOrCreate(
            [
                'staff_id' => $staff->id,
                'attendance_date' => today()->toDateString(),
            ],
            [
                'clock_in_time' => now()->format('H:i:s'),
                'is_present' => true,
                'is_off_campus' => ! empty($data['is_off_campus']),
                'field_project_name' => $data['field_project_name'] ?? null,
                'location_lat_long' => $data['location_lat_long'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $staff->id,
            ]
        );

        $this->auditService->log(
            'hr.attendance.clock_in',
            'staff_attendance',
            $record->id,
            null,
            [
                'staff_id' => $staff->id,
                'attendance_date' => $record->attendance_date?->toDateString(),
                'clock_in_time' => (string) $record->clock_in_time,
                'is_off_campus' => (bool) $record->is_off_campus,
            ],
        );

        return $record->fresh();
    }

    public function clockOut(Staff $staff, ?string $notes = null): StaffAttendance
    {
        $record = $this->todayRecord($staff);

        if (! $record || ! $record->clock_in_time) {
            throw new \RuntimeException('You must clock in before clocking out.');
        }

        if ($record->clock_out_time) {
            throw new \RuntimeException('You have already clocked out for today.');
        }

        $clockOut = now();
        $clockIn = Carbon::parse($record->attendance_date->format('Y-m-d').' '.$record->clock_in_time);
        $workHours = round($clockIn->floatDiffInHours($clockOut), 2);

        $record->update([
            'clock_out_time' => $clockOut->format('H:i:s'),
            'work_hours' => $workHours,
            'notes' => $notes ?? $record->notes,
        ]);

        $this->auditService->log(
            'hr.attendance.clock_out',
            'staff_attendance',
            $record->id,
            null,
            [
                'staff_id' => $staff->id,
                'clock_out_time' => (string) $record->clock_out_time,
                'work_hours' => (float) $record->work_hours,
            ],
        );

        return $record->fresh();
    }
}
