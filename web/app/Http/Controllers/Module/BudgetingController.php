<?php

namespace App\Http\Controllers\Module;

use App\Http\Controllers\Controller;
use App\Services\DepartmentBudgetingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetingController extends Controller
{
    public function __construct(
        protected DepartmentBudgetingService $budgeting,
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

        return view('module-budgeting.create', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'cycles' => $this->budgeting->openCycles(),
            'lines' => $this->defaultLines(),
            'budgetRequest' => null,
            'formAction' => route($routes['store']),
            'submitLabel' => 'Submit to Administration',
            'pageTitle' => 'New budget request',
            'indexRoute' => $routes['index'],
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

        return view('module-budgeting.create', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'cycles' => $this->budgeting->openCycles(),
            'lines' => $lines,
            'budgetRequest' => $record,
            'formAction' => route($routes['update'], $record->id),
            'submitLabel' => 'Resubmit to Administration',
            'pageTitle' => 'Revise budget request',
            'indexRoute' => $routes['index'],
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

        $data = $request->validate([
            'planning_cycle_id' => ['nullable', 'exists:admin_planning_cycles,id'],
            'title' => ['required', 'string', 'max:300'],
            'budget_type' => ['nullable', 'in:annual,quarterly,monthly,weekly'],
            'justification' => ['nullable', 'string', 'max:3000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.description' => ['nullable', 'string', 'max:2000'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
        ], [
            'lines.required' => 'Add at least one budget line item.',
            'lines.*.item.required' => 'Each line needs an item name.',
            'lines.*.quantity.required' => 'Each line needs a quantity.',
            'lines.*.unit_price.required' => 'Each line needs a price per item.',
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
                $this->budgeting->resubmit($record, $department, $payload, $request->user());
                $message = 'Budget request revised and resubmitted to Administration.';
            } else {
                $this->budgeting->submit($department, $payload, $request->user());
                $message = 'Budget request submitted to Administration for aggregation and workflow routing.';
            }
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
