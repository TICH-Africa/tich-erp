<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Models\User;
use App\Services\Administration\AdministrationService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ApprovalWorkflowController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function index(): View
    {
        $queue = Schema::hasTable('admin_budget_requests')
            ? BudgetRequest::query()
                ->with(['department', 'planningCycle'])
                ->whereIn('status', ['submitted', 'draft', 'finance_review', 'executive_review'])
                ->orderBy('submitted_at')
                ->paginate(20)
            : collect();

        $items = $queue instanceof Paginator ? collect($queue->items()) : $queue;

        return view('ceo.approvals.index', [
            'queue' => $queue,
            'submitters' => $this->submittersFor($items),
        ]);
    }

    public function show(BudgetRequest $budgetRequest): View
    {
        $budgetRequest->load(['department', 'planningCycle']);

        return view('ceo.approvals.show', [
            'budgetRequest' => $budgetRequest,
            'submitter' => $this->resolveSubmitter($budgetRequest->submitted_by),
            'canAct' => in_array($budgetRequest->status, ['submitted', 'draft'], true),
            'canAuthorize' => $budgetRequest->status === 'executive_review',
        ]);
    }

    public function review(Request $httpRequest, BudgetRequest $budgetRequest): RedirectResponse
    {
        $data = $httpRequest->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->admin->recordAdminReview($budgetRequest, $data['notes'], auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'Review notes saved.');
    }

    public function routeToFinance(BudgetRequest $budgetRequest): RedirectResponse
    {
        try {
            $this->admin->routeBudgetToFinance($budgetRequest, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()
            ->route('ceo.approvals.index')
            ->with('status', 'Routed to Finance for verification.');
    }

    public function returnToSender(Request $httpRequest, BudgetRequest $budgetRequest): RedirectResponse
    {
        $data = $httpRequest->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->admin->returnBudgetToSender($budgetRequest, auth()->id(), $data['notes']);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()
            ->route('ceo.approvals.index')
            ->with('status', 'Request returned to the submitting department.');
    }

    public function reject(Request $httpRequest, BudgetRequest $budgetRequest): RedirectResponse
    {
        $data = $httpRequest->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->admin->rejectBudget($budgetRequest, auth()->id(), $data['notes'] ?? null);

        return redirect()
            ->route('ceo.approvals.index')
            ->with('status', 'Request rejected.');
    }

    /**
     * @param  Collection<int, BudgetRequest>  $queue
     * @return array<int, array{name: string, email: ?string}>
     */
    private function submittersFor(Collection $queue): array
    {
        $ids = $queue->pluck('submitted_by')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (User $user) => [
                (int) $user->id => [
                    'name' => $user->displayName(),
                    'email' => $user->email,
                ],
            ])
            ->all();
    }

    /**
     * @return array{name: string, email: ?string}|null
     */
    private function resolveSubmitter(?int $userId): ?array
    {
        if (! $userId) {
            return null;
        }

        $user = User::query()->find($userId);

        return $user ? [
            'name' => $user->displayName(),
            'email' => $user->email,
        ] : null;
    }
}
