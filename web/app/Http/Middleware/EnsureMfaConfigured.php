<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Services\MFAService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaConfigured
{
    public function __construct(
        protected MFAService $mfaService,
        protected AuthService $authService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        if ($this->mfaService->isMFARequired($user) && ! $user->mfa_enabled) {
            if ($request->routeIs('mfa.setup', 'mfa.setup.*', 'logout')) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'MFA setup is required before accessing this resource',
                    'mfa_setup_required' => true,
                ], 403);
            }

            $this->authService->rememberIntendedUrl($request);

            return redirect()->route('mfa.setup');
        }

        return $next($request);
    }
}
