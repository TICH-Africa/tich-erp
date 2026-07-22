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
    public function stats(User $user): array
    {
        $departmentIds = $this->access->learningDepartmentsForUser($user)->pluck('id')->all();

        return [
            'departments' => count($departmentIds),
            'pending_departments' => Department::query()
                ->whereIn('id', $departmentIds)
                ->where('approval_status', 'pending_ceo')
                ->count(),
            'units' => Unit::query()->whereIn('department_id', $departmentIds)->count(),
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
