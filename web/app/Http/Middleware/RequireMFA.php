<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Services\MFAService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireMFA
{
    public function __construct(
        protected MFAService $mfaService,
        protected AuthService $authService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $this->deny($request, 'Unauthenticated', 401);
        }

        if (! $this->mfaService->isMFARequired($user)) {
            return $next($request);
        }

        if (! $user->mfa_enabled) {
            return $this->deny($request, 'MFA must be configured for your account', 403, [
                'mfa_setup_required' => true,
                'redirect' => route('mfa.setup'),
            ]);
        }

        if (! $this->authService->isMfaSessionValid($request, $user)) {
            return $this->deny($request, 'MFA verification required', 403, [
                'mfa_required' => true,
                'mfa_method' => $user->mfa_method,
                'redirect' => route('mfa.verify'),
            ]);
        }

        return $next($request);
    }

    private function deny(Request $request, string $message, int $status, array $extra = []): Response
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge(['message' => $message], $extra), $status);
        }

        if (! empty($extra['mfa_setup_required'])) {
            app(AuthService::class)->rememberIntendedUrl($request);

            return redirect()->route('mfa.setup')->with('status', $message);
        }

        app(AuthService::class)->rememberIntendedUrl($request);

        return redirect()->route('mfa.verify')->with('status', $message);
    }
}
