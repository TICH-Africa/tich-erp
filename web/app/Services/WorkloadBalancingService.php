<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\UnitAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkloadBalancingService
{
    public const MAX_WEEKLY_HOURS = 25;
    public const MAX_DAILY_HOURS = 5;

    public function getDepartmentWorkload(int $departmentId, ?int $semesterId = null): Collection
    {
        $query = DB::table('unit_allocations as ua')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->join('staff as s', 's.id', '=', 'ua.staff_id')
            ->where('u.department_id', $departmentId)
            ->where('ua.is_active', 1);

        if ($semesterId) {
            $query->where('ua.semester_id', $semesterId);
        }

        $results = $query->selectRaw('s.id as staff_id, s.first_name, s.surname, s.job_title, s.email, SUM(ua.contact_hours_assigned) as total_hours, COUNT(ua.unit_id) as units_assigned')
            ->groupBy('s.id', 's.first_name', 's.surname', 's.job_title', 's.email')
            ->get();

        return $results->map(function ($row) {
            return [
                'staff_id' => $row->staff_id,
                'full_name' => trim($row->first_name . ' ' . $row->surname),
                'job_title' => $row->job_title,
                'email' => $row->email,
                'total_hours' => (int) $row->total_hours,
                'units_assigned' => (int) $row->units_assigned,
                'at_capacity' => (int) $row->total_hours >= self::MAX_WEEKLY_HOURS,
                'over_capacity' => (int) $row->total_hours > self::MAX_WEEKLY_HOURS,
            ];
        })->sortBy('total_hours');
    }

    public function canAssignUnit(Staff $staff, int $contactHours): bool
    {
        $currentHours = $this->getCurrentWeeklyHours($staff->id);

        return ($currentHours + $contactHours) <= self::MAX_WEEKLY_HOURS;
    }

    public function getCurrentWeeklyHours(int $staffId, ?int $semesterId = null): int
    {
        $query = UnitAllocation::query()
            ->where('staff_id', $staffId)
            ->where('is_active', 1);

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        return (int) $query->sum('contact_hours_assigned');
    }

    public function getWorkloadWarnings(Collection $workload): Collection
    {
        return $workload->filter(fn ($row) => $row['over_capacity'])
            ->map(fn ($row) => [
                'staff_id' => $row['staff_id'],
                'message' => $row['full_name'] . ' exceeds workload cap (' . $row['total_hours'] . '/' . self::MAX_WEEKLY_HOURS . ' hours)',
            ]);
    }
}