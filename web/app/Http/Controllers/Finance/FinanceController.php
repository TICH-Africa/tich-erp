<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AccountsPayable;
use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use App\Models\Department;
use App\Models\DonorProject;
use App\Models\FinanceBudget;
use App\Services\DepartmentDashboardService;
use App\Services\Finance\LedgerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(
        protected DepartmentDashboardService $departmentDashboard,
        protected LedgerService $ledger,
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
        return $this->departmentView($request, 'finance.ap.create', $department);
    }

    public function apStore(Request $request, Department $department)
    {
        //
    }

    public function apShow(Request $request, Department $department, AccountsPayable $ap): View
    {
        $ap->load(['supplier', 'purchaseOrder']);

        return $this->departmentView($request, 'finance.ap.show', $department, [
            'payable' => $ap,
        ]);
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

        return $this->departmentView($request, 'finance.budgeting.index', $department, compact('budgets', 'search'));
    }

    public function budgetingCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.budgeting.create', $department, [
            'departments' => Department::query()->main()->where('is_active', 1)->orderBy('dept_name')->get(['id', 'dept_name']),
        ]);
    }

    public function budgetingStore(Request $request, Department $department)
    {
        //
    }

    public function budgetingShow(Request $request, Department $department, FinanceBudget $budgeting): View
    {
        $budgeting->load(['department', 'approver']);

        return $this->departmentView($request, 'finance.budgeting.show', $department, [
            'budget' => $budgeting,
        ]);
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
}
