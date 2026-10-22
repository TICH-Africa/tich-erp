<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\UnitAllocation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnitAllocationService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * @return Collection<int, UnitAllocation>
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

    /**
     * Assign a lecturer to a unit. Academics assignments go live at once; an HOD request waits
     * on the Academics approval inbox and stays switched off until then, so no lecturer,
     * timetable or attendance screen can pick it up early.
     */
    public function assign(array $data, bool $needsApproval = false): UnitAllocation
    {
        $unitId = (int) $data['unit_id'];
        $semesterId = (int) $data['semester_id'];
        $replacedStaffIds = [];

        if ($needsApproval) {
            $existing = $this->pendingDuplicate($data);

            if ($existing) {
                return $existing;
            }
        } else {
            $replacedStaffIds = $this->retireRivals($unitId, $semesterId);
        }

        $allocation = UnitAllocation::query()->create([
            'unit_id' => $unitId,
            'program_id' => isset($data['program_id']) ? (int) $data['program_id'] : null,
            'staff_id' => (int) $data['staff_id'],
            'semester_id' => $semesterId,
            'campus_id' => (int) $data['campus_id'],
            'is_coordinator' => ! empty($data['is_coordinator']),
            'contact_hours_assigned' => (int) ($data['contact_hours_assigned'] ?? 0),
            'status' => $needsApproval ? UnitAllocation::STATUS_PENDING : UnitAllocation::STATUS_APPROVED,
            'submitted_by' => $needsApproval ? Auth::id() : null,
            'submitted_at' => $needsApproval ? now() : null,
            'approved_by' => $needsApproval ? null : Auth::id(),
            'approved_at' => $needsApproval ? null : now(),
            'is_active' => $needsApproval ? 0 : 1,
        ]);

        if ($needsApproval) {
            $this->auditService->log(
                'academics.unit_allocation.requested',
                'unit_allocations',
                $allocation->id,
                null,
                [
                    'unit_id' => $allocation->unit_id,
                    'staff_id' => $allocation->staff_id,
                    'semester_id' => $allocation->semester_id,
                    'status' => UnitAllocation::STATUS_PENDING,
                ],
                'Lecturer allocation requested by the department HOD',
                'success',
                Auth::id(),
            );

            return $allocation;
        }

        $this->syncTimetableStaff($allocation, $replacedStaffIds);

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

    /**
     * Switch off the live allocation for the same unit and semester before a new one takes over.
     *
     * @return list<int> the lecturers who were teaching the unit
     */
    private function retireRivals(int $unitId, int $semesterId): array
    {
        $rivals = UnitAllocation::query()
            ->where('unit_id', $unitId)
            ->where('semester_id', $semesterId)
            ->where('is_active', 1);

        $staffIds = $rivals->pluck('staff_id')->map(fn ($id) => (int) $id)->all();

        if ($staffIds !== []) {
            $rivals->update(['is_active' => 0]);
        }

        return $staffIds;
    }

    /**
     * Academics accepts an HOD request: it becomes the live allocation for that unit and semester.
     */
    public function approve(UnitAllocation $allocation, User $approver): UnitAllocation
    {
        abort_unless($allocation->awaitingApproval(), 422, 'Only a pending allocation can be approved.');

        DB::transaction(function () use ($allocation, $approver): void {
            $replacedStaffIds = $this->retireRivals((int) $allocation->unit_id, (int) $allocation->semester_id);

            $allocation->update([
                'status' => UnitAllocation::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_comments' => null,
                'is_active' => 1,
            ]);

            $this->syncTimetableStaff($allocation, $replacedStaffIds);
        });

        $this->auditService->log(
            'academics.unit_allocation.approved',
            'unit_allocations',
            $allocation->id,
            ['status' => UnitAllocation::STATUS_PENDING],
            ['status' => UnitAllocation::STATUS_APPROVED, 'is_active' => 1],
            'Lecturer allocation approved',
            'success',
            $approver->id,
        );

        return $allocation;
    }

    public function reject(UnitAllocation $allocation, User $approver, ?string $comments): UnitAllocation
    {
        abort_unless($allocation->awaitingApproval(), 422, 'Only a pending allocation can be rejected.');

        $allocation->update([
            'status' => UnitAllocation::STATUS_REJECTED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_comments' => $comments,
            'is_active' => 0,
        ]);

        $this->auditService->log(
            'academics.unit_allocation.rejected',
            'unit_allocations',
            $allocation->id,
            ['status' => UnitAllocation::STATUS_PENDING],
            ['status' => UnitAllocation::STATUS_REJECTED],
            'Lecturer allocation rejected',
            'warning',
            $approver->id,
        );

        return $allocation;
    }

    /**
     * Drop a request the HOD no longer wants sent, or retire a live allocation.
     */
    public function withdraw(UnitAllocation $allocation, User $user): void
    {
        $before = $allocation->status;

        $allocation->update(['status' => UnitAllocation::STATUS_REMOVED, 'is_active' => 0]);

        $this->auditService->log(
            'academics.unit_allocation.withdrawn',
            'unit_allocations',
            $allocation->id,
            ['status' => $before],
            ['status' => UnitAllocation::STATUS_REMOVED],
            'Unit allocation withdrawn',
            'success',
            $user->id,
        );
    }

    /**
     * A request the HOD sent by mistake should not sit in the Academics inbox.
     */
    public function pendingDuplicate(array $data): ?UnitAllocation
    {
        return UnitAllocation::query()
            ->where('unit_id', (int) $data['unit_id'])
            ->where('staff_id', (int) $data['staff_id'])
            ->where('semester_id', (int) $data['semester_id'])
            ->where('status', UnitAllocation::STATUS_PENDING)
            ->first();
    }

    /**
     * @param list<int> $replacedStaffIds lecturers who were teaching this unit and are now switched off
     */
    public function syncTimetableStaff(UnitAllocation $allocation, array $replacedStaffIds = []): void
    {
        // A class already on the timetable keeps the lecturer it was built with, so when somebody
        // takes the unit over his classes have to move with it - not only the empty slots.
        DB::table('program_timetable_sessions')
            ->where('unit_id', $allocation->unit_id)
            ->where(function ($query) use ($allocation, $replacedStaffIds) {
                $query->whereNull('staff_id')
                    ->orWhere('staff_id', $allocation->staff_id);

                if ($replacedStaffIds !== []) {
                    $query->orWhereIn('staff_id', $replacedStaffIds);
                }
            })
            ->update(['staff_id' => $allocation->staff_id]);
    }

    public function remove(UnitAllocation $allocation): void
    {
        $old = $allocation->only(['unit_id', 'staff_id', 'semester_id', 'is_active', 'status']);

        $allocation->update(['is_active' => 0, 'status' => UnitAllocation::STATUS_REMOVED]);

        $this->auditService->log(
            'academics.unit_allocation.removed',
            'unit_allocations',
            $allocation->id,
            $old,
            ['is_active' => 0, 'status' => UnitAllocation::STATUS_REMOVED],
            'Unit allocation deactivated',
            'success',
            Auth::id(),
        );
    }

    /**
     * @return Collection<int, object>
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
     * @return Collection<int, object>
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
     * @return Collection<int, Collection<int, UnitAllocation>>
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
