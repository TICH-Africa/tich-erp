<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeAssignmentService
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected RBACService $rbacService,
    ) {}

    public function isAwaitingDepartmentAssignment(User $user, ?Staff $staff = null): bool
    {
        if ($this->rbacService->hasRole($user, 'Super Admin')) {
            return false;
        }

        if ($user->isEnrolledStudent()) {
            return false;
        }

        $staff ??= $this->employeePortal->staffForUser($user);

        if (! $staff) {
            return false;
        }

        return ! $this->hasDepartmentAssignment($user, $staff);
    }

    public function hasDepartmentAssignment(User $user, ?Staff $staff = null): bool
    {
        $staff ??= $this->employeePortal->staffForUser($user);

        if ($staff?->department_id) {
            return true;
        }

        return DB::table('user_roles')
            ->where('user_id', $user->id)
            ->whereNotNull('department_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function canAccessBeyondDepartmentPicker(User $user): bool
    {
        return ! $this->isAwaitingDepartmentAssignment($user);
    }
}
