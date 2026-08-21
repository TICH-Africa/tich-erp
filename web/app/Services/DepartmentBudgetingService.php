<?php

namespace App\Services;

use App\Models\Administration\BudgetRequest;
use App\Models\Administration\PlanningCycle;
use App\Models\Department;
use App\Models\User;
use App\Services\Administration\AdministrationService;
use App\Services\Sidebar\AdministrationSidebarNotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DepartmentBudgetingService
{
    /**
     * Module-scoped budgeting: URL prefix → department + layout.
     *
     * @var array<string, array{dept_code: string, layout: string, content_section: string, dashboard_route: string}>
     */
    public const MODULES = [
        'hr' => [
            'dept_code' => 'HR',
            'layout' => 'layouts.hr',
            'content_section' => 'hr-content',
            'dashboard_route' => 'hr.dashboard',
        ],
        'finance' => [
            'dept_code' => 'FIN',
            'layout' => 'layouts.finance',
            'content_section' => 'finance-content',
            'dashboard_route' => 'finance.dashboard',
        ],
        'administration' => [
            'dept_code' => 'ADM',
            'layout' => 'layouts.administration',
            'content_section' => 'administration-content',
            'dashboard_route' => 'administration.dashboard',
        ],
        'ict' => [
            'dept_code' => 'ICTO',
            'layout' => 'layouts.ict',
            'content_section' => 'ict-content',
            'dashboard_route' => 'ict.dashboard',
        ],
        'qa' => [
            'dept_code' => 'QA',
            'layout' => 'layouts.qa',
            'content_section' => 'qa-content',
            'dashboard_route' => 'qa.dashboard',
        ],
        'procurement' => [
            'dept_code' => 'PRC',
            'layout' => 'layouts.procurement',
            'content_section' => 'procurement-content',
            'dashboard_route' => 'procurement.dashboard',
        ],
        'research' => [
            'dept_code' => 'RES',
            'layout' => 'layouts.research',
            'content_section' => 'research-content',
            'dashboard_route' => 'research.dashboard',
        ],
        'academics' => [
            'dept_code' => 'ACAD',
            'layout' => 'layouts.academics',
            'content_section' => 'academics-content',
            'dashboard_route' => 'departments.academics.dashboard',
        ],
    ];

    public function __construct(
        protected AdministrationService $administration,
    ) {}

    /**
     * @return array{dept_code: string, layout: string, content_section: string, dashboard_route: string, key: string}
     */
    public function moduleContext(string $module): array
    {
        if (! isset(self::MODULES[$module])) {
            abort(404, 'Unknown module.');
        }

        return self::MODULES[$module] + ['key' => $module];
    }

    public function departmentForModule(string $module): Department
    {
        $context = $this->moduleContext($module);
        $department = $this->departmentByCode($context['dept_code']);

        if (! $department) {
            abort(404, 'Department for this module is not configured.');
        }

        return $department;
    }

    /**
     * @return array{index: string, create: string, store: string, edit: string, update: string}
     */
    public function routeNames(string $module): array
    {
        $this->moduleContext($module);

        // Institutional finance budgeting already owns finance.budgeting.*
        $base = $module === 'finance' ? 'finance.budget-requests' : $module.'.budgeting';

        return [
            'index' => $base.'.index',
            'create' => $base.'.create',
            'store' => $base.'.store',
            'edit' => $base.'.edit',
            'update' => $base.'.update',
        ];
    }

    public function departmentByCode(string $code): ?Department
    {
        return Department::query()
            ->where('dept_code', $code)
            ->where('is_active', true)
            ->whereNull('parent_dept_id')
            ->first()
            ?? Department::query()
                ->where('dept_code', $code)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
    }

    /**
     * Prefer module home over the legacy /departments/{id} hub.
     */
    public function moduleHomeUrlForDepartment(Department $department): ?string
    {
        foreach (self::MODULES as $module => $config) {
            if ($department->dept_code === $config['dept_code']) {
                if ($module === 'academics') {
                    return route($config['dashboard_route']);
                }

                return route($config['dashboard_route']);
            }
        }

        if ($department->isLearningDepartment()) {
            return route('departments.academics.dashboard', [
                'learning_department' => $department->id,
            ]);
        }

        return null;
    }

    /**
     * @return LengthAwarePaginator<int, BudgetRequest>|Collection<int, BudgetRequest>
     */
    public function requestsForDepartment(Department $department, int $perPage = 20)
    {
        if (! Schema::hasTable('admin_budget_requests')) {
            return collect();
        }

        return BudgetRequest::query()
            ->with(['planningCycle'])
            ->where('department_id', $department->id)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, PlanningCycle>
     */
    public function openCycles(): Collection
    {
        if (! Schema::hasTable('admin_planning_cycles')) {
            return collect();
        }

        return PlanningCycle::query()
            ->where('status', 'open')
            ->orderByDesc('id')
            ->get();
    }

    public function findDepartmentRequest(Department $department, int $id): BudgetRequest
    {
        return BudgetRequest::query()
            ->where('department_id', $department->id)
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resubmit(BudgetRequest $request, Department $department, array $data, User $user): BudgetRequest
    {
        if ((int) $request->department_id !== (int) $department->id) {
            abort(403);
        }

        $updated = $this->administration->resubmitBudgetRequest($request, $data, $user->id);

        try {
            app(AdministrationSidebarNotificationService::class)->broadcastCounts();
        } catch (\Throwable) {
            // Non-fatal if broadcasting is unavailable.
        }

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Department $department, array $data, User $user): BudgetRequest
    {
        $payload = array_merge($data, [
            'department_id' => $department->id,
        ]);

        $request = $this->administration->createBudgetRequest($payload, $user->id);

        try {
            app(AdministrationSidebarNotificationService::class)->broadcastCounts();
        } catch (\Throwable) {
            // Non-fatal if broadcasting is unavailable.
        }

        return $request;
    }
}
