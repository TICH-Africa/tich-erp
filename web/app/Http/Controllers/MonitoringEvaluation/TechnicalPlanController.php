<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Me\MeTechnicalPlan;
use App\Models\Me\MeQuarterlyReport;
use App\Services\Me\MeTechnicalPlanDocumentService;
use App\Services\Me\MeTechnicalPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TechnicalPlanController extends Controller
{
    public function __construct(
        protected MeTechnicalPlanService $plans,
        protected MeTechnicalPlanDocumentService $documents,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $items = MeTechnicalPlan::query()
            ->with(['department', 'budgetRequest', 'outputs'])
            ->when(
                $status !== '',
                fn ($q) => $q->where('status', $status),
                // Hide plans still waiting on Administration clearance unless filtered.
                fn ($q) => $q->where('status', '!=', 'draft')
            )
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('monitoring-evaluation.plans.index', compact('items', 'status'));
    }

    public function show(MeTechnicalPlan $plan): View
    {
        $plan->load(['department', 'budgetRequest', 'outputs', 'quarters', 'quarterlyReports', 'submitter', 'meReviewer']);

        if ($plan->outputs->isEmpty() && $plan->budget_request_id) {
            try {
                $this->plans->hydrateOutputsFromBudget($plan);
                $plan->load(['outputs', 'budgetRequest']);
            } catch (\Throwable) {
                // Keep the page usable even if recovery fails.
            }
        }

        return view('monitoring-evaluation.plans.show', compact('plan'));
    }

    public function print(Request $request, MeTechnicalPlan $plan): View
    {
        return $this->documents->page($plan, $this->quarterFilter($request));
    }

    public function pdf(Request $request, MeTechnicalPlan $plan): StreamedResponse
    {
        return $this->documents->download($plan, $this->quarterFilter($request));
    }

    /**
     * The document covers one quarter or the whole year: ?quarter=1|2|3|4|all
     */
    private function quarterFilter(Request $request): int|string
    {
        $quarter = (string) $request->query('quarter', 'all');

        return ctype_digit($quarter) ? (int) $quarter : 'all';
    }

    public function approve(Request $request, MeTechnicalPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'me_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        try {
            $updated = $this->plans->approveByMe($plan, $request->user(), $data['me_notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        $message = $updated->isBaselineLocked()
            ? 'Technical plan approved and baseline locked (budget already authorized). Quarters created.'
            : 'Technical plan approved by M&E. Baseline will lock when Admin/Finance/CEO approve the linked budget.';

        return redirect()
            ->route('monitoring_evaluation.plans.show', $updated)
            ->with('status', $message);
    }

    public function approveQuarter(
        Request $request,
        MeTechnicalPlan $plan,
        int $quarter,
    ): RedirectResponse {
        $data = $request->validate([
            'me_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        try {
            $updated = $this->plans->approveQuarter($plan, $quarter, $request->user(), $data['me_notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        $message = $updated->isBaselineLocked()
            ? 'Q'.$quarter.' approved - all four quarters are cleared, so the baseline plan is locked.'
            : 'Q'.$quarter.' approved. The remaining quarters stay open for the department to fill.';

        $updated->load(['quarters', 'quarterlyReports']);
        $quarterRow = $updated->quarters->firstWhere('quarter_number', $quarter);
        $report = $quarterRow
            ? $updated->quarterlyReports->firstWhere('quarter_id', $quarterRow->id)
            : null;

        if ($report && in_array($report->status, ['draft', 'returned'], true)) {
            return redirect()
                ->route('monitoring_evaluation.reports.edit', $report)
                ->with('status', $message.' Open the quarterly report to enter achieved values.');
        }

        return redirect()
            ->route('monitoring_evaluation.plans.show', $updated)
            ->with('status', $message);
    }

    public function returnQuarter(Request $request, MeTechnicalPlan $plan, int $quarter): RedirectResponse
    {
        $data = $request->validate([
            'me_notes' => ['required', 'string', 'max:3000'],
        ]);

        try {
            $this->plans->returnQuarter($plan, $quarter, $request->user(), $data['me_notes']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return back()->with('status', 'Q'.$quarter.' returned to the department for revision.');
    }

    public function returnPlan(Request $request, MeTechnicalPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'me_notes' => ['required', 'string', 'max:3000'],
        ]);

        try {
            $this->plans->returnToDepartment($plan, $request->user(), $data['me_notes']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return back()->with('status', 'Technical plan returned to the department.');
    }

    public function lockBaseline(Request $request, MeTechnicalPlan $plan): RedirectResponse
    {
        try {
            $locked = $this->plans->tryBaselineLock($plan, $request->user());
            if (! $locked) {
                return back()->withErrors([
                    'plan' => 'Baseline lock requires M&E approval and an approved linked budget request.',
                ]);
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return back()->with('status', 'Baseline plan locked and partitioned into four quarters.');
    }
}
