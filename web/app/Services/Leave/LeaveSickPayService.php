<?php

namespace App\Services\Leave;

use App\Models\LeaveRequest;
use App\Models\LeaveSickPayAdjustment;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Auto half-pay deductions for sick leave days 8–14.
 */
class LeaveSickPayService
{
    public function recordOnApproval(LeaveRequest $leaveRequest, Staff $staff): void
    {
        $halfDays = (int) ($leaveRequest->sick_half_pay_days ?? 0);
        if ($halfDays < 1) {
            return;
        }

        $gross = (float) ($staff->gross_monthly_salary ?? 0);
        $dailyRate = $gross > 0 ? round($gross / 30, 2) : null;
        // Half pay means employee receives 50%; deduction = 50% of daily rate × days
        $deduction = $dailyRate !== null ? round(($dailyRate * 0.5) * $halfDays, 2) : null;

        $start = $leaveRequest->start_date instanceof Carbon
            ? $leaveRequest->start_date->copy()
            : Carbon::parse($leaveRequest->start_date);

        LeaveSickPayAdjustment::query()->updateOrCreate(
            [
                'leave_request_id' => $leaveRequest->id,
                'year' => (int) $start->year,
                'month' => (int) $start->month,
            ],
            [
                'staff_id' => $staff->id,
                'half_pay_days' => $halfDays,
                'daily_rate' => $dailyRate,
                'deduction_amount' => $deduction,
                'status' => 'applied',
                'applied_at' => now(),
            ]
        );
    }

    /**
     * Total half-pay deduction for a staff member in a payroll month.
     */
    public function deductionForStaffMonth(int $staffId, int $year, int $month): float
    {
        return (float) LeaveSickPayAdjustment::query()
            ->where('staff_id', $staffId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'applied')
            ->sum('deduction_amount');
    }
}
