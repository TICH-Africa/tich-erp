<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        protected MFAService $mfaService,
        protected RBACService $rbacService,
    ) {}

    public function attemptLogin(string $login, string $password): ?User
    {
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::query()
            ->where($loginField, $login)
            ->where('is_active', 1)
            ->first();

        if (! $user || ! Hash::check($password, $user->password_hash)) {
            if ($user) {
                $this->recordFailedAttempt($user);
            }

            return null;
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            return null;
        }

        $user->forceFill([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        return $user;
    }

    public function recordFailedAttempt(User $user): void
    {
        $attempts = ($user->failed_login_attempts ?? 0) + 1;
        $maxAttempts = config('tich.auth.max_login_attempts', 5);
        $lockoutMinutes = config('tich.auth.lockout_minutes', 15);

        $updates = ['failed_login_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $updates['locked_until'] = now()->addMinutes($lockoutMinutes);
        }

        $user->forceFill($updates)->save();
    }

    public function postLoginDestination(User $user, Request $request): string
    {
        $request->session()->forget('mfa_verified_at');

        if ($this->mfaService->isMFARequired($user)) {
            if (! $user->mfa_enabled) {
                return route('mfa.setup');
            }

            return route('mfa.verify');
        }

        return route('dashboard');
    }

    public function markMfaVerified(Request $request, User $user): void
    {
        $verifiedAt = now()->toIso8601String();

        $request->session()->put('mfa_verified_at', $verifiedAt);
        $request->session()->put('mfa_verified_user_id', $user->id);

        if ($token = $user->currentAccessToken()) {
            cache()->put(
                "mfa_verified_token_{$token->id}",
                ['user_id' => $user->id, 'verified_at' => $verifiedAt],
                now()->addMinutes(config('tich.auth.mfa_session_minutes', 30))
            );
        }

        $this->mfaService->recordVerification($user);

        $user->forceFill(['mfa_verified' => 1])->save();
    }

    public function clearMfaSession(Request $request): void
    {
        $request->session()->forget(['mfa_verified_at', 'mfa_verified_user_id']);

        if ($user = $request->user()) {
            $user->currentAccessToken()?->delete();
        }
    }

    public function isMfaSessionValid(Request $request, User $user): bool
    {
        $minutes = config('tich.auth.mfa_session_minutes', 30);

        $verifiedAt = $request->session()->get('mfa_verified_at');
        $verifiedUserId = $request->session()->get('mfa_verified_user_id');

        if ($verifiedAt && (int) $verifiedUserId === (int) $user->id) {
            return now()->diffInMinutes($verifiedAt) <= $minutes;
        }

        if ($token = $user->currentAccessToken()) {
            $cached = cache()->get("mfa_verified_token_{$token->id}");

            if ($cached && (int) $cached['user_id'] === (int) $user->id) {
                return now()->diffInMinutes($cached['verified_at']) <= $minutes;
            }
        }

        return false;
    }

    public function verifyMfaCode(User $user, string $code): bool
    {
        $code = trim($code);

        if ($user->mfa_method === 'email') {
            return $this->mfaService->verifyEmailOTP($user, $code);
        }

        if ($user->mfa_method === 'auth_app') {
            return $this->mfaService->verifyTOTP($user, $code);
        }

        if ($this->mfaService->verifyBackupCode($user, $code)) {
            return true;
        }

        return $this->mfaService->verifyEmailOTP($user, $code)
            || $this->mfaService->verifyTOTP($user, $code);
    }

    public function registerUser(array $data): User
    {
        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'user_type' => $data['user_type'],
            'password_hash' => Hash::make($data['password']),
            'is_active' => 1,
        ]);

        $this->rbacService->assignDefaultRole($user);

        return $user;
    }

    public function issueApiToken(User $user, string $name = 'api-token'): string
    {
        return $user->createToken($name)->plainTextToken;
    }

    public function loginResponse(User $user, Request $request): array
    {
        $token = $this->issueApiToken($user, $request->userAgent() ?? 'api');

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'user_type' => $user->user_type,
            ],
            'mfa_required' => $this->mfaService->isMFARequired($user),
            'mfa_enabled' => (bool) $user->mfa_enabled,
            'mfa_setup_required' => $this->mfaService->isMFARequired($user) && ! $user->mfa_enabled,
            'roles' => $this->rbacService->getUserRoles($user),
        ];
    }
}
