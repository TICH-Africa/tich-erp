<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Me\MeDepartmentHealthScore;
use App\Models\Me\MePolicy;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use App\Services\Me\MePolicyService;
use App\Services\Me\MeTechnicalPlanService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        MePolicyService $policies,
        MeTechnicalPlanService $plans,
    ): View {
        $currentPolicy = $policies->currentPublishedPolicy();
        $signoff = $currentPolicy ? $policies->signoffProgress($currentPolicy) : null;

        $stats = [
            'plans_review' => MeTechnicalPlan::query()->where('status', 'me_review')->count(),
            'baselines' => MeTechnicalPlan::query()->where('status', 'baseline_locked')->count(),
            'reports_queue' => MeQuarterlyReport::query()->where('status', 'submitted')->count(),
            'ceo_delivered' => MeQuarterlyReport::query()->where('status', 'ceo_delivered')->count(),
        ];

        $pendingPlans = MeTechnicalPlan::query()
            ->with('department')
            ->whereIn('status', ['me_review', 'returned'])
            ->orderByDesc('submitted_at')
            ->limit(8)
            ->get();

        $pendingReports = MeQuarterlyReport::query()
            ->with(['department', 'quarter'])
            ->where('status', 'submitted')
            ->orderByDesc('submitted_at')
            ->limit(8)
            ->get();

        $health = MeDepartmentHealthScore::query()
            ->with('department')
            ->orderByDesc('calculated_at')
            ->limit(8)
            ->get();

        return view('monitoring-evaluation.dashboard', compact(
            'stats',
            'currentPolicy',
            'signoff',
            'pendingPlans',
            'pendingReports',
            'health',
        ));
    }
}
