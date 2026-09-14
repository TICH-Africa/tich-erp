<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use App\Services\Me\MeQuarterlyReportService;
use App\Services\Me\MeTechnicalPlanService;
use App\Support\MeDepartmentReportModuleContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentReportController extends Controller
{
    public function __construct(
        protected MeQuarterlyReportService $reports,
        protected MeTechnicalPlanService $plans,
    ) {}

    public function index(Request $request): View
    {
        $moduleContext = MeDepartmentReportModuleContext::resolve($request);
        $moduleKey = $moduleContext['key'] ?? '';

        if (in_array($moduleKey, ['qa', 'monitoring_evaluation'], true)) {
            $scopeIds = Department::query()->where('is_active', 1)->pluck('id')->all();
        } else {
            $scopeIds = MeDepartmentReportModuleContext::scopeDepartmentIds($moduleContext, $request);
        }

        abort_if($scopeIds === [], 403, 'No department is configured for this module.');

        $plans = MeTechnicalPlan::query()
            ->with(['quarters', 'department'])
            ->where('status', 'baseline_locked')
            ->whereIn('department_id', $scopeIds)
            ->orderByDesc('baseline_locked_at')
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
        ]));
    }

    public function open(Request $request, MeTechnicalPlan $plan, int $quarter): RedirectResponse
    {
        $moduleContext = MeDepartmentReportModuleContext::resolve($request);
        $this->assertDepartmentInModuleScope($moduleContext, $plan->department);

        abort_unless($plan->isBaselineLocked(), 403);
        abort_unless(
            $this->reports->userCanEditDepartmentReport($request->user(), $plan->department),
            403
        );

        $q = $plan->quarters()->where('quarter_number', $quarter)->firstOrFail();
        $report = $this->plans->ensureQuarterlyReportDraft($plan, $q);

        return redirect()->to(MeDepartmentReportModuleContext::url('edit', $moduleContext['key'], [
            'report' => $report,
        ]));
    }

    public function edit(Request $request, MeQuarterlyReport $report): View
    {
        $moduleContext = MeDepartmentReportModuleContext::resolve($request);
        $moduleKey = $moduleContext['key'] ?? '';
        $report->load(['lines', 'department', 'quarter', 'technicalPlan']);
        $this->assertDepartmentInModuleScope($moduleContext, $report->department);

        $canEdit = $this->reports->userCanEditDepartmentReport($request->user(), $report->department);
        $canSubmit = $this->reports->userCanSubmitDepartmentReport($request->user(), $report->department);

        abort_unless($canEdit || in_array($moduleKey, ['qa', 'monitoring_evaluation'], true), 403);

        return view('monitoring-evaluation.department.edit', $this->viewPayload($moduleContext, [
            'report' => $report,
            'canSubmit' => $canSubmit,
            'canEdit' => $canEdit,
            'viewOnly' => in_array($moduleKey, ['qa', 'monitoring_evaluation'], true) && ! $canEdit,
        ]));
    }

    public function update(Request $request, MeQuarterlyReport $report): RedirectResponse
    {
        $moduleContext = MeDepartmentReportModuleContext::resolve($request);
        $report->load('department');
        $this->assertDepartmentInModuleScope($moduleContext, $report->department);

        abort_unless(
            $this->reports->userCanSubmitDepartmentReport($request->user(), $report->department),
            403
        );

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

        return back()->with('status', 'Draft saved. Deviation recalculated automatically.');
    }

    public function submit(Request $request, MeQuarterlyReport $report): RedirectResponse
    {
        $moduleContext = MeDepartmentReportModuleContext::resolve($request);
        $report->load('department');
        $this->assertDepartmentInModuleScope($moduleContext, $report->department);

        abort_unless(
            $this->reports->userCanSubmitDepartmentReport($request->user(), $report->department),
            403
        );

        try {
            $this->reports->submit($report, $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['report' => $e->getMessage()]);
        }

        return redirect()
            ->to(MeDepartmentReportModuleContext::url('index', $moduleContext['key']))
            ->with('status', 'Quarterly M&E report submitted for M&E Officer verification.');
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
