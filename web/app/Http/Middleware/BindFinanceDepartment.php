<?php

namespace App\Http\Middleware;

use App\Services\Finance\FinanceNavigationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injects the Finance department into the route so controllers/views that still
 * type-hint Department $department work without /departments/{id} in the URL.
 */
class BindFinanceDepartment
{
    public function __construct(
        protected FinanceNavigationService $navigation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $department = $this->navigation->financeDepartment();
        abort_if(! $department, 404, 'Finance department is not configured.');

        $route = $request->route();
        if ($route && ! $route->parameter('department')) {
            $route->setParameter('department', $department);
        }

        return $next($request);
    }
}
