<?php

namespace App\Services;

use App\Models\LeaveType;
use App\Models\Staff;
use App\Models\LeaveBalance;
use Illuminate\Support\Facades\DB;

class LeaveAccrualService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function accrueMonthly(): void
    {
        $leaveTypes = LeaveType::query()
            ->where('is_active', 1)
            ->where('accrual_type', 'monthly')
            ->get();

        if ($leaveTypes->isEmpty()) {
            return;
        }

        $staff = Staff::query()
            ->whereIn('employment_status', ['active', 'onboarding', 'probation'])
            ->get(['id', 'employment_start_date', 'employment_status']);

        foreach ($leaveTypes as $leaveType) {
            foreach ($staff as $employee) {
                $this->accrueForStaff($employee, $leaveType);
            }
        }
    }

    public function accrueForStaff(Staff $staff, LeaveType $leaveType): void
    {
        if ($leaveType->accrual_type !== 'monthly' || ! $leaveType->accrual_rate) {
            return;
        }

        $currentYear = now()->year;
        $startDate = $staff->employment_start_date;

        if (! $startDate || $startDate->year > $currentYear) {
            return;
        }

        $completedMonths = $this->completedMonthsWorked($startDate, $currentYear);
        if ($completedMonths <= 0) {
            return;
        }

        $monthlyRate = (float) $leaveType->accrual_rate;
        $totalAccrued = round($monthlyRate * $completedMonths, 2);

        $balance = LeaveBalance::query()
            ->where('staff_id', $staff->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $currentYear)
            ->first();

        if (! $balance) {
            LeaveBalance::query()->create([
                'staff_id' => $staff->id,
                'leave_type_id' => $leaveType->id,
                'year' => $currentYear,
                'entitled_days' => $totalAccrued,
                'days_taken' => 0,
                'days_pending' => 0,
                'balance_days' => $totalAccrued,
                'last_updated' => now()->toDateString(),
            ]);

            return;
        }

        if ($balance->entitled_days !== $totalAccrued) {
            $oldEntitlement = $balance->entitled_days;
            $taken = (float) $balance->days_taken;
            $pending = (float) $balance->days_pending;
            $newBalance = max(0, $totalAccrued - $taken - $pending);

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
    }

    public function recalculateForStaff(Staff $staff, ?int $year = null): void
    {
        $year = $year ?? now()->year;
        $startDate = $staff->employment_start_date;

        if (! $startDate || $startDate->year > $year) {
            return;
        }

        $leaveTypes = LeaveType::query()
            ->where('is_active', 1)
            ->where('accrual_type', 'monthly')
            ->get();

        foreach ($leaveTypes as $leaveType) {
            $this->accrueForStaff($staff, $leaveType);
        }
    }

    private function completedMonthsWorked(\Carbon\Carbon $startDate, int $year): int
    {
        $currentDate = now();

        if ($startDate->year < $year) {
            return 12;
        }

        if ($startDate->year > $year) {
            return 0;
        }

        $months = $currentDate->month - $startDate->month + 1;

        return max(0, $months);
    }
}
