<?php

namespace App\Http\Middleware;

use App\Support\ExecutivePortal;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureExecutiveReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $readOnly = ExecutivePortal::isReadOnly($request->user());

        View::share('executiveReadOnly', $readOnly);

        if ($readOnly && ! $request->isMethodSafe()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Chief Institution Administrator access is read-only.',
                ], 403);
            }

            abort(403, 'Chief Institution Administrator access is read-only. You can view records but cannot approve, reject, or sign.');
        }

        return $next($request);
    }
}
