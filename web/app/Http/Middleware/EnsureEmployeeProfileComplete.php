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
        return $next($request);
    }
}
