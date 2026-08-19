<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AccountsPayable;
use App\Models\AccountLedger;
use App\Models\AuditLog;
use App\Models\ChartOfAccount;
use App\Models\Department;
use App\Models\DonorProject;
use App\Models\FinanceBudget;
use App\Models\FinanceBudgetCycle;
use App\Models\Administration\BudgetRequest;
use App\Models\Supplier;
use App\Services\Administration\AdministrationService;
use App\Services\DepartmentDashboardService;
use App\Services\Finance\LedgerService;
use App\Services\RBACService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(
        protected DepartmentDashboardService $departmentDashboard,
        protected LedgerService $ledger,
        protected RBACService $rbac,
    ) {}

    protected function departmentView(Request $request, string $view, Department $department, array $data = []): View
    {
        $sidebarNavigation = $this->departmentDashboard->sidebarNavigation($request->user(), $department);

        return view($view, array_merge([
            'department' => $department,
            'categoryLabel' => fn (Department $dept) => $this->departmentDashboard->categoryLabel($dept),
            'sidebarNavigation' => $sidebarNavigation,
        ], $data));
    }

    public function studentFinanceIndex(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.student-finance.index', $department);
    }

    public function studentFinanceCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.student-finance.create', $department);
    }

    public function studentFinanceStore(Request $request, Department $department)
    {
        //
    }

    public function studentFinanceShow(Request $request, Department $department, $id): View
    {
        return $this->departmentView($request, 'finance.student-finance.show', $department);
    }

    public function arIndex(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.ar.index', $department);
    }

    public function arCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.ar.create', $department);
    }

    public function arStore(Request $request, Department $department)
    {
        //
    }

    public function arShow(Request $request, Department $department, $id): View
    {
        return $this->departmentView($request, 'finance.ar.show', $department);
    }

    public function apIndex(Request $request, Department $department): View
    {
        $search = $request->string('search')->toString();

        $payables = AccountsPayable::query()
            ->with('supplier')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('supplier_name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('invoice_date')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total' => (int) AccountsPayable::query()->count(),
            'unpaid' => (int) AccountsPayable::query()->where('payment_status', 'unpaid')->count(),
            'outstanding' => (float) AccountsPayable::query()->sum('balance'),
        ];

        return $this->departmentView($request, 'finance.ap.index', $department, compact('payables', 'stats', 'search'));
    }

    public function apCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.ap.create', $department, [
            'suppliers' => Supplier::query()
                ->where('is_active', 1)
                ->orderBy('supplier_name')
                ->get(['id', 'supplier_name', 'supplier_code']),
        ]);
    }

    public function apStore(Request $request, Department $department)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'invoice_number' => ['required', 'string', 'max:50', 'unique:accounts_payable,invoice_number'],
            'invoice_amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoiceAmount = (float) $validated['invoice_amount'];
        $taxAmount = (float) ($validated['tax_amount'] ?? 0);
        $totalAmount = $invoiceAmount + $taxAmount;

        $payable = new AccountsPayable([
            'supplier_id' => $validated['supplier_id'],
            'invoice_number' => $validated['invoice_number'],
            'invoice_date' => now()->toDateString(),
            'due_date' => $validated['due_date'],
            'invoice_amount' => $invoiceAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => 0,
            'balance' => $totalAmount,
            'description' => $validated['description'] ?? null,
            'three_way_match_status' => 'pending',
            'finance_approval_status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $payable->save();

        return redirect()
            ->route('finance.ap.show', [$department, $payable])
            ->with('success', 'Supplier invoice created successfully.');
    }

    public function apShow(Request $request, Department $department, AccountsPayable $ap): View
    {
        $ap->load(['supplier', 'purchaseOrder']);

        return $this->departmentView($request, 'finance.ap.show', $department, [
            'payable' => $ap,
        ]);
    }

    public function suppliersWorkflow(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.suppliers.index', $department);
    }

    public function glIndex(Request $request, Department $department): View
    {
        $accounts = ChartOfAccount::query()
            ->where('is_active', 1)
            ->orderBy('account_code')
            ->get();

        $balances = $this->ledger->accountBalances();
        $entries = $this->ledger->recentEntries(100);

        return $this->departmentView($request, 'finance.gl.index', $department, [
            'accounts' => $accounts,
            'balances' => $balances,
            'entries' => $entries,
            'mainAccount' => config('finance.main_treasury_account'),
            'trialBalance' => $this->ledger->trialBalance(),
        ]);
    }

    public function glJournalCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.gl.create', $department, [
            'accounts' => ChartOfAccount::query()->where('is_active', 1)->orderBy('account_code')->get(),
        ]);
    }

    public function glJournalStore(Request $request, Department $department)
    {
        //
    }

    public function glShow(Request $request, Department $department, AccountLedger $gl): View
    {
        $gl->load('recorder');

        return $this->departmentView($request, 'finance.gl.show', $department, [
            'entry' => $gl,
        ]);
    }

    public function budgetingIndex(Request $request, Department $department): View
    {
        $search = $request->string('search')->toString();

        $budgets = FinanceBudget::query()
            ->with('department')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('budget_code', 'like', "%{$search}%")
                        ->orWhere('budget_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('fiscal_year')
            ->orderBy('budget_name')
            ->paginate(25)
            ->withQueryString();

        $forwardedRequests = BudgetRequest::query()
            ->with(['department', 'planningCycle'])
            ->whereIn('status', ['finance_review', 'executive_review'])
            ->orderByDesc('submitted_at')
            ->get();

        return $this->departmentView($request, 'finance.budgeting.index', $department, compact('budgets', 'search', 'forwardedRequests'));
    }

    public function budgetingCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.budgeting.create', $department, [
            'departments' => Department::query()->main()->where('is_active', 1)->orderBy('dept_name')->get(['id', 'dept_name']),
        ]);
    }

    public function budgetingStore(Request $request, Department $department)
    {
        $validated = $request->validate([
            'budget_name' => ['required', 'string', 'max:300'],
            'budget_code' => ['required', 'string', 'max:100'],
            'budget_type' => ['required', 'in:annual,quarterly,monthly,weekly'],
            'department_id' => ['required', 'exists:departments,id'],
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_start' => ['required', 'date_format:d/m/Y'],
            'period_end' => ['required', 'date_format:d/m/Y', 'after:period_start'],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['period_start'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['period_start'])->format('Y-m-d');
        $validated['period_end'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['period_end'])->format('Y-m-d');

        $validated['status'] = 'active';
        $validated['spent_amount'] = 0;
        $validated['committed_amount'] = 0;

        FinanceBudget::query()->create($validated);

        return redirect()->route('finance.budgeting.index', $department)->with('status', 'Budget created successfully.');
    }

    public function budgetingShow(Request $request, Department $department, FinanceBudget $budgeting): View
    {
        $budgeting->load(['department', 'approver', 'cycles']);

        return $this->departmentView($request, 'finance.budgeting.show', $department, [
            'budget' => $budgeting,
        ]);
    }

    public function budgetingCycleStore(Request $request, Department $department, FinanceBudget $budget): RedirectResponse
    {
        abort_unless($this->rbac->hasAnyRole($request->user(), ['Finance Manager', 'CEO', 'Super Admin']), 403);

        $validated = $request->validate([
            'cycle_type' => ['required', 'in:annual,quarterly,monthly,weekly'],
            'label' => ['required', 'string', 'max:200'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after:period_start'],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $budget->cycles()->create($validated);

        return back()->with('status', 'Budget cycle added successfully.');
    }

    public function budgetingCycleUpdate(Request $request, Department $department, FinanceBudget $budget, FinanceBudgetCycle $cycle): RedirectResponse
    {
        abort_unless($this->rbac->hasAnyRole($request->user(), ['Finance Manager', 'CEO', 'Super Admin']), 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:200'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after:period_start'],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldAmount = (float) $cycle->allocated_amount;
        $newAmount = (float) $validated['allocated_amount'];

        $cycle->update($validated);

        if ($oldAmount !== $newAmount) {
            AuditLog::query()->create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'module' => 'finance',
                'entity_type' => 'budget_cycle',
                'entity_id' => $cycle->id,
                'old_value' => ['allocated_amount' => $oldAmount],
                'new_value' => ['allocated_amount' => $newAmount],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'success',
                'created_at' => now(),
            ]);
        }

        return back()->with('status', 'Budget cycle updated successfully.');
    }

    public function budgetingCycleDestroy(Request $request, Department $department, FinanceBudget $budget, FinanceBudgetCycle $cycle): RedirectResponse
    {
        abort_unless($this->rbac->hasAnyRole($request->user(), ['Finance Manager', 'CEO', 'Super Admin']), 403);

        $cycle->delete();

        return back()->with('status', 'Budget cycle removed.');
    }

    public function budgetingRequestsIndex(Request $request, Department $department): View
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $requests = BudgetRequest::query()
            ->with(['department', 'planningCycle'])
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('request_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return $this->departmentView($request, 'finance.budgeting.requests.index', $department, compact('requests', 'status', 'search'));
    }

    public function budgetingRequestShow(Request $request, Department $department, $id): View
    {
        $budgetRequest = BudgetRequest::query()->with(['department', 'planningCycle'])->findOrFail($id);

        return $this->departmentView($request, 'finance.budgeting.requests.show', $department, [
            'budgetRequest' => $budgetRequest,
        ]);
    }

    public function budgetingRequestReview(Request $request, Department $department, $id, AdministrationService $admin): RedirectResponse
    {
        $budgetRequest = BudgetRequest::query()->findOrFail($id);

        $validated = $request->validate([
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'group_allocations' => ['nullable', 'array'],
            'group_allocations.*.type' => ['required', 'string', 'max:50'],
            'group_allocations.*.label' => ['required', 'string', 'max:200'],
            'group_allocations.*.amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $groupAllocations = array_values(array_filter($validated['group_allocations'] ?? [], fn ($item) => $item['amount'] > 0));

        try {
            $admin->divideBudgetIntoGroups($budgetRequest, $groupAllocations, (float) $validated['allocated_amount']);
            $admin->forwardToExecutive($budgetRequest, auth()->id(), $validated['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'Budget reviewed, divided into groups, and forwarded to Executive/CEO.');
    }

    public function budgetingRequestReject(Request $request, Department $department, $id, AdministrationService $admin): RedirectResponse
    {
        $budgetRequest = BudgetRequest::query()->findOrFail($id);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $admin->rejectBudget($budgetRequest, auth()->id(), $validated['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'Budget request rejected.');
    }

    public function ceoApprove(Request $request, Department $department, $id, AdministrationService $admin): RedirectResponse
    {
        $budgetRequest = BudgetRequest::query()->findOrFail($id);

        $validated = $request->validate([
            'approved_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $admin->authorizeBudgetByExecutive($budgetRequest, (float) $validated['approved_amount'], auth()->id(), $validated['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'Budget approved. Funds can now be disbursed.');
    }

    public function markAsDisbursed(Request $request, Department $department, $id, AdministrationService $admin): RedirectResponse
    {
        $budgetRequest = BudgetRequest::query()->findOrFail($id);

        $validated = $request->validate([
            'receipt_number' => ['nullable', 'string', 'max:200'],
            'disbursed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $budgetRequest->update([
            'status' => 'disbursed',
            'disbursed_at' => $validated['disbursed_at'] ? now()->parse($validated['disbursed_at']) : now(),
            'disbursed_by' => auth()->id(),
            'receipt_number' => $validated['receipt_number'] ?? null,
            'workflow_notes' => trim(($budgetRequest->workflow_notes ? $budgetRequest->workflow_notes."\n" : '').($validated['notes'] ?: 'Marked as disbursed by Finance. Receipt: '.($validated['receipt_number'] ?? '').' Date: '.($validated['disbursed_at'] ?? now()->format('Y-m-d H:i')))),
        ]);

        return back()->with('status', 'Budget marked as disbursed successfully.');
    }

    public function projectsDonorsIndex(Request $request, Department $department): View
    {
        $search = $request->string('search')->toString();

        $projects = DonorProject::query()
            ->with('leader')
            ->withSum('disbursements as disbursed_kes', 'kes_amount')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('project_code', 'like', "%{$search}%")
                        ->orWhere('project_name', 'like', "%{$search}%")
                        ->orWhere('donor_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('start_date')
            ->paginate(25)
            ->withQueryString();

        return $this->departmentView($request, 'finance.projects-donors.index', $department, compact('projects', 'search'));
    }

    public function projectsDonorsCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.projects-donors.create', $department);
    }

    public function projectsDonorsStore(Request $request, Department $department)
    {
        //
    }

    public function projectsDonorsShow(Request $request, Department $department, DonorProject $projectDonor): View
    {
        $projectDonor->load(['leader', 'disbursements' => fn ($query) => $query->orderByDesc('receipt_date')]);

        return $this->departmentView($request, 'finance.projects-donors.show', $department, [
            'project' => $projectDonor,
        ]);
    }

    public function payrollIntegrationIndex(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.payroll-integration.index', $department);
    }

    public function payrollIntegrationSync(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.payroll-integration.create', $department);
    }

    public function payrollIntegrationStore(Request $request, Department $department)
    {
        //
    }

    public function payrollIntegrationShow(Request $request, Department $department, $id): View
    {
        return $this->departmentView($request, 'finance.payroll-integration.show', $department);
    }

    public function apiSuppliers(Request $request)
    {
        $search = $request->string('search')->toString();

        $suppliers = Supplier::query()
            ->where('is_active', 1)
            ->when($search !== '', fn ($query) => $query->where('supplier_name', 'like', "%{$search}%"))
            ->orderBy('supplier_name')
            ->get(['id', 'supplier_name', 'supplier_code']);

        return response()->json($suppliers);
    }
}
