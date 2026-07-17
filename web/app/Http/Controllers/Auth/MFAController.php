<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MFAService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MFAController extends Controller
{
    protected MFAService $mfaService;

    public function __construct(MFAService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * Send email OTP for MFA verification
     */
    public function sendEmailOTP(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $this->mfaService->sendEmailOTP($user);

        return response()->json([
            'message' => 'OTP sent to your email',
            'expires_in' => 600 // 10 minutes
        ]);
    }

    /**
     * Verify email OTP
     */
    public function verifyEmailOTP(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => 'required|string|digits:6'
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($this->mfaService->verifyEmailOTP($user, $request->otp)) {
            $this->mfaService->recordVerification($user);

            return response()->json([
                'message' => 'OTP verified successfully',
                'verified_at' => now()
            ]);
        }

        return response()->json([
            'message' => 'Invalid or expired OTP'
        ], 422);
    }

    /**
     * Setup TOTP (Authenticator App)
     */
    public function setupTOTP(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $secret = $this->mfaService->generateTOTPSecret();
        $backupCodes = $this->mfaService->generateBackupCodes();
        $qrCodeUri = $this->mfaService->getTOTPQRCodeURI($user);

        return response()->json([
            'secret' => $secret,
            'backup_codes' => $backupCodes,
            'qr_code_uri' => $qrCodeUri,
            'message' => 'Save your backup codes securely. They will not be shown again.'
        ]);
    }

    /**
     * Enable TOTP MFA
     */
    public function enableTOTP(Request $request): JsonResponse
    {
        $request->validate([
            'secret' => 'required|string',
            'backup_codes' => 'required|array',
            'verification_code' => 'required|string|digits:6'
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Verify the code before enabling
        $tempUser = clone $user;
        $tempUser->mfa_secret = $request->secret;

        if (!$this->mfaService->verifyTOTP($tempUser, $request->verification_code)) {
            return response()->json([
                'message' => 'Invalid verification code'
            ], 422);
        }

        $this->mfaService->enableMFA(
            $user,
            'auth_app',
            $request->secret,
            $request->backup_codes
        );

        return response()->json([
            'message' => 'TOTP MFA enabled successfully',
            'enabled_at' => now()
        ]);
    }

    /**
     * Enable Email MFA
     */
    public function enableEmailMFA(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $this->mfaService->enableMFA($user, 'email');

        return response()->json([
            'message' => 'Email MFA enabled successfully',
            'enabled_at' => now()
        ]);
    }

    /**
     * Verify TOTP code during login
     */
    public function verifyTOTP(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|digits:6'
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($this->mfaService->verifyTOTP($user, $request->code)) {
            $this->mfaService->recordVerification($user);

            return response()->json([
                'message' => 'TOTP verified successfully',
                'verified_at' => now()
            ]);
        }

        return response()->json([
            'message' => 'Invalid TOTP code'
        ], 422);
    }

    /**
     * Verify backup code
     */
    public function verifyBackupCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($this->mfaService->verifyBackupCode($user, $request->code)) {
            $this->mfaService->recordVerification($user);

            return response()->json([
                'message' => 'Backup code verified successfully',
                'verified_at' => now(),
                'remaining_codes' => is_array($user->mfa_backup_codes) ? count($user->mfa_backup_codes) : 0,
            ]);
        }

        return response()->json([
            'message' => 'Invalid backup code'
        ], 422);
    }

    /**
     * Disable MFA
     */
    public function disableMFA(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Verify password before disabling MFA
        if (!password_verify($request->password, $user->password_hash)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 422);
        }

        $this->mfaService->disableMFA($user, 'Disabled via MFA API', $request);

        return response()->json([
            'message' => 'MFA disabled successfully',
            'disabled_at' => now()
        ]);
    }

    /**
     * Get MFA status
     */
    public function getMFAStatus(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'mfa_enabled' => (bool) $user->mfa_enabled,
            'mfa_method' => $user->mfa_method,
            'mfa_enabled_at' => $user->mfa_enabled_at,
            'mfa_last_verified_at' => $user->mfa_last_verified_at,
            'has_backup_codes' => !empty($user->mfa_backup_codes),
            'mfa_required' => $this->mfaService->isMFARequired($user)
        ]);
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Verify password before regenerating codes
        if (!password_verify($request->password, $user->password_hash)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 422);
        }

        $backupCodes = $this->mfaService->generateBackupCodes();
        $user->update(['mfa_backup_codes' => $backupCodes]);

        return response()->json([
            'message' => 'Backup codes regenerated successfully',
            'backup_codes' => $backupCodes
        ]);
    }
}
