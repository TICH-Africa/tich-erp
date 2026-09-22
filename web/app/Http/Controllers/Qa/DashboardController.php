<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Qa\IqaAssessment;
use App\Models\Qa\QaComplianceScore;
use App\Models\Qa\QaCorrectiveAction;
use App\Models\Qa\QcaFlag;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $openPlans = IqaAssessment::query()
            ->where('status', IqaAssessment::STATUS_DRAFT)
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $openActions = QaCorrectiveAction::query()
            ->with(['department', 'plan'])
            ->whereIn('status', ['open', 'in_progress', 'overdue'])
            ->orderBy('resolution_deadline')
            ->limit(8)
            ->get();

        $totalCompliance = QaComplianceScore::query()
            ->where('items_submitted', '>', 0)
            ->avg('weighted_score');
        $totalCompliance = round($totalCompliance ?? 0, 2);

        $complianceStatus = $totalCompliance >= 80 ? 'green' : ($totalCompliance >= 60 ? 'amber' : 'red');

        $openFlags = QcaFlag::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $criticalFlags = QcaFlag::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->whereIn('severity', ['High', 'Critical'])
            ->count();

        $complianceDist = QaComplianceScore::query()
            ->where('items_submitted', '>', 0)
            ->get()
            ->reduce(function ($carry, $score) {
                $s = $score->weighted_score;
                if ($s >= 80) {
                    $carry['green']++;
                } elseif ($s >= 60) {
                    $carry['amber']++;
                } else {
                    $carry['red']++;
                }

                return $carry;
            }, ['green' => 0, 'amber' => 0, 'red' => 0]);

        $flagsBySeverity = QcaFlag::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->get()
            ->groupBy('severity')
            ->map(fn ($flags) => $flags->count());

        $actionsByStatus = QaCorrectiveAction::query()
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status');

        $plansByStatus = IqaAssessment::query()
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status');

        $topFailing = QaComplianceScore::query()
            ->with(['department', 'plan'])
            ->where('is_below_threshold', 1)
            ->orderByDesc('calculated_at')
            ->limit(5)
            ->get();

        $recentPlans = IqaAssessment::query()
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return view('qa.dashboard', [
            'openPlans' => $openPlans,
            'openActions' => $openActions,
            'pendingTasks' => 0,
            'qaPendingTasks' => collect(),
            'stats' => [
                'draft' => IqaAssessment::query()->where('status', IqaAssessment::STATUS_DRAFT)->count(),
                'active' => 0,
                'compiled' => IqaAssessment::query()->where('status', IqaAssessment::STATUS_PUBLISHED)->count(),
                'corrective' => QaCorrectiveAction::query()->whereIn('status', ['open', 'in_progress', 'overdue'])->count(),
            ],
            'totalCompliance' => $totalCompliance,
            'complianceStatus' => $complianceStatus,
            'openFlags' => $openFlags,
            'criticalFlags' => $criticalFlags,
            'topFailing' => $topFailing,
            'recentPlans' => $recentPlans,
            'chartData' => [
                'compliance' => [
                    'green' => $complianceDist['green'],
                    'amber' => $complianceDist['amber'],
                    'red' => $complianceDist['red'],
                ],
                'flags' => $flagsBySeverity,
                'actions' => $actionsByStatus,
                'plans' => $plansByStatus,
            ],
        ]);
    }
}
