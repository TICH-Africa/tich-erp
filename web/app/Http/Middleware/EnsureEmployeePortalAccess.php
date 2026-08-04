<?php

namespace App\Http\Middleware;

use App\Services\EmployeePortalService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeePortalAccess
{
    public function __construct(protected EmployeePortalService $employeePortal) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $staff = $this->employeePortal->staffForUser($user);

        if (! $staff) {
            abort(403, 'My Employee Portal requires a linked employee profile. Contact HR if your account is not linked.');
        }

        $request->attributes->set('portal_staff', $staff);

        return $next($request);
    }
}
