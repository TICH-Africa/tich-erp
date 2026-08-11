<?php

namespace App\Services\Finance;

use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Staff;
use App\Models\StatutoryDeduction;
use App\Services\KenyaPayrollTaxService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollRunService
{
    public function __construct(
        protected KenyaPayrollTaxService $taxService,
        protected LedgerService $ledger,
    ) {}

    public function createRun(int $year, int $month, ?int $createdByStaffId = null, ?string $notes = null): PayrollRun
    {
        abort_if($month < 1 || $month > 12, 422, 'Invalid pay period month.');
        abort_if(
            PayrollRun::query()->where('pay_period_year', $year)->where('pay_period_month', $month)->where('status', '!=', PayrollRun::STATUS_CANCELLED)->exists(),
            422,
            'A payroll run already exists for this period.',
        );

        return DB::transaction(function () use ($year, $month, $createdByStaffId, $notes) {
            $run = PayrollRun::query()->create([
                'run_number' => $this->nextRunNumber($year, $month),
                'pay_period_year' => $year,
                'pay_period_month' => $month,
                'status' => PayrollRun::STATUS_DRAFT,
                'notes' => $notes,
                'created_by' => $createdByStaffId,
            ]);

            $this->populateRun($run);

            return $run->fresh(['items.staff.department', 'items.statutoryDeductions', 'creator']);
        });
    }

    public function populateRun(PayrollRun $run): PayrollRun
    {
        abort_unless($run->isEditable(), 422, 'Only draft payroll runs can be recalculated.');

        return DB::transaction(function () use ($run) {
            PayrollItem::query()->where('payroll_run_id', $run->id)->delete();

            $staffMembers = Staff::query()
                ->with('activeAllowances')
                ->whereIn('employment_status', ['active', 'on_leave'])
                ->orderBy('surname')
                ->orderBy('first_name')
                ->get();

            $totals = [
                'staff_count' => 0,
                'total_gross' => 0.0,
                'total_deductions' => 0.0,
                'total_net' => 0.0,
                'total_paye' => 0.0,
                'total_nssf' => 0.0,
                'total_sha' => 0.0,
                'total_ahl' => 0.0,
                'total_employer_cost' => 0.0,
            ];

            foreach ($staffMembers as $staff) {
                $item = $this->buildItemForStaff($run, $staff);

                if (! $item) {
                    continue;
                }

                $totals['staff_count']++;
                $totals['total_gross'] += (float) $item->gross_salary;
                $totals['total_deductions'] += (float) $item->total_deductions;
                $totals['total_net'] += (float) $item->net_salary;
                $totals['total_paye'] += $this->statutoryTotal($item, 'paye') + $this->statutoryTotal($item, 'withholding_tax');
                $totals['total_nssf'] += $this->statutoryTotal($item, 'nssf', includeEmployer: true);
                $totals['total_sha'] += $this->statutoryTotal($item, 'sha');
                $totals['total_ahl'] += $this->statutoryTotal($item, 'ahl', includeEmployer: true);
                $totals['total_employer_cost'] += (float) ($item->calculation_snapshot['total_employer_cost'] ?? $item->gross_salary);
            }

            $run->update(array_map(fn (float|int $value) => is_float($value) ? round($value, 2) : $value, $totals));

            return $run->fresh(['items.staff.department', 'items.statutoryDeductions']);
        });
    }

    public function approve(PayrollRun $run, int $approvedByStaffId): PayrollRun
    {
        abort_unless($run->canApprove(), 422, 'This payroll run cannot be approved.');

        return DB::transaction(function () use ($run, $approvedByStaffId) {
            $now = now();

            PayrollItem::query()
                ->where('payroll_run_id', $run->id)
                ->update([
                    'is_processed' => 1,
                    'processed_by' => $approvedByStaffId,
                    'processed_at' => $now,
                    'is_approved' => 1,
                    'approved_by' => $approvedByStaffId,
                    'approved_at' => $now,
                ]);

            $run->update([
                'status' => PayrollRun::STATUS_APPROVED,
                'approved_by' => $approvedByStaffId,
                'approved_at' => $now,
            ]);

            return $run->fresh(['items.staff', 'approver']);
        });
    }

    public function cancel(PayrollRun $run): PayrollRun
    {
        abort_unless($run->status === PayrollRun::STATUS_DRAFT, 422, 'Only draft runs can be cancelled.');

        $run->update(['status' => PayrollRun::STATUS_CANCELLED]);

        return $run;
    }

    public function postToGeneralLedger(PayrollRun $run, int $postedByStaffId): PayrollRun
    {
        abort_unless($run->canPostToGl(), 422, 'Only approved payroll runs can be posted to the general ledger.');

        return DB::transaction(function () use ($run, $postedByStaffId) {
            $this->ledger->postPayrollRun($run, $postedByStaffId);

            $run->update([
                'status' => PayrollRun::STATUS_POSTED,
                'posted_by' => $postedByStaffId,
                'posted_at' => now(),
                'gl_reference' => $run->run_number,
            ]);

            return $run->fresh(['poster']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function statutoryReport(PayrollRun $run, string $agency): array
    {
        $run->load(['items.staff.department', 'items.statutoryDeductions']);

        $types = match ($agency) {
            'kra' => ['paye', 'withholding_tax'],
            'nssf' => ['nssf'],
            'sha' => ['sha'],
            default => abort(422, 'Unknown statutory agency.'),
        };

        $rows = [];

        foreach ($run->items as $item) {
            foreach ($item->statutoryDeductions as $deduction) {
                if (! in_array($deduction->deduction_type, $types, true)) {
                    continue;
                }

                $rows[] = [
                    'employee_number' => $item->staff?->employee_number,
                    'employee_name' => $item->staff?->fullName(),
                    'kra_pin' => $item->staff?->kra_pin,
                    'nssf_number' => $item->staff?->nssf_number,
                    'sha_number' => $item->staff?->sha_number,
                    'deduction_type' => $deduction->deduction_type,
                    'gross_salary' => (float) $deduction->gross_salary_for_deduction,
                    'rate' => $deduction->deduction_rate,
                    'employee_amount' => (float) $deduction->employee_amount,
                    'employer_amount' => (float) $deduction->employer_amount,
                    'total_amount' => round((float) $deduction->employee_amount + (float) $deduction->employer_amount, 2),
                ];
            }
        }

        return [
            'agency' => strtoupper($agency),
            'period' => $run->periodLabel(),
            'run_number' => $run->run_number,
            'rows' => $rows,
            'totals' => [
                'employee' => round(collect($rows)->sum('employee_amount'), 2),
                'employer' => round(collect($rows)->sum('employer_amount'), 2),
                'combined' => round(collect($rows)->sum('total_amount'), 2),
            ],
        ];
    }

    private function buildItemForStaff(PayrollRun $run, Staff $staff): ?PayrollItem
    {
        $basic = (float) $staff->gross_monthly_salary;
        $allowances = (float) $staff->activeAllowances->sum('amount');

        if ($basic <= 0 && $allowances <= 0) {
            return null;
        }

        $breakdown = $this->taxService->calculateForStaff($staff, $basic, [
            'allowances' => $allowances,
        ]);

        $item = PayrollItem::query()->create([
            'payroll_run_id' => $run->id,
            'payslip_number' => $this->nextPayslipNumber($run, $staff),
            'staff_id' => $staff->id,
            'pay_period_year' => $run->pay_period_year,
            'pay_period_month' => $run->pay_period_month,
            'basic_salary' => $breakdown['basic_salary'],
            'gross_salary' => $breakdown['gross_salary'],
            'total_allowances' => $breakdown['allowances'],
            'total_deductions' => $breakdown['total_deductions'],
            'net_salary' => $breakdown['net_salary'],
            'calculation_snapshot' => $breakdown,
        ]);

        $this->storeStatutoryDeductions($item, $breakdown);

        return $item->load('statutoryDeductions');
    }

    /**
     * @param  array<string, mixed>  $breakdown
     */
    private function storeStatutoryDeductions(PayrollItem $item, array $breakdown): void
    {
        $employerMap = collect($breakdown['employer_contributions'] ?? [])
            ->keyBy('code')
            ->map(fn (array $row) => (float) $row['amount']);

        $skipCodes = ['personal_relief', 'other'];

        foreach ($breakdown['deductions'] as $row) {
            $code = (string) ($row['code'] ?? '');

            if ($code === '' || in_array($code, $skipCodes, true)) {
                continue;
            }

            StatutoryDeduction::query()->create([
                'payroll_item_id' => $item->id,
                'staff_id' => $item->staff_id,
                'deduction_type' => $code,
                'gross_salary_for_deduction' => (float) ($row['base'] ?? $breakdown['gross_salary']),
                'deduction_rate' => isset($row['rate']) ? (float) $row['rate'] : null,
                'employee_amount' => (float) $row['amount'],
                'employer_amount' => (float) ($employerMap[$code] ?? 0),
            ]);
        }

        foreach ($employerMap as $code => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $exists = StatutoryDeduction::query()
                ->where('payroll_item_id', $item->id)
                ->where('deduction_type', $code)
                ->exists();

            if ($exists) {
                continue;
            }

            StatutoryDeduction::query()->create([
                'payroll_item_id' => $item->id,
                'staff_id' => $item->staff_id,
                'deduction_type' => $code,
                'gross_salary_for_deduction' => (float) $breakdown['gross_salary'],
                'employee_amount' => 0,
                'employer_amount' => $amount,
            ]);
        }
    }

    private function statutoryTotal(PayrollItem $item, string $type, bool $includeEmployer = false): float
    {
        return (float) $item->statutoryDeductions
            ->where('deduction_type', $type)
            ->sum(fn (StatutoryDeduction $row) => (float) $row->employee_amount + ($includeEmployer ? (float) $row->employer_amount : 0));
    }

    private function nextRunNumber(int $year, int $month): string
    {
        $sequence = PayrollRun::query()->count() + 1;

        return sprintf('PR-%04d%02d-%04d', $year, $month, $sequence);
    }

    private function nextPayslipNumber(PayrollRun $run, Staff $staff): string
    {
        return sprintf('PS-%04d%02d-%s', $run->pay_period_year, $run->pay_period_month, $staff->employee_number);
    }

    /**
     * @return Collection<int, PayrollRun>
     */
    public function recentRuns(int $limit = 12): Collection
    {
        return PayrollRun::query()
            ->with(['creator', 'approver', 'poster'])
            ->orderByDesc('pay_period_year')
            ->orderByDesc('pay_period_month')
            ->limit($limit)
            ->get();
    }
}
