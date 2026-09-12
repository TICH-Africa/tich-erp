<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use App\Services\Me\MeQuarterlyReportService;
use App\Services\Me\MeTechnicalPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected MeQuarterlyReportService $reports,
        protected MeTechnicalPlanService $plans,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $items = MeQuarterlyReport::query()
            ->with(['department', 'quarter', 'technicalPlan'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('monitoring-evaluation.reports.index', compact('items', 'status'));
    }

    public function show(MeQuarterlyReport $report): View
    {
        $report->load(['department', 'quarter', 'lines', 'technicalPlan', 'submitter', 'meVerifier']);

        return view('monitoring-evaluation.reports.show', compact('report'));
    }

    public function verify(Request $request, MeQuarterlyReport $report): RedirectResponse
    {
        $data = $request->validate([
            'me_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        try {
            $this->reports->verify($report, $request->user(), $data['me_notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['report' => $e->getMessage()]);
        }

        return back()->with('status', 'Report verified and delivered to the CEO dashboard.');
    }

    public function returnReport(Request $request, MeQuarterlyReport $report): RedirectResponse
    {
        $data = $request->validate([
            'me_notes' => ['required', 'string', 'max:3000'],
        ]);

        try {
            $this->reports->returnToHod($report, $request->user(), $data['me_notes']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['report' => $e->getMessage()]);
        }

        return back()->with('status', 'Report returned to the department for revision.');
    }

    public function openQuarter(Request $request, MeTechnicalPlan $plan, int $quarter): RedirectResponse
    {
        abort_unless($plan->isBaselineLocked(), 403, 'Plan must be baseline-locked.');

        $q = $plan->quarters()->where('quarter_number', $quarter)->firstOrFail();
        $report = $this->plans->ensureQuarterlyReportDraft($plan, $q);
        $report->loadMissing('department');

        // Department staff fill/submit; M&E officers review on the verification screen.
        if ($this->reports->userCanSubmitDepartmentReport($request->user(), $report->department)) {
            return redirect()->route('monitoring_evaluation.department.reports.edit', $report);
        }

        if (in_array($report->status, ['submitted', 'me_verified', 'ceo_delivered', 'returned'], true)) {
            return redirect()->route('monitoring_evaluation.reports.show', $report);
        }

        return redirect()
            ->route('monitoring_evaluation.department.reports.edit', $report)
            ->with('status', 'This report is still a department draft. Departments submit it to M&E for verification.');
    }
}
