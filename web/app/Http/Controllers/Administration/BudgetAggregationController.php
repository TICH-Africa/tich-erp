<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Services\Administration\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BudgetAggregationController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function index(Request $request): View
    {
        $fiscalYear = (int) ($request->query('fiscal_year') ?: now()->year);
        $aggregation = Schema::hasTable('admin_budget_requests')
            ? $this->admin->aggregatedBudgets($fiscalYear)
            : ['by_department' => [], 'totals' => ['requested' => 0, 'verified' => 0, 'approved' => 0, 'cbe_share' => 0]];

        $requests = Schema::hasTable('admin_budget_requests')
            ? BudgetRequest::query()->with(['department', 'planningCycle'])->latest()->paginate(20)
            : collect();

        return view('administration.budget-aggregation.index', [
            'fiscalYear' => $fiscalYear,
            'aggregation' => $aggregation,
            'requests' => $requests,
            'departments' => $this->admin->departments(),
            'cycles' => Schema::hasTable('admin_planning_cycles')
                ? \App\Models\Administration\PlanningCycle::query()->where('status', 'open')->orderByDesc('id')->get()
                : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'planning_cycle_id' => ['nullable', 'exists:admin_planning_cycles,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:300'],
            'framework' => ['nullable', 'in:standard,cbe'],
            'budget_type' => ['nullable', 'in:annual,quarterly,monthly,weekly'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.description' => ['nullable', 'string', 'max:2000'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
            'justification' => ['nullable', 'string', 'max:3000'],
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

        $framework = $data['framework'] ?? 'standard';

        $payload = [
            'planning_cycle_id' => $data['planning_cycle_id'] ?? null,
            'title' => $data['title'],
            'framework' => $framework,
            'budget_type' => $data['budget_type'] ?? null,
            'requested_amount' => $requestedAmount,
            'standard_line_items' => $framework === 'standard' ? $lineItems : null,
            'cbe_details' => $framework === 'cbe' ? [] : null,
            'justification' => $data['justification'] ?? null,
        ];

        try {
            $this->admin->createBudgetRequest($payload, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['budget' => $e->getMessage()]);
        }

        return back()->with('status', 'Budget request submitted for workflow routing.');
    }

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
}
