<?php

namespace App\Services;

use App\Models\LeaveCarryForwardRequest;
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

    /**
     * Run year-end carry-forward rollover for all staff.
     * Transfers approved carry-forward days from the previous year into the new year's balance.
     * Should be called once at the start of a new year (e.g. via scheduled command on Jan 1).
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
