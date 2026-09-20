<?php

namespace App\Http\Controllers\Module;

use App\Http\Controllers\Controller;
use App\Models\Me\MeTechnicalPlan;
use App\Services\DepartmentBudgetingService;
use App\Services\Me\MeTechnicalPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TechnicalPlanController extends Controller
{
    public const QUARTERS = [
        '1' => 'Q1 · Jan–Mar',
        '2' => 'Q2 · Apr–Jun',
        '3' => 'Q3 · Jul–Sep',
        '4' => 'Q4 · Oct–Dec',
    ];

    public function __construct(
        protected DepartmentBudgetingService $budgeting,
        protected MeTechnicalPlanService $mePlans,
    ) {}

    public function index(Request $request): View
    {
        $module = $this->moduleKey($request);
        $context = $this->budgeting->moduleContext($module);
        $department = $this->budgeting->departmentForModule($module);
        $routes = $this->budgeting->technicalPlanRouteNames($module);

        $plans = collect();
        if (Schema::hasTable('me_technical_plans')) {
            $plans = MeTechnicalPlan::query()
                ->withCount('outputs')
                ->where('department_id', $department->id)
                ->whereNull('budget_request_id')
                ->latest()
                ->paginate(20);
        }

        return view('module-technical-plans.index', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'plans' => $plans,
            'indexRoute' => $routes['index'],
            'createRoute' => $routes['create'],
            'editRoute' => $routes['edit'],
            'showRoute' => $routes['show'],
        ]);
    }

    public function create(Request $request): View
    {
        $module = $this->moduleKey($request);
        $context = $this->budgeting->moduleContext($module);
        $department = $this->budgeting->departmentForModule($module);
        $routes = $this->budgeting->technicalPlanRouteNames($module);

        return view('module-technical-plans.create', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'quarters' => self::QUARTERS,
            'quarterOutputs' => $this->defaultQuarterOutputs(),
            'plan' => null,
            'formAction' => route($routes['store']),
            'submitLabel' => 'Submit technical plan to M&E',
            'pageTitle' => 'New technical plan',
            'indexRoute' => $routes['index'],
        ]);
    }

    public function show(Request $request, int $plan): View
    {
        $module = $this->moduleKey($request);
        $context = $this->budgeting->moduleContext($module);
        $department = $this->budgeting->departmentForModule($module);
        $routes = $this->budgeting->technicalPlanRouteNames($module);
        $record = $this->findDepartmentPlan($department->id, $plan);

        return view('module-technical-plans.show', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'plan' => $record->load(['outputs', 'submitter']),
            'quarters' => self::QUARTERS,
            'indexRoute' => $routes['index'],
            'editRoute' => $routes['edit'],
        ]);
    }

    public function edit(Request $request, int $plan): View
    {
        $module = $this->moduleKey($request);
        $context = $this->budgeting->moduleContext($module);
        $department = $this->budgeting->departmentForModule($module);
        $routes = $this->budgeting->technicalPlanRouteNames($module);
        $record = $this->findDepartmentPlan($department->id, $plan);

        abort_unless(in_array($record->status, ['draft', 'returned'], true), 403, 'Only draft or returned plans can be revised.');
        abort_if($record->isBaselineLocked(), 403, 'Baseline-locked plans cannot be edited.');

        $quarterOutputs = old('quarters');
        if (! is_array($quarterOutputs) || $quarterOutputs === []) {
            $quarterOutputs = $this->quarterOutputsFromPlan($record);
        }

        return view('module-technical-plans.create', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'quarters' => self::QUARTERS,
            'quarterOutputs' => $quarterOutputs,
            'plan' => $record,
            'formAction' => route($routes['update'], $record->id),
            'submitLabel' => 'Resubmit technical plan to M&E',
            'pageTitle' => 'Revise technical plan',
            'indexRoute' => $routes['index'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->persist($request);
    }

    public function update(Request $request, int $plan): RedirectResponse
    {
        return $this->persist($request, $plan);
    }

    private function persist(Request $request, ?int $planId = null): RedirectResponse
    {
        $module = $this->moduleKey($request);
        $department = $this->budgeting->departmentForModule($module);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'fiscal_year' => ['nullable', 'string', 'max:20'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'quarters' => ['required', 'array'],
            'quarters.1' => ['required', 'array'],
            'quarters.2' => ['required', 'array'],
            'quarters.3' => ['required', 'array'],
            'quarters.4' => ['required', 'array'],
            'quarters.*.outputs' => ['required', 'array', 'min:1'],
            'quarters.*.outputs.*.output' => ['nullable', 'string', 'max:2000'],
            'quarters.*.outputs.*.activity' => ['nullable', 'string', 'max:2000'],
            'quarters.*.outputs.*.costable_item' => ['nullable', 'string', 'max:500'],
            'quarters.*.outputs.*.planned' => ['nullable', 'numeric', 'min:0'],
            'quarters.*.outputs.*.planned_unit' => ['nullable', 'string', 'max:50'],
        ], [
            'quarters.required' => 'Add outputs for each quarter.',
        ]);

        $flatOutputs = $this->flattenQuarterOutputs($data['quarters']);
        if ($flatOutputs === []) {
            return back()->withInput()->withErrors([
                'quarters' => 'Add at least one output with an output name and activity across the four quarters.',
            ]);
        }

        try {
            if ($planId) {
                $existing = $this->findDepartmentPlan($department->id, $planId);
                $this->mePlans->submitIndependentPlan(
                    $department,
                    $request->user(),
                    $data['title'],
                    $flatOutputs,
                    $data['summary'] ?? null,
                    $data['fiscal_year'] ?? null,
                    $existing,
                );
                $message = 'Technical plan revised and re-routed to M&E.';
            } else {
                $this->mePlans->submitIndependentPlan(
                    $department,
                    $request->user(),
                    $data['title'],
                    $flatOutputs,
                    $data['summary'] ?? null,
                    $data['fiscal_year'] ?? null,
                );
                $message = 'Technical plan submitted to M&E.';
            }
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['plan' => $e->getMessage()]);
        }

        return redirect()
            ->route($this->budgeting->technicalPlanRouteNames($module)['index'])
            ->with('status', $message);
    }

    /**
     * @param  array<string, mixed>  $quarters
     * @return list<array{output: string, activity: string, costable_item: ?string, planned: float, planned_unit: ?string, quarter: int}>
     */
    private function flattenQuarterOutputs(array $quarters): array
    {
        $flat = [];
        foreach (['1', '2', '3', '4'] as $q) {
            $rows = is_array($quarters[$q]['outputs'] ?? null) ? $quarters[$q]['outputs'] : [];
            foreach (array_values($rows) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $output = trim((string) ($row['output'] ?? ''));
                $activity = trim((string) ($row['activity'] ?? ''));
                if ($output === '' && $activity === '') {
                    continue;
                }
                if ($output === '' || $activity === '') {
                    continue;
                }
                $flat[] = [
                    'output' => $output,
                    'activity' => $activity,
                    'costable_item' => trim((string) ($row['costable_item'] ?? '')) ?: null,
                    'planned' => round((float) ($row['planned'] ?? 0), 2),
                    'planned_unit' => trim((string) ($row['planned_unit'] ?? '')) ?: null,
                    'quarter' => (int) $q,
                ];
            }
        }

        return $flat;
    }

    /**
     * @return array<string, array{outputs: list<array{output: string, activity: string, costable_item: string, planned: string, planned_unit: string}>}>
     */
    private function defaultQuarterOutputs(): array
    {
        $old = old('quarters');
        if (is_array($old) && $old !== []) {
            return $old;
        }

        $empty = [['output' => '', 'activity' => '', 'costable_item' => '', 'planned' => '', 'planned_unit' => '']];
        $data = [];
        foreach (array_keys(self::QUARTERS) as $q) {
            $data[$q] = ['outputs' => $empty];
        }

        return $data;
    }

    /**
     * @return array<string, array{outputs: list<array<string, mixed>>}>
     */
    private function quarterOutputsFromPlan(MeTechnicalPlan $plan): array
    {
        $plan->loadMissing('outputs');
        $data = $this->defaultQuarterOutputs();

        foreach ($plan->outputs as $output) {
            $q = (string) ($output->quarter ?: 1);
            if (! isset($data[$q])) {
                $q = '1';
            }
            if (
                count($data[$q]['outputs']) === 1
                && ($data[$q]['outputs'][0]['output'] ?? '') === ''
                && ($data[$q]['outputs'][0]['activity'] ?? '') === ''
            ) {
                $data[$q]['outputs'] = [];
            }
            $data[$q]['outputs'][] = [
                'output' => $output->output,
                'activity' => $output->activity,
                'costable_item' => $output->costable_item ?? '',
                'planned' => (string) $output->planned,
                'planned_unit' => $output->planned_unit ?? '',
            ];
        }

        foreach ($data as $q => $block) {
            if (($block['outputs'] ?? []) === []) {
                $data[$q]['outputs'] = [['output' => '', 'activity' => '', 'costable_item' => '', 'planned' => '', 'planned_unit' => '']];
            }
        }

        return $data;
    }

    private function findDepartmentPlan(int $departmentId, int $planId): MeTechnicalPlan
    {
        return MeTechnicalPlan::query()
            ->where('department_id', $departmentId)
            ->whereNull('budget_request_id')
            ->findOrFail($planId);
    }

    private function moduleKey(Request $request): string
    {
        $name = (string) $request->route()?->getName();
        $module = explode('.', $name)[0] ?? '';

        if ($module === '' || ! isset(DepartmentBudgetingService::MODULES[$module])) {
            abort(404);
        }

        return $module;
    }
}
