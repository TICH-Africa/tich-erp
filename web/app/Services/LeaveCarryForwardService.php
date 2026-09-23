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
        protected PlatformNotificationService $notifications,
    ) {}

    public function submit(Staff $staff, array $data): LeaveCarryForwardRequest
    {
        $fromYear = (int) ($data['from_year'] ?? now()->year);
        $toYear = $fromYear + 1;

        $leaveType = LeaveType::query()->where('leave_code', 'ANNUAL')->where('is_active', true)->firstOrFail();
        $maxCarry = (int) ($leaveType->carry_forward_days ?? config('tich-leave.annual_carry_forward_max', 10));

        if ($maxCarry <= 0) {
            throw ValidationException::withMessages([
                'carry_forward' => 'Leave carry-forward is not enabled for this leave type.',
            ]);
        }

        $existing = LeaveCarryForwardRequest::query()
            ->where('staff_id', $staff->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('from_year', $fromYear)
            ->whereNotIn('status', ['rejected'])
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
            'line_manager_status' => 'pending',
            'line_manager_staff_id' => $staff->line_manager_id,
            'hr_status' => 'pending',
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

        $this->notifyLineManager($request, $staff);

        return $request;
    }

    public function approveByLineManager(Staff $manager, LeaveCarryForwardRequest $request, array $data = []): LeaveCarryForwardRequest
    {
        $this->assertLineManagerCanAct($manager, $request);

        if ($request->line_manager_status !== 'pending' || $request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'This request has already been processed by the line manager.',
            ]);
        }

        $request->update([
            'line_manager_status' => 'approved',
            'line_manager_staff_id' => $manager->id,
            'line_manager_acted_at' => now(),
            'line_manager_notes' => $data['line_manager_notes'] ?? null,
        ]);

        $this->auditService->log(
            'leave.carry_forward.lm_approved',
            'leave_carry_forward_requests',
            $request->id,
            ['line_manager_status' => 'pending'],
            ['line_manager_status' => 'approved'],
            'Line manager approved carry-forward request',
            'success'
        );

        $this->notifyHrOfLmApproval($request->fresh(['staff', 'leaveType']));

        return $request->fresh();
    }

    public function rejectByLineManager(Staff $manager, LeaveCarryForwardRequest $request, ?string $reason = null): LeaveCarryForwardRequest
    {
        $this->assertLineManagerCanAct($manager, $request);

        if ($request->line_manager_status !== 'pending' || $request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'This request has already been processed by the line manager.',
            ]);
        }

        $request->update([
            'line_manager_status' => 'rejected',
            'line_manager_staff_id' => $manager->id,
            'line_manager_acted_at' => now(),
            'line_manager_notes' => $reason,
            'hr_status' => 'rejected',
            'status' => 'rejected',
            'days_approved' => 0,
            'review_notes' => $reason,
        ]);

        $this->auditService->log(
            'leave.carry_forward.lm_rejected',
            'leave_carry_forward_requests',
            $request->id,
            ['line_manager_status' => 'pending'],
            ['line_manager_status' => 'rejected', 'status' => 'rejected'],
            'Line manager rejected carry-forward request',
            'success'
        );

        return $request->fresh();
    }

    public function approve(Staff $reviewer, LeaveCarryForwardRequest $request, array $data): LeaveCarryForwardRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'This request has already been processed.',
            ]);
        }

        if (($request->line_manager_status ?? 'pending') !== 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Line manager approval is required before HR can approve.',
            ]);
        }

        if (($request->hr_status ?? 'pending') !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'HR has already acted on this request.',
            ]);
        }

        $leaveType = $request->leaveType;
        $maxCarry = (int) ($leaveType->carry_forward_days ?? 10);
        $daysApproved = min((float) ($data['days_approved'] ?? $request->days_requested), $maxCarry);

        $request->update([
            'status' => 'approved',
            'hr_status' => 'approved',
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

        if (($request->line_manager_status ?? 'pending') !== 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Line manager approval is required before HR can reject.',
            ]);
        }

        $request->update([
            'status' => 'rejected',
            'hr_status' => 'rejected',
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
            ->where('line_manager_status', 'approved')
            ->where(function ($q) {
                $q->whereNull('hr_status')->orWhere('hr_status', 'pending');
            })
            ->with(['staff', 'leaveType'])
            ->orderBy('created_at')
            ->get();
    }

    public function pendingForLineManager(Staff $manager): \Illuminate\Database\Eloquent\Collection
    {
        return LeaveCarryForwardRequest::query()
            ->where('status', 'pending')
            ->where('line_manager_status', 'pending')
            ->where(function ($q) use ($manager) {
                $q->where('line_manager_staff_id', $manager->id)
                    ->orWhereHas('staff', fn ($s) => $s->where('line_manager_id', $manager->id));
            })
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

    private function assertLineManagerCanAct(Staff $manager, LeaveCarryForwardRequest $request): void
    {
        $employee = $request->staff;
        $isAssigned = (int) ($request->line_manager_staff_id ?? 0) === (int) $manager->id;
        $isCurrentManager = $employee && (int) ($employee->line_manager_id ?? 0) === (int) $manager->id;

        if (! $isAssigned && ! $isCurrentManager) {
            throw ValidationException::withMessages([
                'status' => 'You are not the line manager for this carry-forward request.',
            ]);
        }
    }

    private function notifyLineManager(LeaveCarryForwardRequest $request, Staff $staff): void
    {
        $manager = $staff->lineManager;
        if (! $manager?->user_id) {
            return;
        }

        $this->notifications->notifyUser(
            (int) $manager->user_id,
            'Leave carry-forward awaiting your approval',
            $staff->fullName().' requested to carry forward '
                .number_format((float) $request->days_requested, 1)
                .' annual leave day(s) from '.$request->from_year.' to '.$request->to_year.'.',
            'leave_carry_forward',
            (string) $request->id,
            'normal',
            route('employee.leave.carry-forward'),
        );
    }

    private function notifyHrOfLmApproval(LeaveCarryForwardRequest $request): void
    {
        $roleNames = ['HR Manager', 'Assistant HR Manager', 'Super Admin', 'CEO'];
        $userIds = \Illuminate\Support\Facades\DB::table('user_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->whereIn('r.role_name', $roleNames)
            ->where(function ($query) {
                $query->whereNull('ur.expires_at')
                    ->orWhere('ur.expires_at', '>', now());
            })
            ->distinct()
            ->pluck('ur.user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($userIds === []) {
            return;
        }

        $this->notifications->notifyUsers(
            $userIds,
            'Carry-forward ready for HR',
            ($request->staff?->fullName() ?? 'An employee').' carry-forward request was approved by the line manager and awaits HR decision.',
            'leave_carry_forward',
            (string) $request->id,
            'normal',
            route('hr.leave.carry-forward.index'),
        );
    }
}
