<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use App\Services\RBACService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffPortalAccess
{
    public function __construct(protected RBACService $rbac) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $staff = Staff::query()
            ->where('user_id', $user->id)
            ->when($user->staff_id, fn ($query) => $query->orWhere('id', $user->staff_id))
            ->first();

        if (! $staff) {
            abort(403, 'Staff portal access requires a linked staff profile. Contact HR or your administrator.');
        }

        if (! app(\App\Services\StaffPortalService::class)->isTeachingStaff($user)) {
            abort(403, 'You do not have teaching staff portal access.');
        }

        $request->attributes->set('portal_staff', $staff);

        return $next($request);
    }
}
