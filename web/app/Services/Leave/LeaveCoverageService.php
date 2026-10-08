<?php

namespace App\Services\Leave;

use App\Models\Department;
use App\Models\LeaveCoverageAccessGrant;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestCoverage;
use App\Models\Staff;
use App\Services\PlatformNotificationService;
use App\Services\RBACService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeaveCoverageService
{
    public function __construct(
        protected PlatformNotificationService $notifications,
        protected RBACService $rbac,
    ) {}

    /**
     * Departments the applicant must assign coverage for (role assignments + home dept).
     *
     * @return Collection<int, Department>
     */
    public function departmentsRequiringCoverage(Staff $staff): Collection
    {
        $ids = collect();

        if ($staff->department_id) {
            $ids->push((int) $staff->department_id);
        }

        if ($staff->user_id && Schema::hasTable('user_roles')) {
            $roleDeptIds = DB::table('user_roles')
                ->where('user_id', $staff->user_id)
                ->whereNotNull('department_id')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->pluck('department_id');
            $ids = $ids->merge($roleDeptIds);
        }

        $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Department::query()->whereIn('id', $ids->all())->orderBy('dept_name')->get();
    }

    /**
     * Pending stand-in requests for this staff member to accept or decline.
     *
     * @return Collection<int, LeaveRequestCoverage>
     */
    public function pendingForCoverStaff(Staff $coverStaff): Collection
    {
        return LeaveRequestCoverage::query()
            ->with(['leaveRequest.staff', 'leaveRequest.leaveType', 'department', 'coverStaff'])
            ->where('cover_staff_id', $coverStaff->id)
            ->where('status', LeaveRequestCoverage::STATUS_PENDING)
            ->whereHas('leaveRequest', function ($q) {
                $q->whereIn('overall_status', ['pending_hr', 'returned', 'approved'])
                    ->where('end_date', '>=', now()->toDateString());
            })
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Staff already covering an approved/pending leave (for warning).
     *
     * @return Collection<int, LeaveRequestCoverage>
     */
    public function activeCoveragesForStaff(int $coverStaffId): Collection
    {
        return LeaveRequestCoverage::query()
            ->with(['leaveRequest.staff', 'department'])
            ->where('cover_staff_id', $coverStaffId)
            ->where('status', LeaveRequestCoverage::STATUS_ACCEPTED)
            ->whereNull('access_revoked_at')
            ->whereHas('leaveRequest', function ($q) {
                $q->whereIn('overall_status', ['pending_hr', 'approved'])
                    ->where('end_date', '>=', now()->toDateString());
            })
            ->get();
    }

    /**
     * @param  array<int, int>  $coverByDepartment  department_id => cover_staff_id
     * @return list<LeaveRequestCoverage>
     */
    public function assignCoverages(LeaveRequest $leaveRequest, Staff $applicant, array $coverByDepartment): array
    {
        $required = $this->departmentsRequiringCoverage($applicant);
        $created = [];

        foreach ($required as $department) {
            $coverStaffId = (int) ($coverByDepartment[$department->id] ?? 0);
            if ($coverStaffId < 1) {
                throw new \InvalidArgumentException('Select who will take over for '.$department->dept_name.'.');
            }
            if ($coverStaffId === (int) $applicant->id) {
                throw new \InvalidArgumentException('You cannot appoint yourself as takeover for '.$department->dept_name.'.');
            }

            $coverStaff = Staff::query()->find($coverStaffId);
            if (! $coverStaff) {
                throw new \InvalidArgumentException('Invalid takeover person for '.$department->dept_name.'.');
            }

            $existing = LeaveRequestCoverage::query()
                ->where('leave_request_id', $leaveRequest->id)
                ->where('department_id', $department->id)
                ->first();

            $samePersonAlreadyAccepted = $existing
                && (int) $existing->cover_staff_id === $coverStaffId
                && $existing->status === LeaveRequestCoverage::STATUS_ACCEPTED;

            $coverage = LeaveRequestCoverage::query()->updateOrCreate(
                [
                    'leave_request_id' => $leaveRequest->id,
                    'department_id' => $department->id,
                ],
                [
                    'cover_staff_id' => $coverStaffId,
                    'status' => $samePersonAlreadyAccepted
                        ? LeaveRequestCoverage::STATUS_ACCEPTED
                        : LeaveRequestCoverage::STATUS_PENDING,
                    'notified_at' => now(),
                    'responded_at' => $samePersonAlreadyAccepted ? ($existing->responded_at ?? now()) : null,
                    'response_notes' => $samePersonAlreadyAccepted ? $existing->response_notes : null,
                ]
            );

            if (! $samePersonAlreadyAccepted) {
                $this->notifyCoverPerson($coverage->load('department'), $leaveRequest, $applicant, $coverStaff);
            }

            $created[] = $coverage;
        }

        return $created;
    }

    public function acceptCoverage(LeaveRequestCoverage $coverage, Staff $actingStaff, ?string $notes = null): LeaveRequestCoverage
    {
        $this->assertCanRespond($coverage, $actingStaff);

        if ($coverage->isAccepted()) {
            return $coverage;
        }

        if ($coverage->isDeclined()) {
            throw new \InvalidArgumentException('This stand-in request was already declined.');
        }

        $coverage->update([
            'status' => LeaveRequestCoverage::STATUS_ACCEPTED,
            'responded_at' => now(),
            'response_notes' => filled($notes) ? trim($notes) : null,
        ]);

        $coverage->loadMissing(['leaveRequest.staff', 'leaveRequest.leaveType', 'department', 'coverStaff']);
        $this->notifyApplicantOfResponse($coverage, accepted: true);
        $this->notifyCoverPersonOfOwnResponse($coverage, accepted: true);

        $leave = $coverage->leaveRequest;
        if ($leave && $leave->overall_status === 'approved') {
            $this->grantAccessForApprovedLeave($leave->fresh(['coverages.coverStaff', 'staff']));
        }

        return $coverage->fresh(['leaveRequest.staff', 'department', 'coverStaff']);
    }

    public function declineCoverage(LeaveRequestCoverage $coverage, Staff $actingStaff, ?string $notes = null): LeaveRequestCoverage
    {
        $this->assertCanRespond($coverage, $actingStaff);

        if ($coverage->isDeclined()) {
            return $coverage;
        }

        if ($coverage->isAccepted() && $coverage->access_granted_at && ! $coverage->access_revoked_at) {
            throw new \InvalidArgumentException('You already have leave cover access for this request and cannot decline it.');
        }

        $coverage->update([
            'status' => LeaveRequestCoverage::STATUS_DECLINED,
            'responded_at' => now(),
            'response_notes' => filled($notes) ? trim($notes) : null,
        ]);

        $coverage->loadMissing(['leaveRequest.staff', 'leaveRequest.leaveType', 'department', 'coverStaff']);
        $this->notifyApplicantOfResponse($coverage, accepted: false);
        $this->notifyCoverPersonOfOwnResponse($coverage, accepted: false);

        return $coverage->fresh(['leaveRequest.staff', 'department', 'coverStaff']);
    }

    public function assertAllCoveragesAccepted(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->loadMissing(['coverages.department', 'coverages.coverStaff']);

        $blocking = $leaveRequest->coverages->filter(
            fn (LeaveRequestCoverage $c) => ! $c->isAccepted()
        );

        if ($blocking->isEmpty()) {
            return;
        }

        $parts = $blocking->map(function (LeaveRequestCoverage $c) {
            $who = $c->coverStaff?->fullName() ?? 'stand-in';
            $dept = $c->department?->dept_name ?? 'department';

            return $who.' ('.$dept.' - '.$c->statusLabel().')';
        })->implode('; ');

        throw new \InvalidArgumentException(
            'Cannot approve leave until every stand-in has accepted. Still outstanding: '.$parts.'.'
        );
    }

    /**
     * Re-notify cover staff for pending rows (e.g. after migration reopened auto-accepts).
     */
    public function renotifyPendingCoverages(): int
    {
        $pending = LeaveRequestCoverage::query()
            ->with(['leaveRequest.staff', 'department', 'coverStaff'])
            ->where('status', LeaveRequestCoverage::STATUS_PENDING)
            ->whereHas('leaveRequest', function ($q) {
                $q->whereIn('overall_status', ['pending_hr', 'returned'])
                    ->where('end_date', '>=', now()->toDateString());
            })
            ->get();

        $count = 0;
        foreach ($pending as $coverage) {
            $leave = $coverage->leaveRequest;
            $applicant = $leave?->staff;
            $coverStaff = $coverage->coverStaff;
            if (! $leave || ! $applicant || ! $coverStaff) {
                continue;
            }
            $this->notifyCoverPerson($coverage, $leave, $applicant, $coverStaff);
            $coverage->update(['notified_at' => now()]);
            $count++;
        }

        return $count;
    }

    public function grantAccessForApprovedLeave(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->loadMissing(['coverages.coverStaff', 'staff']);

        foreach ($leaveRequest->coverages as $coverage) {
            if (! $coverage->isAccepted()) {
                continue;
            }
            if ($coverage->access_granted_at && ! $coverage->access_revoked_at) {
                continue;
            }

            $this->grantDepartmentAccess($coverage, $leaveRequest);
            $coverage->update(['access_granted_at' => now(), 'access_revoked_at' => null]);
        }
    }

    public function revokeAccessForLeave(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->loadMissing('coverages.accessGrants');

        foreach ($leaveRequest->coverages as $coverage) {
            $this->revokeDepartmentAccess($coverage);
            if (! $coverage->access_revoked_at) {
                $coverage->update(['access_revoked_at' => now()]);
            }
        }
    }

    private function assertCanRespond(LeaveRequestCoverage $coverage, Staff $actingStaff): void
    {
        if ((int) $coverage->cover_staff_id !== (int) $actingStaff->id) {
            throw new \InvalidArgumentException('You are not the appointed stand-in for this leave request.');
        }

        $leave = $coverage->leaveRequest()->first();
        if (! $leave || in_array($leave->overall_status, ['cancelled', 'rejected'], true)) {
            throw new \InvalidArgumentException('This leave request is no longer active.');
        }
    }

    private function grantDepartmentAccess(LeaveRequestCoverage $coverage, LeaveRequest $leaveRequest): void
    {
        $applicant = $leaveRequest->staff;
        $coverStaff = $coverage->coverStaff;
        if (! $applicant?->user_id || ! $coverStaff?->user_id) {
            return;
        }

        $applicantRoles = DB::table('user_roles')
            ->where('user_id', $applicant->user_id)
            ->where('department_id', $coverage->department_id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get();

        if ($applicantRoles->isEmpty()) {
            $staffRoleId = DB::table('roles')->where('role_name', 'Staff')->value('id');
            if ($staffRoleId) {
                $applicantRoles = collect([(object) [
                    'role_id' => $staffRoleId,
                    'campus_id' => null,
                    'department_id' => $coverage->department_id,
                ]]);
            }
        }

        $expiresAt = $leaveRequest->end_date?->copy()->endOfDay();

        foreach ($applicantRoles as $roleRow) {
            $preexisting = DB::table('user_roles')
                ->where('user_id', $coverStaff->user_id)
                ->where('role_id', $roleRow->role_id)
                ->where('department_id', $coverage->department_id)
                ->where(function ($q) use ($roleRow) {
                    if ($roleRow->campus_id) {
                        $q->where('campus_id', $roleRow->campus_id);
                    } else {
                        $q->whereNull('campus_id');
                    }
                })
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();

            if (! $preexisting) {
                DB::table('user_roles')->updateOrInsert(
                    [
                        'user_id' => $coverStaff->user_id,
                        'role_id' => $roleRow->role_id,
                        'campus_id' => $roleRow->campus_id,
                        'department_id' => $coverage->department_id,
                    ],
                    [
                        'assigned_at' => now(),
                        'assigned_by' => $applicant->user_id,
                        'expires_at' => $expiresAt,
                    ]
                );
            }

            LeaveCoverageAccessGrant::query()->create([
                'leave_request_coverage_id' => $coverage->id,
                'cover_user_id' => $coverStaff->user_id,
                'role_id' => $roleRow->role_id,
                'department_id' => $coverage->department_id,
                'campus_id' => $roleRow->campus_id,
                'was_preexisting' => $preexisting,
                'granted_at' => now(),
            ]);
        }
    }

    private function revokeDepartmentAccess(LeaveRequestCoverage $coverage): void
    {
        $grants = LeaveCoverageAccessGrant::query()
            ->where('leave_request_coverage_id', $coverage->id)
            ->whereNull('revoked_at')
            ->get();

        foreach ($grants as $grant) {
            if (! $grant->was_preexisting) {
                DB::table('user_roles')
                    ->where('user_id', $grant->cover_user_id)
                    ->where('role_id', $grant->role_id)
                    ->where('department_id', $grant->department_id)
                    ->when(
                        $grant->campus_id,
                        fn ($q) => $q->where('campus_id', $grant->campus_id),
                        fn ($q) => $q->whereNull('campus_id')
                    )
                    ->delete();
            }

            $grant->update(['revoked_at' => now()]);
        }
    }

    private function notifyCoverPerson(
        LeaveRequestCoverage $coverage,
        LeaveRequest $leaveRequest,
        Staff $applicant,
        Staff $coverStaff
    ): void {
        if (! $coverStaff->user_id) {
            return;
        }

        $dept = $coverage->department?->dept_name ?? 'department';
        $this->notifications->notifyUser(
            (int) $coverStaff->user_id,
            'Leave stand-in request - please respond',
            $applicant->fullName().' asked you to cover '.$dept.' from '
                .$leaveRequest->start_date?->format('d M Y').' to '.$leaveRequest->end_date?->format('d M Y')
                .'. Please accept or decline on My leave. Department access is granted only after you accept and HR approves the leave.',
            'leave_request',
            (string) $leaveRequest->id,
            'high',
            route('employee.leave.index').'#leave-coverage-inbox',
        );
    }

    private function notifyApplicantOfResponse(LeaveRequestCoverage $coverage, bool $accepted): void
    {
        $leave = $coverage->leaveRequest;
        $applicant = $leave?->staff;
        $coverStaff = $coverage->coverStaff;
        if (! $applicant?->user_id || ! $coverStaff) {
            return;
        }

        $dept = $coverage->department?->dept_name ?? 'department';
        $title = $accepted
            ? 'Stand-in accepted your leave cover request'
            : 'Stand-in declined your leave cover request';
        $body = $accepted
            ? $coverStaff->fullName().' accepted covering '.$dept.' for your leave '
                .$leave->start_date?->format('d M Y').' to '.$leave->end_date?->format('d M Y').'.'
            : $coverStaff->fullName().' declined covering '.$dept.' for your leave '
                .$leave->start_date?->format('d M Y').' to '.$leave->end_date?->format('d M Y')
                .'. Please edit the leave request and appoint someone else.';

        if ($coverage->response_notes) {
            $body .= ' Note: '.$coverage->response_notes;
        }

        $this->notifications->notifyUser(
            (int) $applicant->user_id,
            $title,
            $body,
            'leave_request',
            (string) $leave->id,
            $accepted ? 'normal' : 'high',
            route('employee.leave.index'),
        );
    }

    private function notifyCoverPersonOfOwnResponse(LeaveRequestCoverage $coverage, bool $accepted): void
    {
        $leave = $coverage->leaveRequest;
        $applicant = $leave?->staff;
        $coverStaff = $coverage->coverStaff;
        if (! $coverStaff?->user_id || ! $applicant) {
            return;
        }

        $dept = $coverage->department?->dept_name ?? 'department';
        $title = $accepted ? 'You accepted a leave stand-in request' : 'You declined a leave stand-in request';
        $body = $accepted
            ? 'You accepted covering '.$dept.' for '.$applicant->fullName().' ('
                .$leave->start_date?->format('d M Y').' to '.$leave->end_date?->format('d M Y')
                .'). You will receive department access when HR approves the leave.'
            : 'You declined covering '.$dept.' for '.$applicant->fullName().' ('
                .$leave->start_date?->format('d M Y').' to '.$leave->end_date?->format('d M Y').').';

        $this->notifications->notifyUser(
            (int) $coverStaff->user_id,
            $title,
            $body,
            'leave_request',
            (string) $leave->id,
            'normal',
            route('employee.leave.index'),
        );
    }

    /**
     * Staff eligible to be selected as cover (active employees with a user account).
     *
     * @return Collection<int, Staff>
     */
    public function eligibleCoverStaff(?int $excludeStaffId = null): Collection
    {
        return Staff::query()
            ->with('department:id,dept_name')
            ->excludePlatformOperators()
            ->whereNotNull('user_id')
            ->whereIn('employment_status', ['active', 'onboarding', 'probation'])
            ->when($excludeStaffId, fn ($q) => $q->where('id', '!=', $excludeStaffId))
            ->orderBy('first_name')
            ->orderBy('surname')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'department_id', 'user_id', 'job_title']);
    }
}
