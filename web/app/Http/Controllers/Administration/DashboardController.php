<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\AdminTask;
use App\Models\Administration\BudgetRequest;
use App\Models\Administration\CalendarEvent;
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
        $statutory = $this->admin->statutoryReadiness();
        $p2p = $this->admin->procurementToPaySnapshot();

        $budgetRequests = Schema::hasTable('admin_budget_requests')
            ? BudgetRequest::query()->when($department, fn ($query) => $query->where('department_id', $department->id))
            : null;

        $planningCycles = Schema::hasTable('admin_planning_cycles')
            ? PlanningCycle::query()->orderByDesc('fiscal_year')->orderByDesc('period_start')->limit(20)->get()
            : collect();

        $calendarEvents = Schema::hasTable('admin_calendar_events')
            ? CalendarEvent::query()->orderByDesc('starts_on')->limit(20)->get()
            : collect();

        $adminTasks = Schema::hasTable('admin_tasks')
            ? AdminTask::query()->when($department, fn ($query) => $query->where('department_id', $department->id))->orderByDesc('due_on')->limit(20)->get()
            : collect();

        $variances = Schema::hasTable('admin_variances')
            ? \App\Models\Administration\Variance::query()->when($department, fn ($query) => $query->where('department_id', $department->id))->orderByDesc('fiscal_year')->orderByDesc('month')->limit(20)->get()
            : collect();

        $departments = Schema::hasTable('departments')
            ? Department::query()->where('dept_category', 'administrative')->where('is_active', true)->orderBy('dept_name')->get()
            : collect();

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
            'departments' => $departments,
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
            'statutoryReadiness' => $statutory,
            'p2p' => $p2p,
            'chartData' => $chartData,
            'planningCycles' => $planningCycles,
            'calendarEvents' => $calendarEvents,
            'adminTasks' => $adminTasks,
            'variances' => $variances,
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
