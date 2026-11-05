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

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->has('status') && ! $request->boolean('list')) {
            return redirect()->to(route('monitoring_evaluation.dashboard').'#quarterly-workspace');
        }

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

        $canEdit = auth()->user()
            && $this->reports->userCanEnterAchieved(auth()->user(), $report);

        return view('monitoring-evaluation.reports.show', compact('report', 'canEdit'));
    }

    public function edit(MeQuarterlyReport $report): View|RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('monitoring_evaluation.read'), 403);
        abort_unless($this->reports->userIsMeReviewer(auth()->user()), 403);

        $report->load(['lines', 'department', 'quarter', 'technicalPlan']);

        if (! $this->reports->userCanEnterAchieved(auth()->user(), $report)) {
            return redirect()->route('monitoring_evaluation.reports.show', $report);
        }

        return view('monitoring-evaluation.reports.edit', [
            'report' => $report,
        ]);
    }

    public function update(Request $request, MeQuarterlyReport $report): RedirectResponse
    {
        abort_unless($this->reports->userIsMeReviewer($request->user()), 403);
        abort_unless($this->reports->userCanEnterAchieved($request->user(), $report), 403);

        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.achieved' => ['required', 'numeric'],
        ]);

        try {
            $this->reports->saveDraft($report, $data['lines']);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['report' => $e->getMessage()]);
        }

        return back()->with('status', 'Achieved values saved.');
    }

    public function complete(Request $request, MeQuarterlyReport $report): RedirectResponse
    {
        abort_unless($this->reports->userIsMeReviewer($request->user()), 403);

        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.achieved' => ['required', 'numeric'],
            'me_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        try {
            $this->reports->completeAndDeliver(
                $report,
                $request->user(),
                $data['lines'],
                $data['me_notes'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['report' => $e->getMessage()]);
        }

        return redirect()
            ->route('monitoring_evaluation.dashboard')
            ->with('status', 'Quarterly report verified and delivered to the CEO dashboard.');
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

        return back()->with('status', 'Report reopened for M&E revision.');
    }

    public function openQuarter(Request $request, MeTechnicalPlan $plan, int $quarter): RedirectResponse
    {
        abort_unless($this->reports->userIsMeReviewer($request->user()), 403);

        $q = $plan->quarters()->where('quarter_number', $quarter)->firstOrFail();
        abort_unless($plan->isQuarterReportable((int) $q->quarter_number), 403, 'This quarter is not approved by M&E yet.');

        $report = $this->plans->ensureQuarterlyReportDraft($plan, $q);

        if ($this->reports->userCanEnterAchieved($request->user(), $report)) {
            return redirect()
                ->route('monitoring_evaluation.reports.edit', $report)
                ->with('status', 'Enter achieved values for this quarter.');
        }

        return redirect()->route('monitoring_evaluation.reports.show', $report);
    }
}
