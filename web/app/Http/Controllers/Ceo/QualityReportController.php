<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\Qa\QaComplianceScore;
use App\Models\Qa\QaCorrectiveAction;
use App\Models\Qa\QaPlan;
use Illuminate\View\View;

class QualityReportController extends Controller
{
    public function index(): View
    {
        $plans = QaPlan::query()
            ->whereIn('status', ['compiled', 'closed', 'in_progress', 'dispatched'])
            ->with(['complianceScores.department', 'correctiveActions'])
            ->orderByDesc('compiled_at')
            ->orderByDesc('id')
            ->paginate(15);

        $openActions = QaCorrectiveAction::query()
            ->with(['department', 'plan'])
            ->whereIn('status', ['open', 'in_progress', 'overdue'])
            ->orderBy('resolution_deadline')
            ->limit(10)
            ->get();

        $failing = QaComplianceScore::query()
            ->with(['plan', 'department'])
            ->where('is_below_threshold', 1)
            ->orderByDesc('calculated_at')
            ->limit(10)
            ->get();

        return view('ceo.quality.index', compact('plans', 'openActions', 'failing'));
    }

    public function show(QaPlan $plan): View
    {
        $plan->load(['complianceScores.department', 'correctiveActions.department', 'checklists']);

        return view('ceo.quality.show', compact('plan'));
    }
}
