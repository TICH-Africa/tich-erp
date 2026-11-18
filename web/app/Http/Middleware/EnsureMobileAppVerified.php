<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileAppVerified
{
    public function __construct(protected AuthService $authService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->authService->isMobileClient($request)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->mobile_app_verified_at !== null) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Verify the email code to continue using the mobile app.',
            'code' => 'mobile_otp_required',
            'mobile_otp_required' => true,
        ], 403);
    }
}