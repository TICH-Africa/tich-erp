<?php

namespace App\Services\Leave;

use App\Models\Department;
use App\Models\LeaveCoverageAccessGrant;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestCoverage;
use App\Models\Staff;
use App\Models\User;
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
     * Staff already covering an approved/pending leave (for warning).
     *
     * @return Collection<int, LeaveRequestCoverage>
     */
    public function activeCoveragesForStaff(int $coverStaffId): Collection
    {
        return LeaveRequestCoverage::query()
            ->with(['leaveRequest.staff', 'department'])
            ->where('cover_staff_id', $coverStaffId)
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

            $coverage = LeaveRequestCoverage::query()->updateOrCreate(
                [
                    'leave_request_id' => $leaveRequest->id,
                    'department_id' => $department->id,
                ],
                [
                    'cover_staff_id' => $coverStaffId,
                    'status' => 'accepted',
                    'notified_at' => now(),
                ]
            );

            $this->notifyCoverPerson($coverage->load('department'), $leaveRequest, $applicant, $coverStaff);
            $created[] = $coverage;
        }

        return $created;
    }

    public function grantAccessForApprovedLeave(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->loadMissing(['coverages.coverStaff', 'staff']);

        foreach ($leaveRequest->coverages as $coverage) {
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

        // If applicant has no role scoped to this dept, grant a generic Staff role for the dept.
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
            'Leave coverage appointment',
            $applicant->fullName().' appointed you to cover '.$dept.' from '
                .$leaveRequest->start_date?->format('d M Y').' to '.$leaveRequest->end_date?->format('d M Y')
                .'. This is automatically accepted. You will receive department access when HR approves the leave.',
            'leave_request',
            (string) $leaveRequest->id,
            'normal',
            route('employee.dashboard'),
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
