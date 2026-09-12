<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Me\MeTechnicalPlan;
use App\Services\Me\MeTechnicalPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TechnicalPlanController extends Controller
{
    public function __construct(
        protected MeTechnicalPlanService $plans,
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
        $plan->load(['department', 'budgetRequest', 'outputs', 'quarters', 'submitter', 'meReviewer']);

        return view('monitoring-evaluation.plans.show', compact('plan'));
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
