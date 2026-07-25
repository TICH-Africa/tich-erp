<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;

class StaffPortalService
{
    public function staffForUser(User $user): ?Staff
    {
        if ($user->staff_id) {
            $staff = Staff::query()->with('department')->find($user->staff_id);
            if ($staff) {
                return $staff;
            }
        }

        return Staff::query()
            ->with('department')
            ->where('user_id', $user->id)
            ->first();
    }

    public function isTeachingStaff(User $user): bool
    {
        $staff = $this->staffForUser($user);

        if (! $staff) {
            return false;
        }

        $rbac = app(RBACService::class);

        return $staff->is_teaching_staff
            || $rbac->hasAnyRole($user, ['Lecturer', 'HOD', 'Dean', 'Academic Registrar', 'Super Admin']);
    }
}
