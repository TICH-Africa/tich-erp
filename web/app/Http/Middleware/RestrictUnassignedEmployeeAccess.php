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
     * Exact route names employees without a department assignment may still open
     * (beyond the employee.* and notifications.* prefixes).
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTE_NAMES = [
        'dashboard',
        'account.start',
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

        if ($user && ($this->rbacService->isPlatformAdministrator($user) || $this->rbacService->hasRole($user, 'CEO'))) {
            return $next($request);
        }

        if (! $user || $this->assignment->canAccessBeyondDepartmentPicker($user)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && $this->isAllowedWhileUnassigned($routeName)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Your account is not assigned to a department yet. Use My Employee Portal until HR or ICT assigns you to a unit.');
        }

        return redirect()
            ->route('employee.dashboard')
            ->with(
                'warning',
                'You are not assigned to a department yet. Department modules stay locked until HR or ICT links your profile to a unit. Use My Employee Portal for personal tools.'
            );
    }

    private function isAllowedWhileUnassigned(string $routeName): bool
    {
        if (in_array($routeName, self::ALLOWED_ROUTE_NAMES, true)) {
            return true;
        }

        // Personal employee portal (profile, leave, attendance, documents, etc.)
        if (str_starts_with($routeName, 'employee.')) {
            return true;
        }

        if (str_starts_with($routeName, 'notifications.')) {
            return true;
        }

        return false;
    }
}
