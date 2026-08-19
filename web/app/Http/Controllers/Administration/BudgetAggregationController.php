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
            'framework' => ['required', 'in:standard,cbe'],
            'budget_type' => ['nullable', 'in:annual,quarterly,monthly,weekly'],
            'requested_amount' => ['required', 'numeric', 'min:0'],
            'line_items' => ['nullable', 'json'],
            'cbe_competencies' => ['nullable', 'string', 'max:5000'],
            'assessment_hours' => ['nullable', 'numeric', 'min:0'],
            'consumables_per_cohort' => ['nullable', 'string', 'max:5000'],
            'justification' => ['nullable', 'string', 'max:3000'],
        ]);

        $data['standard_line_items'] = $data['framework'] === 'standard' && ! empty($data['line_items'])
            ? json_decode($data['line_items'], true, 512, JSON_THROW_ON_ERROR)
            : null;
        $data['cbe_details'] = $data['framework'] === 'cbe'
            ? array_filter([
                'competencies' => $data['cbe_competencies'] ?? null,
                'assessment_hours' => $data['assessment_hours'] ?? null,
                'consumables_per_cohort' => $data['consumables_per_cohort'] ?? null,
            ], static fn ($value) => $value !== null && $value !== '')
            : null;

        try {
            $this->admin->createBudgetRequest($data, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['budget' => $e->getMessage()]);
        }

        return back()->with('status', 'Budget request submitted for workflow routing.');
    }
}
