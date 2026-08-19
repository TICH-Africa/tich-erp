<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Services\Administration\AdministrationService;
use App\Services\RBACService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ApprovalWorkflowController extends Controller
{
    public function __construct(protected AdministrationService $admin, protected RBACService $rbac) {}

    public function index(): View
    {
        $queue = Schema::hasTable('admin_budget_requests')
            ? BudgetRequest::query()
                ->with('department')
                ->whereIn('status', ['submitted', 'draft', 'executive_review'])
                ->orderBy('submitted_at')
                ->paginate(20)
            : collect();

        return view('administration.approvals.index', [
            'queue' => $queue,
        ]);
    }

    public function routeToFinance(BudgetRequest $budgetRequest): RedirectResponse
    {
        $this->requireRole(['Administration Manager', 'Finance Manager', 'Super Admin', 'CEO']);

        try {
            $this->admin->routeBudgetToFinance($budgetRequest, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'Routed to Finance for verification.');
    }

    public function reject(Request $httpRequest, BudgetRequest $budgetRequest): RedirectResponse
    {
        $data = $httpRequest->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->admin->rejectBudget($budgetRequest, auth()->id(), $data['notes'] ?? null);

        return back()->with('status', 'Request rejected.');
    }

    private function requireRole(array $roles): void
    {
        abort_unless($this->rbac->hasAnyRole(auth()->user(), $roles), 403, 'This approval action is restricted to the designated workflow owner.');
    }
}
