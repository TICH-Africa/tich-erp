<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use App\Services\Me\MeQuarterlyReportDocumentService;
use App\Services\Me\MeQuarterlyReportService;
use App\Services\Me\MeTechnicalPlanDocumentService;
use App\Services\Me\MeTechnicalPlanService;
use App\Support\MeDepartmentReportModuleContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentReportController extends Controller
{
    public function __construct(
        protected MeQuarterlyReportService $reports,
        protected MeTechnicalPlanService $plans,
        protected MeQuarterlyReportDocumentService $reportDocuments,
        protected MeTechnicalPlanDocumentService $planDocuments,
    ) {}

    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $moduleContext = MeDepartmentReportModuleContext::resolve($request);
        $moduleKey = $moduleContext['key'] ?? '';

        if ($moduleKey === 'monitoring_evaluation' && $this->reports->userIsMeReviewer($request->user())) {
            return redirect()->to(route('monitoring_evaluation.dashboard').'#quarterly-workspace');
        }

        if (in_array($moduleKey, ['qa', 'monitoring_evaluation'], true)) {
            $scopeIds = Department::query()->where('is_active', 1)->pluck('id')->all();
        } else {
            $scopeIds = MeDepartmentReportModuleContext::scopeDepartmentIds($moduleContext, $request);
        }

        abort_if($scopeIds === [], 403, 'No department is configured for this module.');

        // A locked plan reports on every quarter; a plan still being filled reports on
        // the quarters M&E has already approved.
        $plans = MeTechnicalPlan::query()
            ->with(['quarters', 'department', 'quarterlyReports'])
            ->where(function ($query) {
                $query->where('status', 'baseline_locked')
                    ->orWhereHas('quarters', fn ($q) => $q->where('status', 'me_approved'));
            })
            ->whereIn('department_id', $scopeIds)
            ->orderByDesc('baseline_locked_at')
            ->orderByDesc('id')
            ->get();

        $reports = MeQuarterlyReport::query()
            ->with(['quarter', 'department', 'technicalPlan'])
            ->whereIn('department_id', $scopeIds)
            ->orderByDesc('id')
            ->paginate(20);

        return view('monitoring-evaluation.department.index', $this->viewPayload($moduleContext, [
            'plans' => $plans,
            'reports' => $reports,
            'scopeDepartment' => $moduleContext['department'] ?? null,
            'viewAllDepartments' => in_array($moduleKey, ['qa', 'monitoring_evaluation'], true),
            'hodReadOnly' => ! in_array($moduleKey, ['qa', 'monitoring_evaluation'], true),
        ]));
    }

    public function open(Request $request, MeTechnicalPlan $plan, int $quarter): RedirectResponse
    {
        abort_unless($this->reports->userIsMeReviewer($request->user()), 403, 'Quarterly reports are managed on the M&E dashboard.');

        $moduleContext = MeDepartmentReportModuleContext::resolve($request);
        $this->assertDepartmentInModuleScope($moduleContext, $plan->department);

        $q = $plan->quarters()->where('quarter_number', $quarter)->firstOrFail();
        abort_unless(
            $plan->isQuarterReportable((int) $q->quarter_number),
            403,
            $q->label().' is not approved by M&E yet, so its report cannot be filled.'
        );

        $report = $this->plans->ensureQuarterlyReportDraft($plan, $q);

        return redirect()->route('monitoring_evaluation.reports.edit', $report);
    }

    /**
     * A filed quarterly report as a signed document.
     */
    public function print(Request $request, MeQuarterlyReport $report): View
    {
        $this->assertReportVisible($request, $report);

        return $this->reportDocuments->page($report);
    }

    public function pdf(Request $request, MeQuarterlyReport $report): StreamedResponse
    {
        $this->assertReportVisible($request, $report);

        return $this->reportDocuments->download($report);
    }

    /**
     * The reports a department filed against one technical plan: {quarter}=1|2|3|4|all.
     */
    public function planPrint(Request $request, MeTechnicalPlan $plan, int|string $quarter): View
    {
        return $this->documents($request, $plan, $quarter, 'planPage');
    }

    public function planPdf(Request $request, MeTechnicalPlan $plan, int|string $quarter): StreamedResponse
    {
        return $this->documents($request, $plan, $quarter, 'planDownload');
    }

    private function documents(Request $request, MeTechnicalPlan $plan, int|string $quarter, string $method)
    {
        $moduleContext = MeDepartmentReportModuleContext::resolve($request);
        $this->assertDepartmentInModuleScope($moduleContext, $plan->department);

        $plan->loadMissing(['department', 'quarters']);

        try {
            return $this->reportDocuments->{$method}($plan, $this->quarterFilter($quarter));
        } catch (\RuntimeException $e) {
            abort(404, $e->getMessage());
        }
    }

    private function quarterFilter(int|string $quarter): int|string
    {
        return ctype_digit((string) $quarter) ? (int) $quarter : 'all';
    }

    private function assertReportVisible(Request $request, MeQuarterlyReport $report): void
    {
        $moduleContext = MeDepartmentReportModuleContext::resolve($request);
        $report->loadMissing('department');
        $this->assertDepartmentInModuleScope($moduleContext, $report->department);
    }

    public function edit(Request $request, MeQuarterlyReport $report): View
    {
        $moduleContext = MeDepartmentReportModuleContext::resolve($request);
        $moduleKey = $moduleContext['key'] ?? '';
        $report->load(['lines', 'department', 'quarter', 'technicalPlan']);
        $this->assertDepartmentInModuleScope($moduleContext, $report->department);

        abort_unless(
            $this->reports->userCanViewDepartmentReport($request->user(), $report->department),
            403
        );

        if ($this->reports->userIsMeReviewer($request->user())) {
            return redirect()->route('monitoring_evaluation.reports.show', $report);
        }

        return view('monitoring-evaluation.department.edit', $this->viewPayload($moduleContext, [
            'report' => $report,
            'canSubmit' => false,
            'canEdit' => false,
            'viewOnly' => true,
        ]));
    }

    public function update(Request $request, MeQuarterlyReport $report): RedirectResponse
    {
        abort(403, 'Quarterly reports are compiled by the M&E office on the command center dashboard.');
    }

    public function submit(Request $request, MeQuarterlyReport $report): RedirectResponse
    {
        abort(403, 'Quarterly reports are compiled by the M&E office on the command center dashboard.');
    }

    /**
     * @param  array{key: string, department?: Department|null}  $moduleContext
     */
    private function assertDepartmentInModuleScope(array $moduleContext, ?Department $department): void
    {
        abort_unless($department, 404);

        $moduleKey = $moduleContext['key'] ?? '';

        if (in_array($moduleKey, ['qa', 'monitoring_evaluation'], true)) {
            return;
        }

        $scopeIds = MeDepartmentReportModuleContext::scopeDepartmentIds($moduleContext);

        abort_unless(
            in_array((int) $department->id, $scopeIds, true),
            403,
            'You can only open M&E reports for this module\'s department.'
        );
    }

    /**
     * @param  array{key: string, layout: string, content_section: string, routes: array<string, string>, department?: Department|null}  $moduleContext
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function viewPayload(array $moduleContext, array $extra = []): array
    {
        $payload = array_merge([
            'moduleContext' => $moduleContext,
            'reportRoutes' => $moduleContext['routes'],
        ], $extra);

        if (($moduleContext['key'] ?? '') === 'academics') {
            $payload['department'] = $moduleContext['department'] ?? Department::findAcademicsHub();
        }

        return $payload;
    }
}
