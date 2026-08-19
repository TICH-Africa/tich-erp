<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveCarryForwardRequest;
use App\Models\LeaveType;
use App\Models\Staff;
use Illuminate\Validation\ValidationException;

class LeaveCarryForwardService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function submit(Staff $staff, array $data): LeaveCarryForwardRequest
    {
        $fromYear = (int) ($data['from_year'] ?? now()->year);
        $toYear = $fromYear + 1;

        $leaveType = LeaveType::query()->where('leave_code', 'ANNUAL')->where('is_active', true)->firstOrFail();
        $maxCarry = (int) ($leaveType->carry_forward_days ?? 0);

        if ($maxCarry <= 0) {
            throw ValidationException::withMessages([
                'carry_forward' => 'Leave carry-forward is not enabled for this leave type.',
            ]);
        }

        $existing = LeaveCarryForwardRequest::query()
            ->where('staff_id', $staff->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('from_year', $fromYear)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'carry_forward' => "You already have a carry-forward request for {$fromYear}. Current status: {$existing->statusLabel()}.",
            ]);
        }

        $balance = LeaveBalance::query()
            ->where('staff_id', $staff->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $fromYear)
            ->first();

        $unusedDays = $balance ? (float) $balance->balance_days : 0;

        if ($unusedDays <= 0) {
            throw ValidationException::withMessages([
                'carry_forward' => 'You have no unused annual leave days to carry forward.',
            ]);
        }

        $daysRequested = min((float) $data['days_requested'], $unusedDays, $maxCarry);

        if ($daysRequested <= 0) {
            throw ValidationException::withMessages([
                'days_requested' => 'Days requested must be greater than zero.',
            ]);
        }

        $request = LeaveCarryForwardRequest::query()->create([
            'staff_id' => $staff->id,
            'leave_type_id' => $leaveType->id,
            'from_year' => $fromYear,
            'to_year' => $toYear,
            'days_requested' => $daysRequested,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        $this->auditService->log(
            'leave.carry_forward.requested',
            'leave_carry_forward_requests',
            $request->id,
            [],
            $request->toArray(),
            "Employee requested carry-forward of {$daysRequested} days from {$fromYear} to {$toYear}",
            'success'
        );

        return $request;
    }

    public function approve(Staff $reviewer, LeaveCarryForwardRequest $request, array $data): LeaveCarryForwardRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'This request has already been processed.',
            ]);
        }

        $leaveType = $request->leaveType;
        $maxCarry = (int) ($leaveType->carry_forward_days ?? 10);
        $daysApproved = min((float) ($data['days_approved'] ?? $request->days_requested), $maxCarry);

        $request->update([
            'status' => 'approved',
            'days_approved' => $daysApproved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        $this->auditService->log(
            'leave.carry_forward.approved',
            'leave_carry_forward_requests',
            $request->id,
            ['status' => 'pending'],
            ['status' => 'approved', 'days_approved' => $daysApproved],
            "HR approved carry-forward of {$daysApproved} days",
            'success'
        );

        return $request->fresh();
    }

    public function reject(Staff $reviewer, LeaveCarryForwardRequest $request, ?string $reason = null): LeaveCarryForwardRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'This request has already been processed.',
            ]);
        }

        $request->update([
            'status' => 'rejected',
            'days_approved' => 0,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $reason,
        ]);

        $this->auditService->log(
            'leave.carry_forward.rejected',
            'leave_carry_forward_requests',
            $request->id,
            ['status' => 'pending'],
            ['status' => 'rejected'],
            'HR rejected carry-forward request',
            'success'
        );

        return $request->fresh();
    }

    public function pendingForHr(): \Illuminate\Database\Eloquent\Collection
    {
        return LeaveCarryForwardRequest::query()
            ->where('status', 'pending')
            ->with(['staff', 'leaveType'])
            ->orderBy('created_at')
            ->get();
    }

    public function forEmployee(Staff $staff): \Illuminate\Database\Eloquent\Collection
    {
        return LeaveCarryForwardRequest::query()
            ->where('staff_id', $staff->id)
            ->with('leaveType')
            ->orderByDesc('from_year')
            ->get();
    }
}
