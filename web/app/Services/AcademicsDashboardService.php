<?php

namespace App\Services;

use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;

class AcademicsDashboardService
{
    public function __construct(protected AcademicsAccessService $access) {}

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
}
