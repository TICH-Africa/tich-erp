<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Services\Administration\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BudgetAuthorizationController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: 'executive_review';
        $search = $request->string('search')->toString();

        $requests = Schema::hasTable('admin_budget_requests')
            ? BudgetRequest::query()
                ->with(['department', 'planningCycle'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($builder) use ($search) {
                        $builder->where('request_code', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('submitted_at')
                ->paginate(20)
                ->withQueryString()
            : collect();

        return view('ceo.budgets.index', [
            'requests' => $requests,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(BudgetRequest $budgetRequest): View
    {
        $budgetRequest->load(['department', 'planningCycle']);

        return view('ceo.budgets.show', [
            'budgetRequest' => $budgetRequest,
            'canAuthorize' => $budgetRequest->status === 'executive_review',
        ]);
    }

    public function approve(Request $request, BudgetRequest $budgetRequest): RedirectResponse
    {
        $validated = $request->validate([
            'approved_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->admin->authorizeBudgetByExecutive(
                $budgetRequest,
                (float) $validated['approved_amount'],
                auth()->id(),
                $validated['notes'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()
            ->route('ceo.budgets.show', $budgetRequest)
            ->with('status', 'Budget approved. Funds can now be disbursed.');
    }

    public function reject(Request $request, BudgetRequest $budgetRequest): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->admin->rejectBudget($budgetRequest, auth()->id(), $validated['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()
            ->route('ceo.budgets.index')
            ->with('status', 'Budget request rejected.');
    }
}
