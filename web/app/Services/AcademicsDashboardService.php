<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Collection;

class AcademicsDashboardService
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected WorkloadBalancingService $workload,
    ) {}

    /**
     * @return array<string, int>
     */
    public function stats(User $user, Department $hub): array
    {
        $departmentIds = $this->access->scopeDepartmentIds($hub);

        return [
            'departments' => count($departmentIds),
            'learning_departments' => count($departmentIds),
            'programs' => $this->access->programsQueryForHub($user, $hub)->count(),
            'units' => Unit::query()
                ->where(function ($builder) use ($departmentIds) {
                    $builder->whereIn('department_id', $departmentIds)
                        ->orWhereHas('program', fn ($q) => $q->whereIn('department_id', $departmentIds));
                })
                ->count(),
            'pending_units' => Unit::query()
                ->whereIn('department_id', $departmentIds)
                ->where('status', 'pending_registry')
                ->count(),
            'draft_versions' => CurriculumVersion::query()
                ->whereHas('program', fn ($q) => $q->whereIn('department_id', $departmentIds))
                ->whereIn('status', ['draft', 'pending_registry', 'pending_ceo'])
                ->count(),
            'published_versions' => CurriculumVersion::query()
                ->whereHas('program', fn ($q) => $q->whereIn('department_id', $departmentIds))
                ->where('status', 'published')
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function statsForLearningDepartment(User $user, Department $department): array
    {
        return [
            'departments' => 1,
            'learning_departments' => 1,
            'programs' => AcademicProgram::query()
                ->where('department_id', $department->id)
                ->where('status', 'active')
                ->count(),
            'units' => Unit::query()
                ->where('department_id', $department->id)
                ->count(),
            'pending_units' => Unit::query()
                ->where('department_id', $department->id)
                ->where('status', 'pending_registry')
                ->count(),
            'draft_versions' => CurriculumVersion::query()
                ->whereHas('program', fn ($q) => $q->where('department_id', $department->id))
                ->whereIn('status', ['draft', 'pending_registry', 'pending_ceo'])
                ->count(),
            'published_versions' => CurriculumVersion::query()
                ->whereHas('program', fn ($q) => $q->where('department_id', $department->id))
                ->where('status', 'published')
                ->count(),
        ];
    }

    public function workloadStats(Department $hub): array
    {
        $departmentId = $hub->academicsScopeDepartmentIds()[0] ?? $hub->id;
        $workload = $this->workload->getDepartmentWorkload($departmentId);

        $overCapacity = $workload->where('over_capacity', true)->count();
        $totalHours = $workload->sum('total_hours');

        return [
            'staff_count' => $workload->count(),
            'over_capacity_count' => $overCapacity,
            'total_weekly_hours' => $totalHours,
        ];
    }

    public function workloadStatsForDepartment(int $departmentId, ?int $semesterId = null): array
    {
        $workload = $this->workload->getDepartmentWorkload($departmentId, $semesterId);

        $overCapacity = $workload->where('over_capacity', true)->count();
        $totalHours = $workload->sum('total_hours');

        return [
            'staff_count' => $workload->count(),
            'over_capacity_count' => $overCapacity,
            'total_weekly_hours' => $totalHours,
        ];
    }

    public function pendingUnitsForHub(Department $hub): Collection
    {
        $departmentIds = $this->access->scopeDepartmentIds($hub);

        return Unit::query()
            ->where(function ($builder) use ($departmentIds) {
                $builder->whereIn('department_id', $departmentIds)
                    ->orWhereHas('program', fn ($q) => $q->whereIn('department_id', $departmentIds));
            })
            ->where('status', 'pending_registry')
            ->with(['department:id,dept_name', 'program:id,program_name,department_id'])
            ->orderBy('submitted_at')
            ->get();
    }
}
