<?php

namespace App\Http\Controllers\Module;

use App\Http\Controllers\Controller;
use App\Models\Me\MeTechnicalPlan;
use App\Services\DepartmentBudgetingService;
use App\Services\Me\MePolicyService;
use App\Services\Me\MeTechnicalPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetingController extends Controller
{
    public function __construct(
        protected DepartmentBudgetingService $budgeting,
        protected MePolicyService $mePolicies,
        protected MeTechnicalPlanService $mePlans,
    ) {}

    public function index(Request $request): View
    {
        $module = $this->moduleKey($request);
        $context = $this->budgeting->moduleContext($module);
        $department = $this->budgeting->departmentForModule($module);
        $routes = $this->budgeting->routeNames($module);

        return view('module-budgeting.index', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'requests' => $this->budgeting->requestsForDepartment($department),
            'indexRoute' => $routes['index'],
            'createRoute' => $routes['create'],
            'storeRoute' => $routes['store'],
            'editRoute' => $routes['edit'],
        ]);
    }

    public function create(Request $request): View
    {
        $module = $this->moduleKey($request);
        $context = $this->budgeting->moduleContext($module);
        $department = $this->budgeting->departmentForModule($module);
        $routes = $this->budgeting->routeNames($module);

        $policy = $this->mePolicies->currentPublishedPolicy();
        $policySigned = $policy
            ? $this->mePolicies->userMaySubmitBudget($request->user(), $department)
            : true;

        return view('module-budgeting.create', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'cycles' => $this->budgeting->openCycles(),
            'lines' => $this->defaultLines(),
            'planOutputs' => $this->defaultPlanOutputs(),
            'budgetRequest' => null,
            'formAction' => route($routes['store']),
            'submitLabel' => 'Submit budget & technical plan',
            'pageTitle' => 'New budget & departmental plan',
            'indexRoute' => $routes['index'],
            'mePolicy' => $policy,
            'mePolicySigned' => $policySigned,
        ]);
    }

    public function edit(Request $request, int $budgetRequest): View
    {
        $module = $this->moduleKey($request);
        $context = $this->budgeting->moduleContext($module);
        $department = $this->budgeting->departmentForModule($module);
        $routes = $this->budgeting->routeNames($module);
        $record = $this->budgeting->findDepartmentRequest($department, $budgetRequest);

        abort_unless($record->status === 'returned', 403, 'Only returned requests can be revised.');

        $lines = old('lines');
        if (! is_array($lines) || $lines === []) {
            $stored = is_array($record->standard_line_items) ? $record->standard_line_items : [];
            $lines = $stored !== [] ? $stored : $this->defaultLines();
        }

        $existingPlan = MeTechnicalPlan::query()
            ->with('outputs')
            ->where('budget_request_id', $record->id)
            ->first();

        $planOutputs = old('plan_outputs');
        if (! is_array($planOutputs) || $planOutputs === []) {
            $planOutputs = $existingPlan
                ? $existingPlan->outputs->map(fn ($o) => [
                    'output' => $o->output,
                    'activity' => $o->activity,
                    'costable_item' => $o->costable_item,
                    'planned' => (string) $o->planned,
                    'planned_unit' => $o->planned_unit,
                ])->all()
                : $this->defaultPlanOutputs();
        }

        $policy = $this->mePolicies->currentPublishedPolicy();
        $policySigned = $policy
            ? $this->mePolicies->userMaySubmitBudget($request->user(), $department)
            : true;

        return view('module-budgeting.create', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'cycles' => $this->budgeting->openCycles(),
            'lines' => $lines,
            'planOutputs' => $planOutputs,
            'budgetRequest' => $record,
            'formAction' => route($routes['update'], $record->id),
            'submitLabel' => 'Resubmit budget & technical plan',
            'pageTitle' => 'Revise budget & departmental plan',
            'indexRoute' => $routes['index'],
            'mePolicy' => $policy,
            'mePolicySigned' => $policySigned,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->persist($request);
    }

    public function update(Request $request, int $budgetRequest): RedirectResponse
    {
        return $this->persist($request, $budgetRequest);
    }

    private function persist(Request $request, ?int $budgetRequestId = null): RedirectResponse
    {
        $module = $this->moduleKey($request);
        $department = $this->budgeting->departmentForModule($module);

        if (! $this->mePolicies->userMaySubmitBudget($request->user(), $department)) {
            return back()->withInput()->withErrors([
                'budget' => $this->mePolicies->gateMessage($department),
            ]);
        }

        $data = $request->validate([
            'planning_cycle_id' => ['nullable', 'exists:admin_planning_cycles,id'],
            'title' => ['required', 'string', 'max:300'],
            'budget_type' => ['nullable', 'in:annual,quarterly,monthly,weekly'],
            'justification' => ['nullable', 'string', 'max:3000'],
            'plan_summary' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.description' => ['nullable', 'string', 'max:2000'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
            'plan_outputs' => ['required', 'array', 'min:1'],
            'plan_outputs.*.output' => ['required', 'string', 'max:2000'],
            'plan_outputs.*.activity' => ['required', 'string', 'max:2000'],
            'plan_outputs.*.costable_item' => ['nullable', 'string', 'max:500'],
            'plan_outputs.*.planned' => ['required', 'numeric', 'min:0'],
            'plan_outputs.*.planned_unit' => ['nullable', 'string', 'max:50'],
        ], [
            'lines.required' => 'Add at least one budget line item.',
            'lines.*.item.required' => 'Each line needs an item name.',
            'lines.*.quantity.required' => 'Each line needs a quantity.',
            'lines.*.unit_price.required' => 'Each line needs a price per item.',
            'plan_outputs.required' => 'Add at least one technical plan output for M&E routing.',
            'plan_outputs.*.output.required' => 'Each plan row needs an output.',
            'plan_outputs.*.activity.required' => 'Each plan row needs an activity.',
            'plan_outputs.*.planned.required' => 'Each plan row needs a planned baseline value.',
        ]);

        [$lineItems, $requestedAmount] = $this->normalizeLines($data['lines']);

        if ($requestedAmount <= 0) {
            return back()->withInput()->withErrors([
                'lines' => 'The budget total must be greater than zero.',
            ]);
        }

        $payload = [
            'planning_cycle_id' => $data['planning_cycle_id'] ?? null,
            'title' => $data['title'],
            'framework' => 'standard',
            'budget_type' => $data['budget_type'] ?? null,
            'requested_amount' => $requestedAmount,
            'standard_line_items' => $lineItems,
            'cbe_details' => null,
            'justification' => $data['justification'] ?? null,
        ];

        try {
            if ($budgetRequestId) {
                $record = $this->budgeting->findDepartmentRequest($department, $budgetRequestId);
                $record = $this->budgeting->resubmit($record, $department, $payload, $request->user());
                $message = 'Budget revised (Admin/Finance) and technical plan re-routed to M&E concurrently.';
            } else {
                $record = $this->budgeting->submit($department, $payload, $request->user());
                $message = 'Dual submission complete: budget → Administration/Finance; technical plan → M&E.';
            }

            $this->mePlans->ingestFromBudgetSubmission(
                $record,
                $department,
                $request->user(),
                $data['plan_outputs'],
                $data['plan_summary'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['budget' => $e->getMessage()]);
        }

        return redirect()
            ->route($this->budgeting->routeNames($module)['index'])
            ->with('status', $message);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{0: list<array<string, mixed>>, 1: float}
     */
    private function normalizeLines(array $lines): array
    {
        $lineItems = [];
        $requestedAmount = 0.0;

        foreach ($lines as $line) {
            $quantity = round((float) $line['quantity'], 4);
            $unitPrice = round((float) $line['unit_price'], 2);
            $total = round($quantity * $unitPrice, 2);
            $requestedAmount += $total;

            $lineItems[] = [
                'item' => trim((string) $line['item']),
                'quantity' => $quantity,
                'description' => trim((string) ($line['description'] ?? '')),
                'unit_price' => $unitPrice,
                'unit_of_measure' => trim((string) ($line['unit_of_measure'] ?? '')) ?: null,
                'total' => $total,
            ];
        }

        return [$lineItems, $requestedAmount];
    }

    /**
     * @return list<array{item: string, quantity: string, description: string, unit_price: string, unit_of_measure: string}>
     */
    private function defaultLines(): array
    {
        $oldLines = old('lines');
        if (is_array($oldLines) && $oldLines !== []) {
            return $oldLines;
        }

        return [
            ['item' => '', 'quantity' => '1', 'description' => '', 'unit_price' => '', 'unit_of_measure' => ''],
        ];
    }

    /**
     * @return list<array{output: string, activity: string, costable_item: string, planned: string, planned_unit: string}>
     */
    private function defaultPlanOutputs(): array
    {
        $old = old('plan_outputs');
        if (is_array($old) && $old !== []) {
            return $old;
        }

        return [
            ['output' => '', 'activity' => '', 'costable_item' => '', 'planned' => '', 'planned_unit' => ''],
        ];
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
