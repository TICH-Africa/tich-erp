<?php

namespace App\Http\Middleware;

use App\Services\StaffPortalService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAcademicsBudgetingAccess
{
    public function __construct(protected StaffPortalService $staffPortal) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->can('academics.read')) {
            return $next($request);
        }

        if ($user->hasRole('Lecturer/Tutor')) {
            return $next($request);
        }

        $staff = $this->staffPortal->staffForUser($user);
        if ($staff && $staff->is_teaching_staff) {
            return $next($request);
        }

        abort(403, 'You do not have access to academics budgeting.');
    }
}
