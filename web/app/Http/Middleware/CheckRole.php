<?php

namespace App\Http\Middleware;

use App\Services\RBACService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function __construct(protected RBACService $rbacService) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            return redirect()->guest(route('login'));
        }

        $minimumRole = null;
        $roleNames = [];

        foreach ($roles as $role) {
            if (str_starts_with($role, 'min:')) {
                $minimumRole = substr($role, 4);
            } else {
                $roleNames[] = $role;
            }
        }

        $allowed = $minimumRole
            ? $this->rbacService->hasMinimumRole($user, $minimumRole)
            : $this->rbacService->hasAnyRole($user, $roleNames);

        if (! $allowed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your role does not allow access to this resource',
                    'required_roles' => $roles,
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->withErrors(['role' => 'Your role does not allow access to that area.']);
        }

        return $next($request);
    }
}
