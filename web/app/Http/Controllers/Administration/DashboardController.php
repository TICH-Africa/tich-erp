<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Models\Administration\FundAllocation;
use App\Models\Administration\PlanningCycle;
use App\Models\Department;
use App\Services\Administration\AdministrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function __invoke(Request $request): View
    {
        $department = null;

        if ($request->filled('department')) {
            abort_unless(Schema::hasTable('departments') && Schema::hasTable('department_modules'), 404);

            $department = Department::query()
                ->whereKey((int) $request->query('department'))
                ->where('dept_category', 'administrative')
                ->where('is_active', true)
                ->whereExists(fn ($query) => $query
                    ->selectRaw('1')
                    ->from('department_modules')
                    ->whereColumn('department_modules.department_id', 'departments.id')
                    ->where('department_modules.module_key', 'administration'))
                ->firstOrFail();
        }

        $lifecycle = $this->admin->admissionsLifecycleStats();
        $inspection = $this->admin->inspectionReadiness();
        $p2p = $this->admin->procurementToPaySnapshot();

        $budgetRequests = Schema::hasTable('admin_budget_requests')
            ? BudgetRequest::query()->when($department, fn ($query) => $query->where('department_id', $department->id))
            : null;
        $chartData = [
            'budgetByStatus' => $this->groupedChartData($budgetRequests, 'status'),
            'budgetByFramework' => $this->groupedChartData($budgetRequests, 'framework'),
            'tasksByStatus' => Schema::hasTable('admin_tasks')
                ? $this->groupedChartData(
                    \App\Models\Administration\AdminTask::query()->when($department, fn ($query) => $query->where('department_id', $department->id)),
                    'status'
                )
                : ['labels' => [], 'values' => []],
            'procurementPipeline' => [
                'labels' => ['Suppliers', 'Purchase orders', 'Open AP', '3-way match pending'],
                'values' => [$p2p['suppliers'], $p2p['purchase_orders'], $p2p['ap_open'], $p2p['three_way_pending']],
            ],
        ];

        return view('administration.dashboard', [
            'department' => $department,
            'planningOpen' => Schema::hasTable('admin_planning_cycles')
                ? PlanningCycle::query()->where('status', 'open')->count()
                : 0,
            'pendingApprovals' => $budgetRequests
                ? (clone $budgetRequests)->whereIn('status', ['submitted', 'finance_review', 'executive_review'])->count()
                : 0,
            'releasedFunds' => Schema::hasTable('admin_fund_allocations')
                ? (float) FundAllocation::query()->when($department, fn ($query) => $query->where('department_id', $department->id))->where('status', 'released')->sum('amount')
                : 0,
            'lifecycle' => $lifecycle,
            'inspectionScore' => $inspection['score'],
            'p2p' => $p2p,
            'chartData' => $chartData,
        ]);
    }

    private function groupedChartData($query, string $column): array
    {
        if (! $query) {
            return ['labels' => [], 'values' => []];
        }

        $grouped = $query->select($column)
            ->selectRaw('COUNT(*) as total')
            ->groupBy($column)
            ->pluck('total', $column);

        return [
            'labels' => $grouped->keys()->map(fn ($label) => ucfirst(str_replace('_', ' ', (string) $label)))->values()->all(),
            'values' => $grouped->values()->map(fn ($total) => (int) $total)->all(),
        ];
    }
}
