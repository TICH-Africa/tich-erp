<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveCarryForwardRequest;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Models\StaffContract;
use App\Services\Leave\LeaveCatalogService;
use Carbon\Carbon;

class LeaveAccrualService
{
    public function __construct(
        protected AuditService $auditService,
        protected LeaveCatalogService $catalog,
    ) {}

    public function accrueMonthly(): void
    {
        $this->catalog->ensureSynced();

        $leaveTypes = LeaveType::query()
            ->where('is_active', 1)
            ->where('accrual_type', 'monthly')
            ->get();

        if ($leaveTypes->isEmpty()) {
            return;
        }

        $staff = Staff::query()
            ->excludePlatformOperators()
            ->whereIn('employment_status', ['active', 'onboarding', 'probation', 'on_leave'])
            ->get(['id', 'employment_start_date', 'employment_status']);

        foreach ($leaveTypes as $leaveType) {
            foreach ($staff as $employee) {
                $this->accrueForStaff($employee, $leaveType);
            }
        }
    }

    /**
     * Ensure year balances exist and annual leave is accrued from original contract start.
     * Safe to call on every leave page load.
     */
    public function ensureBalancesForStaff(Staff $staff, ?int $year = null): void
    {
        $year = $year ?? now()->year;
        $this->catalog->ensureSynced();
        $this->recalculateForStaff($staff, $year);
        $this->ensureNonAccruingBalances($staff, $year);
    }

    public function accrueForStaff(Staff $staff, LeaveType $leaveType, ?int $year = null): void
    {
        $def = $this->catalog->definitionForType($leaveType);
        $isMonthly = ($def['accrual'] ?? $leaveType->accrual_type) === 'monthly'
            || $leaveType->accrual_type === 'monthly';

        if (! $isMonthly) {
            return;
        }

        $year = $year ?? now()->year;
        $startDate = $this->resolveEmploymentStart($staff);

        if (! $startDate || $startDate->year > $year) {
            return;
        }

        $completedMonths = $this->completedMonthsWorked($staff, $startDate, $year);
        if ($completedMonths <= 0) {
            return;
        }

        $monthlyRate = (float) ($def['accrual_rate'] ?? $leaveType->accrual_rate ?? config('tich-leave.annual_monthly_accrual', 1.75));
        if ($monthlyRate <= 0) {
            return;
        }

        $annualCap = (float) ($def['days_allowed_per_year'] ?? $leaveType->days_allowed_per_year ?? config('tich-leave.annual_days_per_year', 21));
        $totalAccrued = min($annualCap, round($monthlyRate * $completedMonths, 2));

        $this->upsertAccruedBalance($staff, $leaveType, $year, $totalAccrued);
    }

    public function recalculateForStaff(Staff $staff, ?int $year = null): void
    {
        $year = $year ?? now()->year;
        $startDate = $this->resolveEmploymentStart($staff);

        if (! $startDate || $startDate->year > $year) {
            return;
        }

        $this->catalog->ensureSynced();

        $leaveTypes = LeaveType::query()
            ->where('is_active', 1)
            ->get();

        foreach ($leaveTypes as $leaveType) {
            $this->accrueForStaff($staff, $leaveType, $year);
        }
    }

    /**
     * Prefer the earliest contract start so renewals within the same year continue accrual.
     */
    public function resolveEmploymentStart(Staff $staff): ?Carbon
    {
        $earliestContractStart = StaffContract::query()
            ->where('staff_id', $staff->id)
            ->whereNotNull('start_date')
            ->orderBy('start_date')
            ->value('start_date');

        if ($earliestContractStart) {
            return Carbon::parse($earliestContractStart)->startOfDay();
        }

        return $staff->employment_start_date
            ? $staff->employment_start_date->copy()->startOfDay()
            : null;
    }

    /**
     * Run year-end carry-forward rollover for all staff.
     */
    public function processYearEndCarryForward(?int $newYear = null): int
    {
        $newYear = $newYear ?? now()->year;
        $previousYear = $newYear - 1;
        $processed = 0;

        $approvedRequests = LeaveCarryForwardRequest::query()
            ->where('from_year', $previousYear)
            ->where('to_year', $newYear)
            ->where('status', 'approved')
            ->whereNotNull('days_approved')
            ->where('days_approved', '>', 0)
            ->get();

        foreach ($approvedRequests as $request) {
            $previousBalance = LeaveBalance::query()
                ->where('staff_id', $request->staff_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $previousYear)
                ->first();

            if (! $previousBalance) {
                continue;
            }

            $unusedDays = (float) $previousBalance->balance_days;
            $daysToCarry = min((float) $request->days_approved, $unusedDays);

            if ($daysToCarry <= 0) {
                continue;
            }

            $newBalance = LeaveBalance::query()
                ->where('staff_id', $request->staff_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $newYear)
                ->first();

            if ($newBalance) {
                $newBalance->update([
                    'carried_forward_days' => $daysToCarry,
                    'balance_days' => (float) $newBalance->balance_days + $daysToCarry,
                    'last_updated' => now()->toDateString(),
                ]);
            } else {
                LeaveBalance::query()->create([
                    'staff_id' => $request->staff_id,
                    'leave_type_id' => $request->leave_type_id,
                    'year' => $newYear,
                    'entitled_days' => 0,
                    'carried_forward_days' => $daysToCarry,
                    'days_taken' => 0,
                    'days_pending' => 0,
                    'balance_days' => $daysToCarry,
                    'last_updated' => now()->toDateString(),
                ]);
            }

            $this->auditService->log(
                'leave.carry_forward.applied',
                'leave_balances',
                $newBalance->id ?? 0,
                ['carried_forward_days' => 0],
                ['carried_forward_days' => $daysToCarry],
                "Carried forward {$daysToCarry} days from {$previousYear} to {$newYear}",
                'success'
            );

            $processed++;
        }

        return $processed;
    }

    /**
     * Months of employment covered by contracts in the leave year (up to today).
     * Example: contract 1 Jun–31 Aug ⇒ 3 months (Jun, Jul, Aug).
     * Renewals in the same year add their months so the count continues.
     */
    private function completedMonthsWorked(Staff $staff, Carbon $employmentStart, int $year): int
    {
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->startOfDay();
        $today = now()->startOfDay();
        if ($today->gt($yearEnd)) {
            $today = $yearEnd->copy();
        }

        $contracts = StaffContract::query()
            ->where('staff_id', $staff->id)
            ->whereNotNull('start_date')
            ->where(function ($q) {
                $q->whereNull('renewal_status')
                    ->orWhere('renewal_status', '!=', 'terminated');
            })
            ->orderBy('start_date')
            ->get(['start_date', 'end_date']);

        $coveredMonths = [];

        if ($contracts->isEmpty()) {
            $from = $employmentStart->copy()->max($yearStart);
            $to = $today->copy();
            if ($to->lt($from) || $from->year > $year) {
                return 0;
            }

            return $this->countInclusiveMonths($from, $to);
        }

        foreach ($contracts as $contract) {
            $cStart = Carbon::parse($contract->start_date)->startOfDay();
            $cEnd = $contract->end_date
                ? Carbon::parse($contract->end_date)->startOfDay()
                : $today->copy();

            if ($cEnd->gt($today)) {
                $cEnd = $today->copy();
            }

            $from = $cStart->copy()->max($yearStart)->max($employmentStart);
            $to = $cEnd->copy()->min($yearEnd);

            if ($to->lt($from)) {
                continue;
            }

            $cursor = $from->copy()->startOfMonth();
            $last = $to->copy()->startOfMonth();
            while ($cursor->lte($last)) {
                $coveredMonths[$cursor->format('Y-m')] = true;
                $cursor->addMonth();
            }
        }

        return min(12, count($coveredMonths));
    }

    private function countInclusiveMonths(Carbon $from, Carbon $to): int
    {
        if ($to->lt($from)) {
            return 0;
        }

        $months = (($to->year - $from->year) * 12) + ($to->month - $from->month) + 1;

        return max(0, min(12, $months));
    }

    private function upsertAccruedBalance(Staff $staff, LeaveType $leaveType, int $year, float $totalAccrued): void
    {
        $balance = LeaveBalance::query()
            ->where('staff_id', $staff->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if (! $balance) {
            LeaveBalance::query()->create([
                'staff_id' => $staff->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
                'entitled_days' => $totalAccrued,
                'days_taken' => 0,
                'days_pending' => 0,
                'balance_days' => $totalAccrued,
                'last_updated' => now()->toDateString(),
            ]);

            return;
        }

        $taken = (float) $balance->days_taken;
        $pending = (float) $balance->days_pending;
        $carried = (float) ($balance->carried_forward_days ?? 0);
        $newBalance = max(0, $totalAccrued + $carried - $taken - $pending);

        if ((float) $balance->entitled_days === $totalAccrued && (float) $balance->balance_days === $newBalance) {
            return;
        }

        $oldEntitlement = $balance->entitled_days;

        $balance->update([
            'entitled_days' => $totalAccrued,
            'balance_days' => $newBalance,
            'last_updated' => now()->toDateString(),
        ]);

        $this->auditService->log(
            'leave.accrual.updated',
            'leave_balances',
            $balance->id,
            ['entitled_days' => $oldEntitlement],
            ['entitled_days' => $totalAccrued],
            'Monthly leave accrual updated',
            'success'
        );
    }

    /**
     * Fixed-entitlement leave types (sick, maternity, etc.) get a year balance row for display.
     */
    private function ensureNonAccruingBalances(Staff $staff, int $year): void
    {
        $types = LeaveType::query()
            ->where('is_active', 1)
            ->get();

        foreach ($types as $leaveType) {
            $def = $this->catalog->definitionForType($leaveType);
            $isMonthly = ($def['accrual'] ?? $leaveType->accrual_type) === 'monthly'
                || $leaveType->accrual_type === 'monthly';

            if ($isMonthly) {
                continue;
            }

            $entitled = (float) ($def['days_allowed_per_year'] ?? $leaveType->days_allowed_per_year ?? 0);
            if ($entitled <= 0) {
                continue;
            }

            $balance = LeaveBalance::query()
                ->where('staff_id', $staff->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $year)
                ->first();

            if (! $balance) {
                LeaveBalance::query()->create([
                    'staff_id' => $staff->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                    'entitled_days' => $entitled,
                    'days_taken' => 0,
                    'days_pending' => 0,
                    'balance_days' => $entitled,
                    'last_updated' => now()->toDateString(),
                ]);

                continue;
            }

            // Keep taken/pending; only seed entitlement if still zero/unset.
            if ((float) $balance->entitled_days <= 0) {
                $taken = (float) $balance->days_taken;
                $pending = (float) $balance->days_pending;
                $carried = (float) ($balance->carried_forward_days ?? 0);
                $balance->update([
                    'entitled_days' => $entitled,
                    'balance_days' => max(0, $entitled + $carried - $taken - $pending),
                    'last_updated' => now()->toDateString(),
                ]);
            }
        }
    }
}
