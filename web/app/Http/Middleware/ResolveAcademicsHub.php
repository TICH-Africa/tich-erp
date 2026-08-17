<?php

namespace App\Http\Middleware;

use App\Models\Department;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAcademicsHub
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $department = $route?->parameter('department');

        if (! $department instanceof Department) {
            $hub = Department::findAcademicsHub();
            abort_unless($hub, 404);

            $existing = $route->parameters();

            foreach (array_keys($existing) as $key) {
                $route->forgetParameter($key);
            }

            // Controller actions are unpacked positionally. Hub URLs bind
            // {program} first, so department must be inserted ahead of it.
            $route->setParameter('department', $hub);

            foreach ($existing as $key => $value) {
                if ($key !== 'department') {
                    $route->setParameter($key, $value);
                }
            }
        }

        return $next($request);
    }
}
