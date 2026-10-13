<?php

namespace App\Http\Controllers\Api\Employee\Concerns;

use App\Models\Staff;
use App\Services\EmployeePortalService;
use Illuminate\Http\Request;

trait ResolvesPortalStaff
{
    protected function portalStaff(Request $request): Staff
    {
        $staff = $request->attributes->get('portal_staff');

        if ($staff instanceof Staff) {
            return $staff;
        }

        /** @var EmployeePortalService $portal */
        $portal = app(EmployeePortalService::class);
        $staff = $portal->ensureStaffProfile($request->user())
            ?? $portal->staffForUser($request->user());

        abort_unless($staff, 403, 'My Employee Portal requires a linked employee profile.');

        $request->attributes->set('portal_staff', $staff);

        return $staff;
    }
}
