<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Models\Administration\FundAllocation;
use App\Services\Administration\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FundDistributionController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function index(): View
    {
        $allocations = Schema::hasTable('admin_fund_allocations')
            ? FundAllocation::query()->with(['department', 'budgetRequest'])->latest()->paginate(20)
            : collect();

        $approved = Schema::hasTable('admin_budget_requests')
            ? BudgetRequest::query()
                ->with(['department', 'planningCycle'])
                ->whereIn('status', ['submitted', 'finance_review', 'executive_review', 'approved', 'disbursed'])
                ->orderByRaw("FIELD(status, 'submitted', 'finance_review', 'executive_review', 'approved', 'disbursed')")
                ->latest()
                ->get()
            : collect();

        return view('administration.fund-distribution.index', [
            'allocations' => $allocations,
            'approvedRequests' => $approved,
            'departments' => $this->admin->departments(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'budget_request_id' => ['nullable', 'exists:admin_budget_requests,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->admin->releaseFundAllocation($data, $request->user()->id);
        } catch (\RuntimeException $exception) {
            return back()->withInput()->withErrors(['allocation' => $exception->getMessage()]);
        }

        return back()->with('status', 'Monthly allocation released digitally to the department.');
    }

    public function markAsDisbursed(Request $request, $id): RedirectResponse
    {
        $budgetRequest = BudgetRequest::query()->findOrFail($id);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $budgetRequest->update([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'disbursed_by' => $request->user()->id,
            'workflow_notes' => trim(($budgetRequest->workflow_notes ? $budgetRequest->workflow_notes."\n" : '').($request->input('notes') ?: 'Marked as disbursed by Administration.')),
        ]);

        return back()->with('status', 'Budget request marked as disbursed successfully.');
    }

    public function markAllocationAsDisbursed(Request $request, FundAllocation $allocation): RedirectResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $allocation->update([
            'status' => 'disbursed',
            'released_at' => now(),
            'notes' => $request->input('notes') ? trim($allocation->notes.' '.$request->input('notes')) : $allocation->notes,
        ]);

        return back()->with('status', 'Allocation marked as disbursed successfully.');
    }
}
