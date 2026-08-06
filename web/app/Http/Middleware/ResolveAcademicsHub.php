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
        $department = $request->route('department');

        if (! $department instanceof Department) {
            $hub = Department::findAcademicsHub();
            abort_unless($hub, 404);
            $request->route()->setParameter('department', $hub);
        }

        return $next($request);
    }
}
