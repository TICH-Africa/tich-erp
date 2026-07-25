<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use App\Services\RBACService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        protected RBACService $rbacService,
        protected AuditService $auditService,
    ) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = Auth::user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            return redirect()->guest(route('login'));
        }

        $allowed = count($permissions) > 1
            ? $this->rbacService->hasAnyPermission($user, $permissions)
            : $this->rbacService->hasPermission($user, $permissions[0] ?? '');

        if (! $allowed) {
            $this->auditService->log(
                'access.denied',
                'routes',
                $request->path(),
                null,
                [
                    'required_permissions' => $permissions,
                    'method' => $request->method(),
                    'route' => $request->route()?->getName(),
                ],
                'Permission check failed',
                'failure',
                $user->id,
                $request
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action',
                    'required_permission' => $permissions,
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->withErrors(['permission' => 'You do not have permission to access that area.']);
        }

        return $next($request);
    }
}
