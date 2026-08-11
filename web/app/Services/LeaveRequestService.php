<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeaveRequestService
{
    public function __construct(
        protected PlatformNotificationService $notifications,
        protected AuditService $auditService,
        protected StoredFileService $files,
    ) {}

    /**
     * @return Collection<int, LeaveType>
     */
    public function activeLeaveTypes(): Collection
    {
        return LeaveType::query()
            ->where('is_active', 1)
            ->orderBy('leave_name')
            ->get();
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function requestsForStaff(Staff $staff): Collection
    {
        return LeaveRequest::query()
            ->with('leaveType')
            ->where('staff_id', $staff->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, LeaveRequest>
     */
    public function hrInbox(?string $status = null, ?string $search = null, int $perPage = 20)
    {
        return LeaveRequest::query()
            ->with(['staff.department', 'leaveType'])
            ->when($status, fn ($query, $value) => $query->where('overall_status', $value))
            ->when($search, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('leave_number', 'like', "%{$term}%")
                        ->orWhereHas('staff', function ($staffQuery) use ($term) {
                            $staffQuery->where('first_name', 'like', "%{$term}%")
                                ->orWhere('surname', 'like', "%{$term}%")
                                ->orWhere('employee_number', 'like', "%{$term}%");
                        });
                });
            })
            ->orderByRaw("FIELD(overall_status, 'pending_hr', 'returned', 'approved', 'rejected', 'cancelled')")
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function pendingHrCount(): int
    {
        return LeaveRequest::query()
            ->where('overall_status', 'pending_hr')
            ->count();
    }

    public function submit(Staff $staff, array $data, ?UploadedFile $certificate = null): LeaveRequest
    {
        $leaveType = LeaveType::query()->findOrFail($data['leave_type_id']);
        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();

        if ($endDate->lt($startDate)) {
            throw new \InvalidArgumentException('End date must be on or after the start date.');
        }

        $days = $this->calculateDays($startDate, $endDate, $leaveType);
        $this->validateMaxDays($leaveType, $days);
        $certificatePath = $this->storeCertificate($certificate);

        return DB::transaction(function () use ($staff, $data, $leaveType, $startDate, $endDate, $days, $certificatePath) {
            $leaveRequest = LeaveRequest::create([
                'leave_number' => $this->generateLeaveNumber(),
                'staff_id' => $staff->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_requested' => $days,
                'reason' => $data['reason'],
                'is_emergency' => ! empty($data['is_emergency']),
                'medical_certificate_path' => $certificatePath,
                'hod_approval_status' => 'not_required',
                'hr_approval_status' => 'pending',
                'overall_status' => 'pending_hr',
                'handover_notes' => $data['handover_notes'] ?? null,
            ]);

            $this->adjustBalance($staff->id, (int) $leaveType->id, $days, 'add_pending');

            $this->auditService->log(
                'hr.leave.submitted',
                'leave_request',
                $leaveRequest->id,
                null,
                $this->auditSnapshot($leaveRequest),
            );

            $this->notifyHrOfSubmission($leaveRequest, $staff);

            return $leaveRequest->fresh(['leaveType']);
        });
    }

    public function updateByEmployee(LeaveRequest $leaveRequest, Staff $staff, array $data, ?UploadedFile $certificate = null): LeaveRequest
    {
        abort_unless($leaveRequest->staff_id === $staff->id, 403);
        abort_unless($leaveRequest->isEditableByEmployee(), 422, 'This leave request cannot be edited.');

        $previousDays = (int) $leaveRequest->days_requested;
        $previousTypeId = (int) $leaveRequest->leave_type_id;

        $leaveType = LeaveType::query()->findOrFail($data['leave_type_id']);
        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();

        if ($endDate->lt($startDate)) {
            throw new \InvalidArgumentException('End date must be on or after the start date.');
        }

        $days = $this->calculateDays($startDate, $endDate, $leaveType);
        $this->validateMaxDays($leaveType, $days);
        $certificatePath = $this->storeCertificate($certificate) ?? $leaveRequest->medical_certificate_path;

        return DB::transaction(function () use ($leaveRequest, $staff, $data, $leaveType, $startDate, $endDate, $days, $certificatePath, $previousDays, $previousTypeId) {
            $this->adjustBalance($staff->id, $previousTypeId, $previousDays, 'remove_pending');

            $oldSnapshot = $this->auditSnapshot($leaveRequest);

            $leaveRequest->update([
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_requested' => $days,
                'reason' => $data['reason'],
                'is_emergency' => ! empty($data['is_emergency']),
                'medical_certificate_path' => $certificatePath,
                'hr_approval_status' => 'pending',
                'overall_status' => 'pending_hr',
                'hr_review_notes' => null,
                'handover_notes' => $data['handover_notes'] ?? null,
            ]);

            $this->adjustBalance($staff->id, (int) $leaveType->id, $days, 'add_pending');

            $this->auditService->log(
                'hr.leave.resubmitted',
                'leave_request',
                $leaveRequest->id,
                $oldSnapshot,
                $this->auditSnapshot($leaveRequest->fresh()),
            );

            $this->notifyHrOfSubmission($leaveRequest->fresh(['leaveType']), $staff, resubmitted: true);

            return $leaveRequest->fresh(['leaveType']);
        });
    }

    public function cancelByEmployee(LeaveRequest $leaveRequest, Staff $staff, ?string $reason = null): LeaveRequest
    {
        abort_unless($leaveRequest->staff_id === $staff->id, 403);
        abort_unless($leaveRequest->isCancellableByEmployee(), 422, 'This leave request cannot be cancelled.');

        return DB::transaction(function () use ($leaveRequest, $staff, $reason) {
            $oldSnapshot = $this->auditSnapshot($leaveRequest);

            $this->adjustBalance(
                $staff->id,
                (int) $leaveRequest->leave_type_id,
                (int) $leaveRequest->days_requested,
                'remove_pending'
            );

            $leaveRequest->update([
                'overall_status' => 'cancelled',
                'is_cancelled' => true,
                'cancellation_reason' => $reason,
                'hr_approval_status' => 'cancelled',
            ]);

            $this->auditService->log(
                'hr.leave.cancelled',
                'leave_request',
                $leaveRequest->id,
                $oldSnapshot,
                $this->auditSnapshot($leaveRequest->fresh()),
                $reason,
            );

            return $leaveRequest->fresh(['leaveType']);
        });
    }

    public function approve(LeaveRequest $leaveRequest, Staff $hrStaff, array $data = []): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $hrStaff, $data) {
            $oldSnapshot = $this->auditSnapshot($leaveRequest);
            $previousDays = (int) $leaveRequest->days_requested;
            $previousTypeId = (int) $leaveRequest->leave_type_id;
            $leaveType = $leaveRequest->leaveType()->first() ?? LeaveType::find($previousTypeId);

            $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date'])->startOfDay() : $leaveRequest->start_date;
            $endDate = isset($data['end_date']) ? Carbon::parse($data['end_date'])->startOfDay() : $leaveRequest->end_date;
            $days = isset($data['start_date']) || isset($data['end_date'])
                ? $this->calculateDays($startDate, $endDate, $leaveType)
                : (int) $leaveRequest->days_requested;

            $this->adjustBalance($leaveRequest->staff_id, $previousTypeId, $previousDays, 'remove_pending');

            $leaveRequest->update([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_requested' => $days,
                'reason' => $data['reason'] ?? $leaveRequest->reason,
                'handover_notes' => $data['handover_notes'] ?? $leaveRequest->handover_notes,
                'hr_approval_status' => 'approved',
                'hr_approved_by' => $hrStaff->id,
                'hr_approved_at' => now(),
                'overall_status' => 'approved',
                'hr_review_notes' => null,
            ]);

            $this->adjustBalance(
                $leaveRequest->staff_id,
                (int) $leaveRequest->leave_type_id,
                $days,
                'add_taken'
            );

            $this->auditService->log(
                'hr.leave.approved',
                'leave_request',
                $leaveRequest->id,
                $oldSnapshot,
                $this->auditSnapshot($leaveRequest->fresh()),
            );

            $this->notifyEmployeeDecision($leaveRequest->fresh(['leaveType', 'staff']), 'approved');

            return $leaveRequest->fresh(['leaveType', 'staff']);
        });
    }

    public function reject(LeaveRequest $leaveRequest, Staff $hrStaff, string $reason): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $hrStaff, $reason) {
            $oldSnapshot = $this->auditSnapshot($leaveRequest);

            $this->adjustBalance(
                $leaveRequest->staff_id,
                (int) $leaveRequest->leave_type_id,
                (int) $leaveRequest->days_requested,
                'remove_pending'
            );

            $leaveRequest->update([
                'hr_approval_status' => 'rejected',
                'hr_approved_by' => $hrStaff->id,
                'hr_approved_at' => now(),
                'overall_status' => 'rejected',
                'cancellation_reason' => $reason,
            ]);

            $this->auditService->log(
                'hr.leave.rejected',
                'leave_request',
                $leaveRequest->id,
                $oldSnapshot,
                $this->auditSnapshot($leaveRequest->fresh()),
                $reason,
            );

            $this->notifyEmployeeDecision($leaveRequest->fresh(['leaveType', 'staff']), 'rejected', $reason);

            return $leaveRequest->fresh(['leaveType', 'staff']);
        });
    }

    public function returnForChanges(LeaveRequest $leaveRequest, Staff $hrStaff, string $notes): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $hrStaff, $notes) {
            $oldSnapshot = $this->auditSnapshot($leaveRequest);

            $this->adjustBalance(
                $leaveRequest->staff_id,
                (int) $leaveRequest->leave_type_id,
                (int) $leaveRequest->days_requested,
                'remove_pending'
            );

            $leaveRequest->update([
                'hr_approval_status' => 'returned',
                'hr_approved_by' => $hrStaff->id,
                'hr_approved_at' => now(),
                'overall_status' => 'returned',
                'hr_review_notes' => $notes,
            ]);

            $this->auditService->log(
                'hr.leave.returned',
                'leave_request',
                $leaveRequest->id,
                $oldSnapshot,
                $this->auditSnapshot($leaveRequest->fresh()),
                $notes,
            );

            $this->notifyEmployeeDecision($leaveRequest->fresh(['leaveType', 'staff']), 'returned', $notes);

            return $leaveRequest->fresh(['leaveType', 'staff']);
        });
    }

    public function calculateDays(Carbon $startDate, Carbon $endDate, ?LeaveType $leaveType = null): int
    {
        if ($leaveType && $leaveType->calculation_type === 'working_days') {
            return $this->calculateWorkingDays($startDate, $endDate);
        }

        return $startDate->diffInDays($endDate) + 1;
    }

    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $days = 0;
        $holidays = DB::table('public_holidays')
            ->where('is_active', 1)
            ->whereBetween('holiday_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('holiday_date')
            ->all();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if (! in_array($date->format('Y-m-d'), $holidays, true) && ! $date->isWeekend()) {
                $days++;
            }
        }

        return $days;
    }

    private function generateLeaveNumber(): string
    {
        do {
            $number = 'LV-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (LeaveRequest::query()->where('leave_number', $number)->exists());

        return $number;
    }

    private function storeCertificate(?UploadedFile $certificate): ?string
    {
        if (! $certificate) {
            return null;
        }

        return $this->files->store($certificate, 'leave-certificates', 'local');
    }

    private function adjustBalance(int $staffId, int $leaveTypeId, int $days, string $action): void
    {
        if (! Schema::hasTable('leave_balances') || $days <= 0) {
            return;
        }

        $year = now()->year;
        $balance = DB::table('leave_balances')
            ->where('staff_id', $staffId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if (! $balance) {
            $entitled = (int) (LeaveType::query()->find($leaveTypeId)?->days_allowed_per_year ?? 0);

            DB::table('leave_balances')->insert([
                'staff_id' => $staffId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
                'entitled_days' => $entitled,
                'days_taken' => 0,
                'days_pending' => 0,
                'balance_days' => $entitled,
                'last_updated' => now()->toDateString(),
                'created_at' => now(),
            ]);

            $balance = DB::table('leave_balances')
                ->where('staff_id', $staffId)
                ->where('leave_type_id', $leaveTypeId)
                ->where('year', $year)
                ->first();
        }

        $pending = (int) $balance->days_pending;
        $taken = (int) $balance->days_taken;
        $entitled = (int) $balance->entitled_days;

        match ($action) {
            'add_pending' => [$pending += $days],
            'remove_pending' => [$pending = max(0, $pending - $days)],
            'add_taken' => [$taken += $days],
            'confirm_taken' => [
                $pending = max(0, $pending - $days),
                $taken += $days,
            ],
            default => null,
        };

        DB::table('leave_balances')
            ->where('id', $balance->id)
            ->update([
                'days_pending' => $pending,
                'days_taken' => $taken,
                'balance_days' => max(0, $entitled - $taken - $pending),
                'last_updated' => now()->toDateString(),
            ]);
    }

    private function notifyHrOfSubmission(LeaveRequest $leaveRequest, Staff $staff, bool $resubmitted = false): void
    {
        $userIds = $this->hrNotifierUserIds();
        if ($userIds === []) {
            return;
        }

        $title = $resubmitted ? 'Leave request resubmitted' : 'New leave request';
        $body = sprintf(
            '%s (%s) %s leave request %s for %s–%s (%s days).',
            $staff->fullName(),
            $staff->employee_number,
            $resubmitted ? 'resubmitted' : 'submitted',
            $leaveRequest->leave_number,
            $leaveRequest->start_date->format('d M Y'),
            $leaveRequest->end_date->format('d M Y'),
            number_format((int) $leaveRequest->days_requested)
        );

        $this->notifications->notifyUsers(
            $userIds,
            $title,
            $body,
            'leave_request',
            (string) $leaveRequest->id,
            'normal',
        );
    }

    private function notifyEmployeeDecision(LeaveRequest $leaveRequest, string $decision, ?string $notes = null): void
    {
        $employeeUserId = $leaveRequest->staff?->user_id;
        if (! $employeeUserId) {
            return;
        }

        $title = match ($decision) {
            'approved' => 'Leave request approved',
            'rejected' => 'Leave request rejected',
            'returned' => 'Leave request returned for changes',
            default => 'Leave request updated',
        };

        $body = match ($decision) {
            'approved' => "Your leave request {$leaveRequest->leave_number} has been approved.",
            'rejected' => "Your leave request {$leaveRequest->leave_number} was rejected. Reason: {$notes}",
            'returned' => "HR returned leave request {$leaveRequest->leave_number} for changes. Notes: {$notes}",
            default => "Your leave request {$leaveRequest->leave_number} was updated.",
        };

        $this->notifications->notifyUser(
            (int) $employeeUserId,
            $title,
            $body,
            'leave_request',
            (string) $leaveRequest->id,
            $decision === 'rejected' ? 'high' : 'normal',
        );
    }

    /**
     * @return list<int>
     */
    private function hrNotifierUserIds(): array
    {
        $slugs = ['hr.manage_leave', 'hr.staff.view'];

        $fromRoles = DB::table('user_roles as ur')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'ur.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->whereIn('p.slug', $slugs)
            ->distinct()
            ->pluck('ur.user_id')
            ->all();

        $fromDirect = DB::table('user_permissions as up')
            ->join('permissions as p', 'p.id', '=', 'up.permission_id')
            ->whereIn('p.slug', $slugs)
            ->distinct()
            ->pluck('up.user_id')
            ->all();

        return array_values(array_unique(array_map('intval', array_merge($fromRoles, $fromDirect))));
    }

    /**
     * @return array<string, mixed>
     */
    private function auditSnapshot(LeaveRequest $leaveRequest): array
    {
        return [
            'leave_number' => $leaveRequest->leave_number,
            'staff_id' => $leaveRequest->staff_id,
            'leave_type_id' => $leaveRequest->leave_type_id,
            'start_date' => $leaveRequest->start_date?->toDateString(),
            'end_date' => $leaveRequest->end_date?->toDateString(),
            'days_requested' => (int) $leaveRequest->days_requested,
            'overall_status' => $leaveRequest->overall_status,
        ];
    }

    public function validateMaxDays(LeaveType $leaveType, int $requestedDays): void
    {
        $normalEntitlement = (int) ($leaveType->days_allowed_per_year ?? 0);
        $carryForward = $leaveType->leave_code === 'ANNUAL' ? (int) ($leaveType->carry_forward_days ?? 0) : 0;
        $maxAllowed = $normalEntitlement + $carryForward;

        if ($requestedDays > $maxAllowed) {
            $message = "{$leaveType->leave_name} allows a maximum of {$maxAllowed} days";
            if ($carryForward > 0) {
                $message .= " (entitlement {$normalEntitlement} + carry forward {$carryForward})";
            }
            $message .= ". You requested {$requestedDays} days.";

            throw new \InvalidArgumentException($message);
        }
    }
}
