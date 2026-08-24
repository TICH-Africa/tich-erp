<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AcademicsDashboardService
{
    public function __construct(protected AcademicsAccessService $access) {}

    /**
     * @return array<string, mixed>
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
            'chart' => $this->chartData($departmentIds),
        ];
    }

    /**
     * @param list<int> $departmentIds
     *
     * @return array<string, mixed>
     */
    private function chartData(array $departmentIds): array
    {
        $programsByDepartment = AcademicProgram::query()
            ->select('department_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->whereIn('department_id', $departmentIds)
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->get()
            ->keyBy('department_id');

        $departmentLabels = Department::query()
            ->whereIn('id', $departmentIds)
            ->orderBy('dept_name')
            ->get(['id', 'dept_name', 'dept_code'])
            ->mapWithKeys(fn (Department $department) => [(int) $department->id => $department->dept_code ?? $department->dept_name]);

        $programStatus = AcademicProgram::query()
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->whereIn('department_id', $departmentIds)
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status => (int) $row->total]);

        $unitStatus = Unit::query()
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->where(function ($builder) use ($departmentIds) {
                $builder->whereIn('department_id', $departmentIds)
                    ->orWhereHas('program', fn ($q) => $q->whereIn('department_id', $departmentIds));
            })
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status => (int) $row->total]);

        $versionStatus = CurriculumVersion::query()
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->whereHas('program', fn ($q) => $q->whereIn('department_id', $departmentIds))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status => (int) $row->total]);

        return [
            'programsByDepartment' => [
                'labels' => $departmentLabels->values()->all(),
                'data' => $programsByDepartment->map(fn ($row) => (int) $row->total)->values()->all(),
            ],
            'programStatus' => [
                'labels' => $programStatus->keys()->values()->all(),
                'data' => $programStatus->values()->all(),
            ],
            'unitStatus' => [
                'labels' => $unitStatus->keys()->values()->all(),
                'data' => $unitStatus->values()->all(),
            ],
            'versionStatus' => [
                'labels' => $versionStatus->keys()->values()->all(),
                'data' => $versionStatus->values()->all(),
            ],
        ];
    }
}
