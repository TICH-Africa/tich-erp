<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use App\Services\AuthService;
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
        protected AuthService $authService,
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

        if ($this->rbacService->isPlatformAdministrator($user)) {
            return $next($request);
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

            return $this->denyAccess(
                $request,
                'You do not have permission to access that area. If you just joined, ask ICT or HR to assign your department role.'
            );
        }

        return $next($request);
    }

    private function denyAccess(Request $request, string $message): Response
    {
        $home = $this->authService->authenticatedHome($request->user());
        $current = $request->url();
        $homePath = parse_url($home, PHP_URL_PATH) ?: '';
        $currentPath = $request->getPathInfo();

        // Avoid ERR_TOO_MANY_REDIRECTS when the fallback is the same page (e.g. /dashboard).
        if ($current === $home || $currentPath === $homePath || $request->routeIs('dashboard', 'account.start')) {
            abort(403, $message);
        }

        return redirect()
            ->to($home)
            ->withErrors(['permission' => $message]);
    }
}
