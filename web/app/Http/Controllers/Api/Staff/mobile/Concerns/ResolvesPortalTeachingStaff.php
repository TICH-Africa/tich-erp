<?php

namespace App\Http\Controllers\Api\Staff\mobile\Concerns;

use App\Models\Staff;
use App\Services\StaffPortalService;
use Illuminate\Http\Request;

trait ResolvesPortalTeachingStaff
{
    protected function portalStaff(Request $request): Staff
    {
        $staff = $request->attributes->get('portal_staff');

        if ($staff instanceof Staff) {
            return $staff;
        }

        /** @var StaffPortalService $portal */
        $portal = app(StaffPortalService::class);
        $staff = $portal->staffForUser($request->user());

        abort_unless($staff, 403, 'Staff portal requires a linked staff profile.');
        abort_unless(
            $portal->isTeachingStaff($request->user()),
            403,
            'You do not have teaching staff portal access.'
        );

        $request->attributes->set('portal_staff', $staff);

        return $staff;
    }
}
