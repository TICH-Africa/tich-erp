<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Models\Administration\FundAllocation;
use App\Models\Administration\PlanningCycle;
use App\Services\Administration\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PlanningController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function index(): View
    {
        $cycles = Schema::hasTable('admin_planning_cycles')
            ? PlanningCycle::query()->orderByDesc('fiscal_year')->orderByDesc('period_start')->paginate(20)
            : collect();

        return view('administration.planning.index', [
            'cycles' => $cycles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'plan_tier' => ['required', 'in:annual,monthly,weekly'],
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'requisition_deadline' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->admin->createPlanningCycle($data, $request->user()->id);

        return back()->with('status', 'Planning cycle created with requisition deadline.');
    }

    public function lock(PlanningCycle $cycle): RedirectResponse
    {
        $cycle->update(['status' => 'locked']);

        return back()->with('status', 'Planning cycle locked - no further requisitions.');
    }
}
