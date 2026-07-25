<?php

namespace App\Services;

use App\Models\UnitAllocation;
use Illuminate\Support\Facades\DB;

class UnitAllocationService
{
    /**
     * @return \Illuminate\Support\Collection<int, UnitAllocation>
     */
    public function forDepartment(int $departmentId, ?int $semesterId = null)
    {
        $query = UnitAllocation::query()
            ->with(['unit', 'staff', 'semester.academicYear', 'campus'])
            ->whereHas('unit', fn ($unitQuery) => $unitQuery->where('department_id', $departmentId));

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        return $query->orderBy('semester_id')->orderBy('unit_id')->get();
    }

    public function assign(array $data): UnitAllocation
    {
        $allocation = UnitAllocation::query()->create([
            'unit_id' => (int) $data['unit_id'],
            'staff_id' => (int) $data['staff_id'],
            'semester_id' => (int) $data['semester_id'],
            'campus_id' => (int) $data['campus_id'],
            'is_coordinator' => ! empty($data['is_coordinator']),
            'contact_hours_assigned' => (int) ($data['contact_hours_assigned'] ?? 0),
            'is_active' => 1,
        ]);

        $this->syncTimetableStaff($allocation);

        return $allocation;
    }

    public function syncTimetableStaff(UnitAllocation $allocation): void
    {
        DB::table('program_timetable_sessions')
            ->where('unit_id', $allocation->unit_id)
            ->where(function ($query) use ($allocation) {
                $query->whereNull('staff_id')
                    ->orWhere('staff_id', $allocation->staff_id);
            })
            ->update(['staff_id' => $allocation->staff_id]);
    }

    public function remove(UnitAllocation $allocation): void
    {
        $allocation->update(['is_active' => 0]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function workloadSummary(int $departmentId, ?int $semesterId = null)
    {
        return DB::table('unit_allocations as ua')
            ->join('staff as s', 's.id', '=', 'ua.staff_id')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->where('u.department_id', $departmentId)
            ->where('ua.is_active', 1)
            ->when($semesterId, fn ($query) => $query->where('ua.semester_id', $semesterId))
            ->groupBy('s.id', 's.first_name', 's.surname', 's.employee_number')
            ->selectRaw('s.id, s.first_name, s.surname, s.employee_number, COUNT(*) as unit_count, SUM(ua.contact_hours_assigned) as total_hours')
            ->orderBy('s.surname')
            ->get();
    }
}
