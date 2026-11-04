<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use App\Services\Me\MeQuarterlyReportDocumentService;
use App\Services\Me\MeQuarterlyReportService;
use App\Services\Me\MeTechnicalPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected MeQuarterlyReportService $reports,
        protected MeTechnicalPlanService $plans,
        protected MeQuarterlyReportDocumentService $documents,
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

    public function print(MeQuarterlyReport $report): View
    {
        return $this->documents->page($report);
    }

    public function pdf(MeQuarterlyReport $report): StreamedResponse
    {
        return $this->documents->download($report);
    }

    /**
     * Every report a plan has filed for one quarter or the whole year.
     */
    public function planPrint(MeTechnicalPlan $plan, int|string $quarter): View
    {
        return $this->planDocuments($plan, $quarter, 'planPage');
    }

    public function planPdf(MeTechnicalPlan $plan, int|string $quarter): StreamedResponse
    {
        return $this->planDocuments($plan, $quarter, 'planDownload');
    }

    private function planDocuments(MeTechnicalPlan $plan, int|string $quarter, string $method)
    {
        $scope = ctype_digit((string) $quarter) ? (int) $quarter : 'all';
        $plan->loadMissing(['department', 'quarters']);

        try {
            return $this->documents->{$method}($plan, $scope);
        } catch (\RuntimeException $e) {
            abort(404, $e->getMessage());
        }
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
        // M&E clears a quarter at a time; each cleared quarter can be reported on without
        // waiting for the whole year to baseline-lock.
        $q = $plan->quarters()->where('quarter_number', $quarter)->firstOrFail();
        abort_unless($plan->isQuarterReportable((int) $q->quarter_number), 403, 'This quarter is not approved by M&E yet.');

        $report = $this->plans->ensureQuarterlyReportDraft($plan, $q);
        $report->loadMissing('department');

        $moduleKey = $report->department
            ? \App\Support\MeDepartmentReportModuleContext::moduleKeyForDepartment($report->department)
            : 'monitoring_evaluation';
        $editUrl = \App\Support\MeDepartmentReportModuleContext::url('edit', $moduleKey, ['report' => $report]);

        // Department staff fill/submit in their own module; M&E officers review on the verification screen.
        if ($this->reports->userCanSubmitDepartmentReport($request->user(), $report->department)) {
            return redirect()->to($editUrl);
        }

        if (in_array($report->status, ['submitted', 'me_verified', 'ceo_delivered', 'returned'], true)) {
            return redirect()->route('monitoring_evaluation.reports.show', $report);
        }

        return redirect()
            ->to($editUrl)
            ->with('status', 'This report is still a department draft. Open it from that department module to submit to M&E.');
    }
}
