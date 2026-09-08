<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Me\MeDepartmentHealthScore;
use App\Models\Me\MeTechnicalPlan;
use App\Services\Me\MeHealthScoreService;
use App\Services\Me\MeQuarterlyReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PimeController extends Controller
{
    public function __construct(
        protected MeQuarterlyReportService $reports,
        protected MeHealthScoreService $healthScores,
    ) {}

    public function index(Request $request): View
    {
        $fiscalYear = $request->string('fiscal_year')->toString() ?: (string) date('Y');
        $quarter = max(1, min(4, (int) $request->input('quarter', $this->currentQuarter())));

        $comparison = $this->reports->pimeComparison($fiscalYear, $quarter);

        $years = MeTechnicalPlan::query()
            ->whereNotNull('fiscal_year')
            ->distinct()
            ->orderByDesc('fiscal_year')
            ->pluck('fiscal_year');

        if ($years->isEmpty()) {
            $years = collect([(string) date('Y')]);
        }

        $health = MeDepartmentHealthScore::query()
            ->with('department')
            ->when($fiscalYear, fn ($q) => $q->where('fiscal_year', $fiscalYear))
            ->orderByDesc('health_score')
            ->get();

        $totals = [
            'planned' => collect($comparison)->sum('planned'),
            'achieved' => collect($comparison)->sum('achieved'),
        ];
        $totals['deviation'] = round($totals['achieved'] - $totals['planned'], 2);

        return view('monitoring-evaluation.pime.index', compact(
            'comparison',
            'fiscalYear',
            'quarter',
            'years',
            'health',
            'totals',
        ));
    }

    public function recalculateHealth(Request $request): RedirectResponse
    {
        $fiscalYear = $request->string('fiscal_year')->toString() ?: null;
        $count = $this->healthScores->recalculateAll($fiscalYear ?: null);

        return back()->with('status', "Recalculated health scores for {$count} department(s).");
    }

    protected function currentQuarter(): int
    {
        return (int) ceil(((int) date('n')) / 3);
    }
}
