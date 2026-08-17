<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Models\Administration\FundAllocation;
use App\Models\Administration\PlanningCycle;
use App\Services\Administration\AdministrationService;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function __invoke(): View
    {
        $lifecycle = $this->admin->admissionsLifecycleStats();
        $inspection = $this->admin->inspectionReadiness();
        $p2p = $this->admin->procurementToPaySnapshot();

        return view('administration.dashboard', [
            'planningOpen' => Schema::hasTable('admin_planning_cycles')
                ? PlanningCycle::query()->where('status', 'open')->count()
                : 0,
            'pendingApprovals' => Schema::hasTable('admin_budget_requests')
                ? BudgetRequest::query()->whereIn('status', ['submitted', 'finance_review', 'executive_review'])->count()
                : 0,
            'releasedFunds' => Schema::hasTable('admin_fund_allocations')
                ? (float) FundAllocation::query()->where('status', 'released')->sum('amount')
                : 0,
            'lifecycle' => $lifecycle,
            'inspectionScore' => $inspection['score'],
            'p2p' => $p2p,
        ]);
    }
}
