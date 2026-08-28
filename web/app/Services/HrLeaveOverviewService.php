<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HrLeaveOverviewService
{
    /**
     * @return Collection<int, object{
     *     leave_request: LeaveRequest,
     *     staff: Staff,
     *     leave_type_name: string,
     *     reason: string,
     *     period_label: string,
     *     accrued_days: int,
     *     days_taken: int,
     *     balance_days: int
     * }>
     */
    public function currentlyOnLeave(?string $search = null): Collection
    {
        if (! Schema::hasTable('leave_requests')) {
            return collect();
        }

        $today = now()->toDateString();

        return LeaveRequest::query()
            ->with(['staff.department', 'leaveType'])
            ->where('overall_status', 'approved')
            ->where('is_cancelled', false)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->when($search, function ($query, $term) {
                $query->whereHas('staff', function ($staffQuery) use ($term) {
                    $staffQuery->where('first_name', 'like', "%{$term}%")
                        ->orWhere('surname', 'like', "%{$term}%")
                        ->orWhere('employee_number', 'like', "%{$term}%");
                });
            })
            ->orderBy('start_date')
            ->get()
            ->map(function (LeaveRequest $leaveRequest) {
                $balance = $this->balanceForStaffAndType(
                    (int) $leaveRequest->staff_id,
                    (int) $leaveRequest->leave_type_id,
                );

                return (object) [
                    'leave_request' => $leaveRequest,
                    'staff' => $leaveRequest->staff,
                    'leave_type_name' => $leaveRequest->leaveType?->leave_name ?? '-',
                    'reason' => $leaveRequest->reason,
                    'period_label' => $leaveRequest->start_date->format('d M Y').' - '.$leaveRequest->end_date->format('d M Y'),
                    'accrued_days' => (int) ($balance->entitled_days ?? 0),
                    'days_taken' => (int) ($balance->days_taken ?? 0),
                    'balance_days' => (int) ($balance->balance_days ?? 0),
                ];
            });
    }

    /**
     * @return Collection<int, object{
     *     staff: Staff,
     *     on_leave: bool,
     *     current_leave_type: ?string,
     *     current_leave_period: ?string,
     *     accrued_days: int,
     *     days_taken: int,
     *     days_remaining: int
     * }>
     */
    public function allEmployeesLeaveSummary(?string $search = null): Collection
    {
        if (! Schema::hasTable('staff')) {
            return collect();
        }

        $year = now()->year;
        $today = now()->toDateString();

        $staffMembers = Staff::query()
            ->with('department')
            ->whereIn('employment_status', ['active', 'on_leave', 'onboarding'])
            ->when($search, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('first_name', 'like', "%{$term}%")
                        ->orWhere('surname', 'like', "%{$term}%")
                        ->orWhere('employee_number', 'like', "%{$term}%")
                        ->orWhere('job_title', 'like', "%{$term}%");
                });
            })
            ->orderBy('first_name')
            ->orderBy('surname')
            ->get();

        if ($staffMembers->isEmpty()) {
            return collect();
        }

        $staffIds = $staffMembers->pluck('id')->all();

        $balances = collect();

        if (Schema::hasTable('leave_balances')) {
            $balances = DB::table('leave_balances')
                ->whereIn('staff_id', $staffIds)
                ->where('year', $year)
                ->selectRaw('staff_id, SUM(entitled_days) as accrued_days, SUM(days_taken) as days_taken, SUM(balance_days) as balance_days')
                ->groupBy('staff_id')
                ->get()
                ->keyBy('staff_id');
        }

        $activeLeave = collect();

        if (Schema::hasTable('leave_requests')) {
            $activeLeave = LeaveRequest::query()
                ->with('leaveType')
                ->whereIn('staff_id', $staffIds)
                ->where('overall_status', 'approved')
                ->where('is_cancelled', false)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->get()
                ->keyBy('staff_id');
        }

        return $staffMembers->map(function (Staff $staff) use ($balances, $activeLeave) {
            $balance = $balances->get($staff->id);
            $leaveRequest = $activeLeave->get($staff->id);

            return (object) [
                'staff' => $staff,
                'on_leave' => $leaveRequest !== null,
                'current_leave_type' => $leaveRequest?->leaveType?->leave_name,
                'current_leave_period' => $leaveRequest
                    ? $leaveRequest->start_date->format('d M Y').' - '.$leaveRequest->end_date->format('d M Y')
                    : null,
                'accrued_days' => (int) ($balance->accrued_days ?? 0),
                'days_taken' => (int) ($balance->days_taken ?? 0),
                'days_remaining' => (int) ($balance->balance_days ?? 0),
            ];
        });
    }

    private function balanceForStaffAndType(int $staffId, int $leaveTypeId): ?object
    {
        if (! Schema::hasTable('leave_balances')) {
            return null;
        }

        return DB::table('leave_balances')
            ->where('staff_id', $staffId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', now()->year)
            ->first();
    }
}
