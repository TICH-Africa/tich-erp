<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\MFAService;
use App\Services\RBACService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected MFAService $mfaService,
        protected RBACService $rbacService,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->authService->attemptLogin($credentials['login'], $credentials['password']);

        if (! $user) {
            return response()->json(['message' => 'Invalid credentials or account locked'], 401);
        }

        Auth::login($user);

        return response()->json($this->authService->loginResponse($user, $request));
    }

    public function mfaChallenge(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $this->authService->verifyMfaCode($user, $request->code)) {
            return response()->json(['message' => 'Invalid or expired verification code'], 422);
        }

        $this->authService->markMfaVerified($request, $user);

        return response()->json([
            'message' => 'MFA verified successfully',
            'verified_at' => now(),
        ]);
    }

    public function mfaSetup(Request $request): JsonResponse
    {
        $request->validate([
            'method' => ['required', 'in:email,auth_app'],
        ]);

        $user = $request->user();

        if ($request->method === 'email') {
            $this->mfaService->sendEmailOTP($user);

            return response()->json([
                'message' => 'Verification code sent to your email',
                'method' => 'email',
            ]);
        }

        $secret = $this->mfaService->generateTOTPSecret();
        $this->mfaService->stageTOTPSecret($user, $secret);

        return response()->json([
            'method' => 'auth_app',
            'secret' => $secret,
            'qr_code_uri' => $this->mfaService->getTOTPQRCodeURI($user, $secret),
        ]);
    }

    public function mfaSetupVerify(Request $request): JsonResponse
    {
        $request->validate([
            'method' => ['required', 'in:email,auth_app'],
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($request->method === 'email') {
            if (! $this->mfaService->verifyEmailOTP($user, $request->code)) {
                return response()->json(['message' => 'Invalid or expired verification code'], 422);
            }

            $this->mfaService->enableMFA($user, 'email');
        } else {
            if (! $this->mfaService->verifyTOTP($user, $request->code)) {
                return response()->json(['message' => 'Invalid authenticator code'], 422);
            }

            $backupCodes = $this->mfaService->generateBackupCodes();
            $this->mfaService->enableMFA(
                $user,
                'auth_app',
                $user->mfa_secret_temp,
                $backupCodes
            );

            return response()->json([
                'message' => 'Authenticator MFA enabled',
                'backup_codes' => $backupCodes,
            ]);
        }

        $this->authService->markMfaVerified($request, $user);

        return response()->json(['message' => 'MFA enabled successfully']);
    }

    public function mfaDisable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();

        if ($this->mfaService->isMFARequired($user)) {
            return response()->json(['message' => 'MFA cannot be disabled for your account type'], 403);
        }

        if (! Hash::check($request->password, $user->password_hash)) {
            return response()->json(['message' => 'Invalid password'], 422);
        }

        $this->mfaService->disableMFA($user);
        $this->authService->clearMfaSession($request);

        return response()->json(['message' => 'MFA disabled successfully']);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        Auth::logout();
        $this->authService->clearMfaSession($request);

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'roles' => $this->rbacService->getUserRoles($user),
            'permissions' => $this->rbacService->getUserPermissions($user),
            'mfa_required' => $this->mfaService->isMFARequired($user),
            'mfa_enabled' => (bool) $user->mfa_enabled,
        ]);
    }
}
