<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use App\Services\Me\MeQuarterlyReportService;
use App\Services\Me\MeTechnicalPlanService;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentReportController extends Controller
{
    public function __construct(
        protected MeQuarterlyReportService $reports,
        protected MeTechnicalPlanService $plans,
        protected StaffPortalService $staffPortal,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->staffPortal->staffForUser($request->user());
        $departmentId = $staff?->department_id;

        $plans = MeTechnicalPlan::query()
            ->with(['quarters', 'department'])
            ->where('status', 'baseline_locked')
            ->when(
                ! $request->user()->hasAnyRole([
                    'Super Admin',
                    'Monitoring and Evaluation Officer',
                    'Assistant Monitoring and Evaluation Officer',
                ]),
                fn ($q) => $q->where('department_id', $departmentId)
            )
            ->orderByDesc('baseline_locked_at')
            ->get();

        $reports = MeQuarterlyReport::query()
            ->with(['quarter', 'department', 'technicalPlan'])
            ->when(
                ! $request->user()->hasAnyRole([
                    'Super Admin',
                    'Monitoring and Evaluation Officer',
                    'Assistant Monitoring and Evaluation Officer',
                ]),
                fn ($q) => $q->where('department_id', $departmentId)
            )
            ->orderByDesc('id')
            ->paginate(20);

        return view('monitoring-evaluation.department.index', compact('plans', 'reports', 'staff'));
    }

    public function open(Request $request, MeTechnicalPlan $plan, int $quarter): RedirectResponse
    {
        abort_unless($plan->isBaselineLocked(), 403);
        abort_unless(
            $this->reports->userCanEditDepartmentReport($request->user(), $plan->department),
            403
        );

        $q = $plan->quarters()->where('quarter_number', $quarter)->firstOrFail();
        $report = $this->plans->ensureQuarterlyReportDraft($plan, $q);

        return redirect()->route('monitoring_evaluation.department.reports.edit', $report);
    }

    public function edit(Request $request, MeQuarterlyReport $report): View
    {
        $report->load(['lines', 'department', 'quarter', 'technicalPlan']);
        abort_unless(
            $this->reports->userCanEditDepartmentReport($request->user(), $report->department),
            403
        );

        return view('monitoring-evaluation.department.edit', compact('report'));
    }

    public function update(Request $request, MeQuarterlyReport $report): RedirectResponse
    {
        $report->load('department');
        abort_unless(
            $this->reports->userCanEditDepartmentReport($request->user(), $report->department),
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
        $report->load('department');
        abort_unless(
            $this->reports->userCanEditDepartmentReport($request->user(), $report->department),
            403
        );

        try {
            $this->reports->submit($report, $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['report' => $e->getMessage()]);
        }

        return redirect()
            ->route('monitoring_evaluation.department.index')
            ->with('status', 'Quarterly M&E report submitted for M&E Officer verification.');
    }
}
