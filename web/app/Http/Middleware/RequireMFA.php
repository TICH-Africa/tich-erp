<?php

namespace App\Http\Middleware;

use App\Services\MFAService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireMFA
{
    protected MFAService $mfaService;

    public function __construct(MFAService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if MFA is required for this user
        if (!$this->mfaService->isMFARequired($user)) {
            return $next($request);
        }

        // Check if MFA is enabled
        if (!$user->mfa_enabled) {
            return response()->json([
                'message' => 'MFA must be enabled for your account',
                'mfa_required' => true
            ], 403);
        }

        // Check if MFA has been verified in current session
        $mfaVerified = session('mfa_verified_' . $user->id);

        if (!$mfaVerified) {
            return response()->json([
                'message' => 'MFA verification required',
                'mfa_required' => true,
                'mfa_method' => $user->mfa_method
            ], 403);
        }

        // Check if MFA verification is still valid (30 minutes)
        $verifiedAt = session('mfa_verified_at_' . $user->id);
        if ($verifiedAt && now()->diffInMinutes($verifiedAt) > 30) {
            session()->forget(['mfa_verified_' . $user->id, 'mfa_verified_at_' . $user->id]);
            
            return response()->json([
                'message' => 'MFA verification expired',
                'mfa_required' => true,
                'mfa_method' => $user->mfa_method
            ], 403);
        }

        return $next($request);
    }
}
