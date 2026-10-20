<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Campus;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\Semester;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\UnitAllocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The unit allocation desk a department HOD works from: put approved units onto the programmes
 * of their own department, then ask Academics to approve the lecturer who will teach them.
 */
class HodUnitAllocationService
{
    public function __construct(
        protected CurriculumVersionService $versions,
        protected WorkingIntakeService $workingIntake,
        protected ExamScheduleSyncService $examScheduleSync,
        protected UnitAllocationService $allocations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function screen(Staff $staff, Request $request): array
    {
        $departmentId = (int) ($staff->department_id ?? 0);

        $programs = AcademicProgram::query()
            ->where('department_id', $departmentId)
            ->orderBy('program_name')
            ->get();

        $program = $programs->firstWhere('id', $request->integer('program')) ?? $programs->first();

        if (! $program) {
            return ['program' => null] + $this->emptyScreen();
        }

        $intakes = $this->versions->intakesForProgram($program->id);
        $intake = $this->workingIntake->resolve($program, $request);

        if (! $intake && $intakes->isNotEmpty()) {
            $intake = $intakes->first();
        }

        $mapped = $intake ? $this->versions->mappedUnits($intake) : collect();
        $mapped->load(['unit', 'block']);

        $mappedUnitIds = $mapped->pluck('unit_id')->all();

        $units = UnitAllocation::query()
            ->with(['unit', 'staff', 'semester.academicYear', 'campus', 'program', 'requestedBy', 'decidedBy'])
            ->whereHas('unit', fn ($query) => $query->where('department_id', $departmentId))
            ->where(fn ($query) => $query->where('is_active', 1)->orWhereIn('status', [
                UnitAllocation::STATUS_PENDING,
                UnitAllocation::STATUS_REJECTED,
            ]))
            ->orderByRaw("field(status, 'pending_approval', 'rejected', 'approved')")
            ->orderByDesc('id')
            ->get();

        $allocationsByUnitSemester = $units->groupBy(fn (UnitAllocation $allocation) => $allocation->unit_id.'-'.$allocation->semester_id);

        return [
            'program' => $program,
            'programs' => $programs,
            'intakes' => $intakes,
            'intake' => $intake,
            'intakeLocked' => $intake !== null && $intake->status !== 'draft',
            'mapped' => $mapped,
            'mappedByPeriod' => $mapped->groupBy('semester'),
            'semesterIdByPeriod' => $this->semesterIdsByPeriod($program, $intake),
            'availableUnits' => $this->availableUnits($departmentId, $mappedUnitIds),
            'lecturers' => $this->lecturers($departmentId),
            'campuses' => Campus::query()->orderBy('campus_name')->get(['id', 'campus_name']),
            'periods' => $this->periods($program),
            'units' => $units,
            'allocationsByUnitSemester' => $allocationsByUnitSemester,
            'pending' => $units->filter(fn (UnitAllocation $allocation) => $allocation->awaitingApproval()),
            'statusLabels' => UnitAllocation::statusLabels(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyScreen(): array
    {
        return [
            'programs' => collect(),
            'intakes' => collect(),
            'intake' => null,
            'intakeLocked' => false,
            'mapped' => collect(),
            'mappedByPeriod' => collect(),
            'semesterIdByPeriod' => [],
            'availableUnits' => collect(),
            'lecturers' => collect(),
            'campuses' => collect(),
            'periods' => [],
            'units' => collect(),
            'allocationsByUnitSemester' => collect(),
            'pending' => collect(),
            'statusLabels' => UnitAllocation::statusLabels(),
        ];
    }

    /**
     * Units the department may teach that are not on this intake yet.
     *
     * @param  list<int>  $excludeUnitIds
     * @return Collection<int, Unit>
     */
    public function availableUnits(int $departmentId, array $excludeUnitIds): Collection
    {
        return Unit::query()
            ->where('department_id', $departmentId)
            ->where('status', 'active')
            ->when($excludeUnitIds !== [], fn ($query) => $query->whereNotIn('id', $excludeUnitIds))
            ->orderBy('unit_code')
            ->get(['id', 'unit_code', 'unit_name', 'semester', 'credit_hours', 'contact_hours']);
    }

    /**
     * @return Collection<int, Staff>
     */
    public function lecturers(int $departmentId): Collection
    {
        return Staff::query()
            ->where('department_id', $departmentId)
            ->where('is_teaching_staff', 1)
            ->where('employment_status', 'active')
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number']);
    }

    /**
     * @return list<array{period: int, label: string}>
     */
    public function periods(AcademicProgram $program): array
    {
        return collect(range(1, max(1, $program->totalTeachingPeriods())))
            ->map(fn (int $period) => ['period' => $period, 'label' => $program->periodLabel($period)])
            ->all();
    }

    /**
     * Teaching period to calendar semester, the same way the Academics curriculum page resolves it.
     *
     * @return array<int, int|null>
     */
    public function semesterIdsByPeriod(AcademicProgram $program, ?CurriculumVersion $intake): array
    {
        if (! $intake) {
            return [];
        }

        $numbers = range(1, max(1, $program->totalTeachingPeriods()));

        $ownYear = $intake->academic_year_id
            ? Semester::query()
                ->where('academic_year_id', $intake->academic_year_id)
                ->whereIn('semester_number', $numbers)
                ->pluck('id', 'semester_number')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        // Ordered oldest first so the newest semester wins when a number repeats across years.
        $fallback = Semester::query()
            ->whereIn('semester_number', $numbers)
            ->orderBy('id')
            ->pluck('id', 'semester_number')
            ->map(fn ($id) => (int) $id)
            ->all();

        return collect($numbers)
            ->mapWithKeys(fn (int $period) => [$period => $ownYear[$period] ?? $fallback[$period] ?? null])
            ->all();
    }

    /**
     * Put an approved unit from the department catalog onto one of the department's intakes.
     */
    public function mapUnit(User $user, Staff $staff, Request $request): CurriculumVersion
    {
        $program = $this->assertProgramInDepartment($staff, $request->integer('program'));
        $intake = $this->assertIntakeOfProgram($program, $request->integer('intake'));
        $unit = $this->assertUnitInDepartment($staff, $request->integer('unit_id'));

        $period = (int) ($request->integer('period') ?: $unit->semester ?: 1);

        $this->versions->addUnitsToPeriod(
            $user,
            $this->hubFor($program),
            $intake,
            [(int) $unit->id],
            $period,
            null,
            $request
        );

        return $intake;
    }

    /**
     * Ask Academics to approve a lecturer for a unit on one of the department's programmes.
     */
    public function requestAllocation(User $user, Staff $staff, Request $request): UnitAllocation
    {
        $program = $this->assertProgramInDepartment($staff, $request->integer('program'));
        $intake = $this->assertIntakeOfProgram($program, $request->integer('intake'));
        $unit = $this->assertUnitInDepartment($staff, $request->integer('unit_id'));
        $lecturer = $this->assertLecturerInDepartment($staff, $request->integer('staff_id'));
        $campus = Campus::query()->findOrFail($request->integer('campus_id'));

        $period = (int) ($request->integer('period') ?: $unit->semester ?: 1);
        $semesterId = $this->examScheduleSync->resolveSemesterId($intake, $period);

        abort_unless($semesterId, 422, 'Could not resolve a semester for this intake and teaching period.');

        return $this->allocations->assign([
            'unit_id' => $unit->id,
            'program_id' => $program->id,
            'staff_id' => $lecturer->id,
            'semester_id' => $semesterId,
            'campus_id' => $campus->id,
            'contact_hours_assigned' => (int) ($request->integer('contact_hours_assigned') ?: $unit->contact_hours ?: 0),
            'is_coordinator' => $request->boolean('is_coordinator'),
        ], needsApproval: true);
    }

    public function withdrawAllocation(User $user, Staff $staff, UnitAllocation $allocation): void
    {
        $this->assertUnitInDepartment($staff, (int) $allocation->unit_id);
        abort_unless($allocation->awaitingApproval(), 422, 'Only a request awaiting Academics can be withdrawn.');

        $this->allocations->withdraw($allocation, $user);
    }

    private function assertProgramInDepartment(Staff $staff, int $programId): AcademicProgram
    {
        $program = AcademicProgram::query()
            ->where('id', $programId)
            ->where('department_id', (int) $staff->department_id)
            ->first();

        abort_unless($program, 403, 'That programme is outside your department.');

        return $program;
    }

    private function assertIntakeOfProgram(AcademicProgram $program, int $intakeId): CurriculumVersion
    {
        $intake = CurriculumVersion::query()
            ->where('id', $intakeId)
            ->where('program_id', $program->id)
            ->first();

        abort_unless($intake, 403, 'That intake does not belong to this programme.');

        return $intake;
    }

    private function assertUnitInDepartment(Staff $staff, int $unitId): Unit
    {
        $unit = Unit::query()
            ->where('id', $unitId)
            ->where('department_id', (int) $staff->department_id)
            ->first();

        abort_unless($unit, 403, 'That unit is outside your department.');

        return $unit;
    }

    private function assertLecturerInDepartment(Staff $staff, int $lecturerId): Staff
    {
        $lecturer = Staff::query()
            ->where('id', $lecturerId)
            ->where('department_id', (int) $staff->department_id)
            ->where('is_teaching_staff', 1)
            ->where('employment_status', 'active')
            ->first();

        abort_unless($lecturer, 403, 'Assign a lecturer who belongs to your department.');

        return $lecturer;
    }

    private function hubFor(AcademicProgram $program): Department
    {
        $department = Department::query()->findOrFail((int) $program->department_id);

        return $department->academicsHub() ?? $department;
    }
}
