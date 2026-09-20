<?php

namespace App\Http\Controllers\Module;

use App\Http\Controllers\Controller;
use App\Services\DepartmentBudgetingService;
use App\Services\Finance\FinancePolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetingController extends Controller
{
    public const ANNUAL_QUARTERS = [
        'q1' => 'Q1 · Jan–Mar',
        'q2' => 'Q2 · Apr–Jun',
        'q3' => 'Q3 · Jul–Sep',
        'q4' => 'Q4 · Oct–Dec',
    ];

    public function __construct(
        protected DepartmentBudgetingService $budgeting,
        protected FinancePolicyService $financePolicies,
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

        $policy = $this->financePolicies->currentPublishedPolicy();
        $policySigned = $policy
            ? $this->financePolicies->userMaySubmitBudget($request->user(), $department)
            : true;

        return view('module-budgeting.create', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'cycles' => $this->budgeting->openCycles(),
            'quarters' => self::ANNUAL_QUARTERS,
            'quarterData' => $this->defaultQuarterData(),
            'lines' => $this->defaultFlatLines(),
            'budgetRequest' => null,
            'formAction' => route($routes['store']),
            'submitLabel' => 'Submit budget',
            'pageTitle' => 'New budget request',
            'indexRoute' => $routes['index'],
            'financePolicy' => $policy,
            'financePolicySigned' => $policySigned,
            'financePolicySignRoute' => \Illuminate\Support\Facades\Route::has($module.'.finance-policy.sign')
                ? $module.'.finance-policy.sign'
                : 'finance.financial-policies.sign',
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

        $quarterData = old('quarters');
        if (! is_array($quarterData) || $quarterData === []) {
            $stored = $record->annualQuartersPayload();
            $quarterData = $stored
                ? $this->quarterDataFromStored($stored)
                : $this->defaultQuarterData();
        }

        $lines = old('lines');
        if (! is_array($lines) || $lines === []) {
            $stored = $record->annualQuartersPayload();
            $lines = $stored
                ? $this->defaultFlatLines()
                : (($record->expenditureLines() !== [])
                    ? $record->expenditureLines()
                    : $this->defaultFlatLines());
        }

        $policy = $this->financePolicies->currentPublishedPolicy();
        $policySigned = $policy
            ? $this->financePolicies->userMaySubmitBudget($request->user(), $department)
            : true;

        return view('module-budgeting.create', [
            'module' => $module,
            'moduleContext' => $context,
            'department' => $department,
            'cycles' => $this->budgeting->openCycles(),
            'quarters' => self::ANNUAL_QUARTERS,
            'quarterData' => $quarterData,
            'lines' => $lines,
            'budgetRequest' => $record,
            'formAction' => route($routes['update'], $record->id),
            'submitLabel' => 'Resubmit budget',
            'pageTitle' => 'Revise budget request',
            'indexRoute' => $routes['index'],
            'financePolicy' => $policy,
            'financePolicySigned' => $policySigned,
            'financePolicySignRoute' => \Illuminate\Support\Facades\Route::has($module.'.finance-policy.sign')
                ? $module.'.finance-policy.sign'
                : 'finance.financial-policies.sign',
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

        if (! $this->financePolicies->userMaySubmitBudget($request->user(), $department)) {
            return back()->withInput()->withErrors([
                'budget' => $this->financePolicies->gateMessage($department),
            ]);
        }

        $budgetType = $request->input('budget_type', 'annual') ?: 'annual';

        $rules = [
            'planning_cycle_id' => ['required', 'exists:admin_planning_cycles,id'],
            'title' => ['required', 'string', 'max:300'],
            'budget_type' => ['required', 'in:annual,quarterly,monthly,weekly'],
            'justification' => ['nullable', 'string', 'max:3000'],
        ];

        if ($budgetType === 'annual') {
            $rules = array_merge($rules, [
                'quarters' => ['required', 'array'],
                'quarters.q1' => ['required', 'array'],
                'quarters.q2' => ['required', 'array'],
                'quarters.q3' => ['required', 'array'],
                'quarters.q4' => ['required', 'array'],
                'quarters.*.income' => ['required', 'array', 'min:1'],
                'quarters.*.income.*.source' => ['nullable', 'string', 'max:255'],
                'quarters.*.income.*.amount' => ['nullable', 'numeric', 'min:0'],
                'quarters.*.expenditure' => ['required', 'array', 'min:1'],
                'quarters.*.expenditure.*.item' => ['nullable', 'string', 'max:255'],
                'quarters.*.expenditure.*.quantity' => ['nullable', 'numeric', 'min:0'],
                'quarters.*.expenditure.*.description' => ['nullable', 'string', 'max:2000'],
                'quarters.*.expenditure.*.unit_price' => ['nullable', 'numeric', 'min:0'],
                'quarters.*.expenditure.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'lines' => ['required', 'array', 'min:1'],
                'lines.*.item' => ['required', 'string', 'max:255'],
                'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
                'lines.*.description' => ['nullable', 'string', 'max:2000'],
                'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
                'lines.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
            ]);
        }

        $data = $request->validate($rules, [
            'planning_cycle_id.required' => 'Select a planning cycle. The fiscal year is taken from that cycle.',
            'lines.required' => 'Add at least one budget line item.',
            'quarters.required' => 'Complete income and expenditure for each quarter.',
        ]);

        if ($budgetType === 'annual') {
            [$lineItems, $requestedAmount] = $this->normalizeAnnualQuarters($data['quarters']);
        } else {
            [$lineItems, $requestedAmount] = $this->normalizeFlatLines($data['lines']);
        }

        if ($requestedAmount <= 0) {
            return back()->withInput()->withErrors([
                'budget' => 'Expenditure total must be greater than zero.',
            ]);
        }

        $payload = [
            'planning_cycle_id' => $data['planning_cycle_id'],
            'title' => $data['title'],
            'framework' => 'standard',
            'budget_type' => $budgetType,
            'requested_amount' => $requestedAmount,
            'standard_line_items' => $lineItems,
            'cbe_details' => null,
            'justification' => $data['justification'] ?? null,
        ];

        try {
            if ($budgetRequestId) {
                $record = $this->budgeting->findDepartmentRequest($department, $budgetRequestId);
                $this->budgeting->resubmit($record, $department, $payload, $request->user());
                $message = 'Budget revised and re-submitted to Administration/Finance.';
            } else {
                $this->budgeting->submit($department, $payload, $request->user());
                $message = 'Budget submitted to Administration/Finance.';
            }
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['budget' => $e->getMessage()]);
        }

        return redirect()
            ->route($this->budgeting->routeNames($module)['index'])
            ->with('status', $message);
    }

    /**
     * @param  array<string, mixed>  $quarters
     * @return array{0: array<string, mixed>, 1: float}
     */
    private function normalizeAnnualQuarters(array $quarters): array
    {
        $payloadQuarters = [];
        $flatLines = [];
        $incomeGrand = 0.0;
        $expenditureGrand = 0.0;

        foreach (array_keys(self::ANNUAL_QUARTERS) as $key) {
            $block = is_array($quarters[$key] ?? null) ? $quarters[$key] : [];
            $incomeRows = [];
            $incomeTotal = 0.0;

            foreach (array_values($block['income'] ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $source = trim((string) ($row['source'] ?? ''));
                $amount = round((float) ($row['amount'] ?? 0), 2);
                if ($source === '' && $amount <= 0) {
                    continue;
                }
                $incomeRows[] = [
                    'source' => $source !== '' ? $source : 'Unnamed source',
                    'amount' => $amount,
                ];
                $incomeTotal += $amount;
            }

            if ($incomeRows === []) {
                $incomeRows[] = ['source' => '', 'amount' => 0.0];
            }

            $expenditureRows = [];
            $expenditureTotal = 0.0;

            foreach (array_values($block['expenditure'] ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $item = trim((string) ($row['item'] ?? ''));
                $quantity = round((float) ($row['quantity'] ?? 0), 4);
                $unitPrice = round((float) ($row['unit_price'] ?? 0), 2);
                if ($item === '' && $quantity <= 0 && $unitPrice <= 0) {
                    continue;
                }
                if ($item === '') {
                    continue;
                }
                if ($quantity <= 0) {
                    $quantity = 1.0;
                }
                $total = round($quantity * $unitPrice, 2);
                $normalized = [
                    'item' => $item,
                    'quantity' => $quantity,
                    'description' => trim((string) ($row['description'] ?? '')),
                    'unit_price' => $unitPrice,
                    'unit_of_measure' => trim((string) ($row['unit_of_measure'] ?? '')) ?: null,
                    'total' => $total,
                    'quarter' => $key,
                ];
                $expenditureRows[] = $normalized;
                $flatLines[] = $normalized;
                $expenditureTotal += $total;
            }

            if ($expenditureRows === []) {
                $expenditureRows[] = [
                    'item' => '',
                    'quantity' => 1.0,
                    'description' => '',
                    'unit_price' => 0.0,
                    'unit_of_measure' => null,
                    'total' => 0.0,
                    'quarter' => $key,
                ];
            }

            $payloadQuarters[$key] = [
                'income' => $incomeRows,
                'income_total' => round($incomeTotal, 2),
                'expenditure' => $expenditureRows,
                'expenditure_total' => round($expenditureTotal, 2),
            ];
            $incomeGrand += $incomeTotal;
            $expenditureGrand += $expenditureTotal;
        }

        return [[
            'format' => 'annual_quarters_v1',
            'quarters' => $payloadQuarters,
            'income_grand_total' => round($incomeGrand, 2),
            'expenditure_grand_total' => round($expenditureGrand, 2),
            'lines' => $flatLines,
        ], round($expenditureGrand, 2)];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{0: list<array<string, mixed>>, 1: float}
     */
    private function normalizeFlatLines(array $lines): array
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
     * @return array<string, array{income: list<array{source: string, amount: string}>, expenditure: list<array{item: string, quantity: string, description: string, unit_price: string, unit_of_measure: string}>}>
     */
    private function defaultQuarterData(): array
    {
        $old = old('quarters');
        if (is_array($old) && $old !== []) {
            return $old;
        }

        $emptyIncome = [['source' => '', 'amount' => '']];
        $emptyExp = [['item' => '', 'quantity' => '1', 'description' => '', 'unit_price' => '', 'unit_of_measure' => '']];
        $data = [];
        foreach (array_keys(self::ANNUAL_QUARTERS) as $key) {
            $data[$key] = [
                'income' => $emptyIncome,
                'expenditure' => $emptyExp,
            ];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $stored
     * @return array<string, array{income: list<array<string, mixed>>, expenditure: list<array<string, mixed>>}>
     */
    private function quarterDataFromStored(array $stored): array
    {
        $data = [];
        foreach (array_keys(self::ANNUAL_QUARTERS) as $key) {
            $block = is_array($stored['quarters'][$key] ?? null) ? $stored['quarters'][$key] : [];
            $income = array_values(array_filter(
                is_array($block['income'] ?? null) ? $block['income'] : [],
                static fn ($r) => is_array($r)
            ));
            $expenditure = array_values(array_filter(
                is_array($block['expenditure'] ?? null) ? $block['expenditure'] : [],
                static fn ($r) => is_array($r)
            ));
            $data[$key] = [
                'income' => $income !== [] ? $income : [['source' => '', 'amount' => '']],
                'expenditure' => $expenditure !== [] ? $expenditure : [[
                    'item' => '', 'quantity' => '1', 'description' => '', 'unit_price' => '', 'unit_of_measure' => '',
                ]],
            ];
        }

        return $data;
    }

    /**
     * @return list<array{item: string, quantity: string, description: string, unit_price: string, unit_of_measure: string}>
     */
    private function defaultFlatLines(): array
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
