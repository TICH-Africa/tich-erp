<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\UnitAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnitAllocationService
{
    public function __construct(protected AuditService $auditService) {}
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
        UnitAllocation::query()
            ->where('unit_id', (int) $data['unit_id'])
            ->where('semester_id', (int) $data['semester_id'])
            ->where('is_active', 1)
            ->update(['is_active' => 0]);

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

        $this->auditService->log(
            'academics.unit_allocation.assigned',
            'unit_allocations',
            $allocation->id,
            null,
            [
                'unit_id' => $allocation->unit_id,
                'staff_id' => $allocation->staff_id,
                'semester_id' => $allocation->semester_id,
                'campus_id' => $allocation->campus_id,
            ],
            'Lecturer assigned to unit',
            'success',
            Auth::id(),
        );

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
        $old = $allocation->only(['unit_id', 'staff_id', 'semester_id', 'is_active']);

        $allocation->update(['is_active' => 0]);

        $this->auditService->log(
            'academics.unit_allocation.removed',
            'unit_allocations',
            $allocation->id,
            $old,
            ['is_active' => 0],
            'Unit allocation deactivated',
            'success',
            Auth::id(),
        );
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

    /**
     * Workload rows with unit lists and overload flags for HOD matrix.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function workloadMatrix(int $departmentId, ?int $semesterId = null)
    {
        $maxHours = (int) config('tich-academics.workload_limits.max_contact_hours', 18);
        $maxUnits = (int) config('tich-academics.workload_limits.max_units', 4);
        $summary = $this->workloadSummary($departmentId, $semesterId);
        $staffIds = $summary->pluck('id')->all();

        if ($staffIds === []) {
            return collect();
        }

        $unitsByStaff = DB::table('unit_allocations as ua')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->where('u.department_id', $departmentId)
            ->where('ua.is_active', 1)
            ->whereIn('ua.staff_id', $staffIds)
            ->when($semesterId, fn ($query) => $query->where('ua.semester_id', $semesterId))
            ->orderBy('u.unit_code')
            ->select([
                'ua.staff_id',
                'u.id as unit_id',
                'u.unit_code',
                'u.unit_name',
                'ua.contact_hours_assigned',
                'ua.semester_id',
            ])
            ->get()
            ->groupBy('staff_id');

        return $summary->map(function ($row) use ($unitsByStaff, $maxHours, $maxUnits) {
            $units = $unitsByStaff->get($row->id, collect());
            $totalHours = (float) $row->total_hours;
            $unitCount = (int) $row->unit_count;
            $hoursOver = $totalHours > $maxHours;
            $unitsOver = $unitCount > $maxUnits;

            return (object) [
                'id' => (int) $row->id,
                'first_name' => $row->first_name,
                'surname' => $row->surname,
                'employee_number' => $row->employee_number,
                'unit_count' => $unitCount,
                'total_hours' => $totalHours,
                'units' => $units,
                'is_overloaded' => $hoursOver || $unitsOver,
                'overload_hours' => $hoursOver,
                'overload_units' => $unitsOver,
                'max_hours' => $maxHours,
                'max_units' => $maxUnits,
            ];
        });
    }

    /**
     * Active lecturer allocations for catalog units within an intake's teaching periods.
     *
     * @param  list<int>  $unitIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, UnitAllocation>>
     */
    public function forUnitsInIntake(array $unitIds, CurriculumVersion $intake, AcademicProgram $program)
    {
        if ($unitIds === []) {
            return collect();
        }

        $semesterIds = collect(range(1, $program->totalTeachingPeriods()))
            ->map(fn (int $period) => app(ExamScheduleSyncService::class)->resolveSemesterId($intake, $period))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($semesterIds === []) {
            return collect();
        }

        return UnitAllocation::query()
            ->with(['staff', 'semester.academicYear', 'campus', 'unit'])
            ->whereIn('unit_id', $unitIds)
            ->whereIn('semester_id', $semesterIds)
            ->where('is_active', 1)
            ->orderBy('semester_id')
            ->get()
            ->groupBy('unit_id');
    }

    public function assertAllocationInProgramDepartment(UnitAllocation $allocation, AcademicProgram $program): void
    {
        abort_unless(
            (int) $allocation->unit?->department_id === (int) $program->department_id,
            404,
        );
    }
}
