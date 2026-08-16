<?php

namespace App\Services\Finance;

use App\Events\FinanceSidebarCountsUpdated;
use App\Models\FeeStructure;
use App\Models\Finance\FinancialAdjustment;
use App\Models\Finance\InstallmentPlanItem;
use App\Models\Finance\PaymentMilestone;
use App\Models\Finance\Refund;
use App\Models\Invoice;
use App\Models\MpesaStkRequest;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceSidebarNotificationService
{
    public const CACHE_KEY = 'finance.sidebar.counts';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'student-finance' => 'Student Finance',
        'student-finance.adjustments' => 'Adjustments',
        'student-finance.invoices' => 'Invoices',
        'student-finance.fee-structures' => 'Fee structures',
        'student-finance.installments' => 'Installment plans',
        'student-finance.payments' => 'Payments',
        'finance-records' => 'Finance Records',
        'ar.overdue' => 'Accounts receivable',
        'ap.pending' => 'Accounts payable',
        'finance.mpesa' => 'M-Pesa / treasury',
        'employee-finance' => 'Employee Finance',
        'payroll-runs' => 'Payroll runs',
        'payroll-integration' => 'Payroll → GL integration',
    ];

    public function counts(bool $fresh = false): array
    {
        if ($fresh) {
            return $this->computeCounts();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts());
    }

    public function formattedCounts(bool $fresh = false): array
    {
        return collect($this->counts($fresh))
            ->mapWithKeys(fn (int $count, string $key) => [$key => $this->formatCount($count)])
            ->all();
    }

    public function formatCount(int $count): ?string
    {
        if ($count <= 0) {
            return null;
        }

        return $count > 99 ? '99+' : (string) $count;
    }

    public function broadcastCounts(): void
    {
        Cache::forget(self::CACHE_KEY);

        $counts = $this->counts(true);
        $labels = collect($counts)
            ->mapWithKeys(fn (int $count, string $key) => [$key => $this->formatCount($count)])
            ->all();

        broadcast(new FinanceSidebarCountsUpdated($counts, $labels));
    }

    /**
     * @return array<string, int>
     */
    private function computeCounts(): array
    {
        $pendingAdjustments = $this->pendingAdjustmentsCount();
        $openInvoices = $this->openInvoicesCount();
        $unapprovedFeeStructures = $this->unapprovedFeeStructuresCount();
        $installmentItemsDue = $this->installmentItemsNeedingActionCount();
        $milestonesDue = $this->paymentMilestonesNeedingActionCount();
        $pendingMpesa = $this->pendingMpesaRequestsCount();
        $pendingAp = $this->accountsPayableNeedingActionCount();
        $draftPayrollRuns = 0;
        $approvedPayrollRuns = 0;

        if (Schema::hasTable('payroll_runs')) {
            $draftPayrollRuns = PayrollRun::query()->where('status', PayrollRun::STATUS_DRAFT)->count();
            $approvedPayrollRuns = PayrollRun::query()->where('status', PayrollRun::STATUS_APPROVED)->count();
        }

        $studentFinance = $pendingAdjustments
            + $openInvoices
            + $unapprovedFeeStructures
            + $installmentItemsDue
            + $milestonesDue
            + $pendingMpesa;

        $financeRecords = $openInvoices + $pendingAp + $approvedPayrollRuns + $pendingMpesa;
        $employeeFinance = $draftPayrollRuns + $approvedPayrollRuns;

        return [
            'student-finance.adjustments' => $pendingAdjustments,
            'student-finance.invoices' => $openInvoices,
            'student-finance.fee-structures' => $unapprovedFeeStructures,
            'student-finance.installments' => $installmentItemsDue + $milestonesDue,
            'student-finance.payments' => $pendingMpesa,
            'student-finance' => $studentFinance,
            'ar.overdue' => $openInvoices,
            'ap.pending' => $pendingAp,
            'finance.mpesa' => $pendingMpesa,
            'finance-records' => $financeRecords,
            'payroll-runs' => $draftPayrollRuns,
            'payroll-integration' => $approvedPayrollRuns,
            'employee-finance' => $employeeFinance,
        ];
    }

    private function openInvoicesCount(): int
    {
        return Invoice::query()
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->where('balance', '>', 0)
            ->count();
    }

    private function refundsNeedingActionCount(): int
    {
        if (! Schema::hasTable('refunds')) {
            return 0;
        }

        return Refund::query()
            ->whereIn('status', ['pending', 'approved'])
            ->count();
    }

    private function pendingAdjustmentsCount(): int
    {
        if (! Schema::hasTable('financial_adjustments')) {
            return 0;
        }

        return FinancialAdjustment::query()->where('status', 'pending')->count();
    }

    private function unapprovedFeeStructuresCount(): int
    {
        if (! Schema::hasTable('fee_structures')) {
            return 0;
        }

        return FeeStructure::query()
            ->where('is_active', 1)
            ->where('is_approved', 0)
            ->count();
    }

    private function installmentItemsNeedingActionCount(): int
    {
        if (! Schema::hasTable('installment_plan_items')) {
            return 0;
        }

        return InstallmentPlanItem::query()
            ->whereIn('status', ['pending', 'overdue', 'partial'])
            ->count();
    }

    private function paymentMilestonesNeedingActionCount(): int
    {
        if (! Schema::hasTable('payment_milestones')) {
            return 0;
        }

        return PaymentMilestone::query()
            ->whereIn('status', ['pending', 'overdue', 'partial'])
            ->count();
    }

    private function pendingMpesaRequestsCount(): int
    {
        if (! Schema::hasTable('mpesa_stk_requests')) {
            return 0;
        }

        return MpesaStkRequest::query()
            ->where('status', MpesaStkRequest::STATUS_PENDING)
            ->count();
    }

    private function accountsPayableNeedingActionCount(): int
    {
        if (! Schema::hasTable('accounts_payable')) {
            return 0;
        }

        $apPending = (int) DB::table('accounts_payable')
            ->where(function ($query) {
                $query->whereIn('three_way_match_status', ['pending', 'escalated'])
                    ->orWhere('finance_approval_status', 'pending');
            })
            ->count();

        $procurementPending = 0;
        if (Schema::hasTable('procurement_requisitions')) {
            $procurementPending = (int) DB::table('procurement_requisitions')
                ->where('finance_approval_status', 'pending')
                ->whereNotIn('status', ['draft', 'completed', 'rejected', 'cancelled'])
                ->count();
        }

        return $apPending + $procurementPending;
    }
}
