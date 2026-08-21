<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Models\User;
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
                ->with(['department', 'planningCycle'])
                ->whereIn('status', ['submitted', 'draft', 'executive_review'])
                ->orderBy('submitted_at')
                ->paginate(20)
            : collect();

        $submitters = $this->submittersFor($queue instanceof \Illuminate\Support\Collection
            ? $queue
            : collect($queue->items()));

        return view('administration.approvals.index', [
            'queue' => $queue,
            'submitters' => $submitters,
        ]);
    }

    public function show(BudgetRequest $budgetRequest): View
    {
        $budgetRequest->load(['department', 'planningCycle']);

        $submitter = $this->resolveSubmitter($budgetRequest->submitted_by);

        return view('administration.approvals.show', [
            'budgetRequest' => $budgetRequest,
            'submitter' => $submitter,
            'canAct' => in_array($budgetRequest->status, ['submitted', 'draft'], true),
        ]);
    }

    public function review(Request $httpRequest, BudgetRequest $budgetRequest): RedirectResponse
    {
        $this->requireRole(['Administration Manager', 'Finance Manager', 'Super Admin', 'CEO']);

        $data = $httpRequest->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->admin->recordAdminReview($budgetRequest, $data['notes'], auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'Review notes saved. You can still forward to Finance or return to the sender.');
    }

    public function routeToFinance(BudgetRequest $budgetRequest): RedirectResponse
    {
        $this->requireRole(['Administration Manager', 'Finance Manager', 'Super Admin', 'CEO']);

        try {
            $this->admin->routeBudgetToFinance($budgetRequest, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()
            ->route('administration.approvals.index')
            ->with('status', 'Routed to Finance for verification.');
    }

    public function returnToSender(Request $httpRequest, BudgetRequest $budgetRequest): RedirectResponse
    {
        $this->requireRole(['Administration Manager', 'Finance Manager', 'Super Admin', 'CEO']);

        $data = $httpRequest->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->admin->returnBudgetToSender($budgetRequest, auth()->id(), $data['notes']);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()
            ->route('administration.approvals.index')
            ->with('status', 'Request returned to the submitting department for revision.');
    }

    public function reject(Request $httpRequest, BudgetRequest $budgetRequest): RedirectResponse
    {
        $this->requireRole(['Administration Manager', 'Finance Manager', 'Super Admin', 'CEO']);

        $data = $httpRequest->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->admin->rejectBudget($budgetRequest, auth()->id(), $data['notes'] ?? null);

        return redirect()
            ->route('administration.approvals.index')
            ->with('status', 'Request rejected.');
    }

    private function requireRole(array $roles): void
    {
        abort_unless($this->rbac->hasAnyRole(auth()->user(), $roles), 403, 'This approval action is restricted to the designated workflow owner.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BudgetRequest>  $requests
     * @return array<int, array{name: string, email: string|null}>
     */
    private function submittersFor($requests): array
    {
        $ids = $requests
            ->pluck('submitted_by')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $users = User::query()
            ->with('staff')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $map = [];
        foreach ($ids as $id) {
            $user = $users->get($id);
            $map[(int) $id] = $user
                ? $this->submitterPayload($user)
                : ['name' => 'Unknown user', 'email' => null];
        }

        return $map;
    }

    /**
     * @return array{name: string, email: string|null}|null
     */
    private function resolveSubmitter(?int $userId): ?array
    {
        if (! $userId) {
            return null;
        }

        $user = User::query()->with('staff')->find($userId);

        return $user ? $this->submitterPayload($user) : null;
    }

    /**
     * @return array{name: string, email: string|null}
     */
    private function submitterPayload(User $user): array
    {
        $staff = $user->staff;
        $email = collect([
            $staff?->organisation_email,
            $staff?->primary_email,
            $user->email,
        ])->first(static fn ($value) => is_string($value) && trim($value) !== '');

        return [
            'name' => $user->displayName(),
            'email' => $email ? trim((string) $email) : null,
        ];
    }
}
