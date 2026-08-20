<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Services\EmployeeProfileCompletenessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeProfileComplete
{
    public function __construct(
        protected EmployeeProfileCompletenessService $completeness,
        protected AuthService $authService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->completeness->mustCompleteProfile($user)) {
            return $next($request);
        }

        if ($request->routeIs(
            'employee.profile.edit',
            'employee.profile.update',
            'employee.sidebar-notifications',
            'account.start',
            'logout',
        )) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Complete your employee profile before continuing.',
                'profile_completion_required' => true,
                'redirect' => route('employee.profile.edit'),
            ], 403);
        }

        $this->authService->rememberIntendedUrl($request);

        return redirect()
            ->route('employee.profile.edit')
            ->with('warning', 'Complete your employee profile before using the ERP. This is required for accountability and emergency contact records.');
    }
}
