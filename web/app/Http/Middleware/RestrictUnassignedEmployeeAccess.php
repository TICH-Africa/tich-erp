<?php

namespace App\Http\Middleware;

use App\Services\EmployeeAssignmentService;
use App\Services\RBACService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictUnassignedEmployeeAccess
{
    /**
     * Routes employees without a department assignment may still open.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTE_NAMES = [
        'dashboard',
        'account.start',
        'employee.profile.edit',
        'employee.profile.update',
        'logout',
        'mfa.setup',
        'mfa.verify',
        'mfa.resend',
    ];

    public function __construct(
        protected EmployeeAssignmentService $assignment,
        protected RBACService $rbacService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $this->rbacService->isPlatformAdministrator($user)) {
            return $next($request);
        }

        if (! $user || $this->assignment->canAccessBeyondDepartmentPicker($user)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && in_array($routeName, self::ALLOWED_ROUTE_NAMES, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Your account is not assigned to a department yet. Open the main dashboard and wait for HR or ICT to assign you.');
        }

        return redirect()
            ->route('dashboard')
            ->with(
                'warning',
                'You are not assigned to a department yet. Browse departments on the dashboard until HR or ICT links your profile to a unit.'
            );
    }
}
