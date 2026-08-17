<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Services\Administration\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ApprovalWorkflowController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function index(): View
    {
        $queue = Schema::hasTable('admin_budget_requests')
            ? BudgetRequest::query()
                ->with('department')
                ->whereIn('status', ['submitted', 'finance_review', 'executive_review'])
                ->orderBy('submitted_at')
                ->paginate(20)
            : collect();

        return view('administration.approvals.index', [
            'queue' => $queue,
        ]);
    }

    public function routeToFinance(BudgetRequest $budgetRequest): RedirectResponse
    {
        try {
            $this->admin->routeBudgetToFinance($budgetRequest, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'Routed to Finance for verification.');
    }

    public function financeVerify(Request $httpRequest, BudgetRequest $budgetRequest): RedirectResponse
    {
        $data = $httpRequest->validate([
            'verified_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->admin->verifyBudgetByFinance($budgetRequest, (float) $data['verified_amount'], $httpRequest->user()->id, $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'Finance verified - sent to Executive/CEO.');
    }

    public function executiveAuthorize(Request $httpRequest, BudgetRequest $budgetRequest): RedirectResponse
    {
        $data = $httpRequest->validate([
            'approved_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->admin->authorizeBudgetByExecutive($budgetRequest, (float) $data['approved_amount'], $httpRequest->user()->id, $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'Executive authorization complete.');
    }

    public function reject(Request $httpRequest, BudgetRequest $budgetRequest): RedirectResponse
    {
        $data = $httpRequest->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->admin->rejectBudget($budgetRequest, $httpRequest->user()->id, $data['notes'] ?? null);

        return back()->with('status', 'Request rejected.');
    }
}
