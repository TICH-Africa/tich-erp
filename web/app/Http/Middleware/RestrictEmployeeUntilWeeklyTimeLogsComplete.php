<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use App\Services\WeeklyTimeLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class RestrictEmployeeUntilWeeklyTimeLogsComplete
{
    public function __construct(
        protected WeeklyTimeLogService $timeLogs,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->attributes->get('portal_staff');
        if (! $staff instanceof Staff) {
            return $next($request);
        }

        $hold = $this->timeLogs->portalComplianceHold($staff);
        if ($hold === null) {
            return $next($request);
        }

        View::share('employeeTimeLogHold', $hold);

        if ($this->routeAllowedWhileTimeLogHoldRequired($request)) {
            return $next($request);
        }

        $message = 'Complete and submit your weekly time logs for '.$hold['month_label']
            .' before using other employee portal sections.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'time_logs_required' => true,
                'hold' => $hold,
            ], 403);
        }

        return redirect()
            ->route('employee.time-logs.index')
            ->with('warning', $message);
    }

    private function routeAllowedWhileTimeLogHoldRequired(Request $request): bool
    {
        $path = trim($request->path(), '/');
        if (preg_match('#^(api/)?employee/time-log-compliance$#', $path)) {
            return true;
        }

        if (preg_match('#^(api/)?employee/time-logs($|/)#', $path)) {
            return true;
        }

        $routeName = $request->route()?->getName() ?? '';
        if (str_starts_with($routeName, 'employee.time-logs.')) {
            return true;
        }

        return $routeName === 'employee.sidebar-notifications';
    }
}
