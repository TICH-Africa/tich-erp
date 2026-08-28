<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        protected MFAService $mfaService,
        protected RBACService $rbacService,
        protected AuditService $auditService,
    ) {}

    public function attemptLogin(string $login, string $password, ?Request $request = null): ?User
    {
        $user = $this->resolveUserByLogin($login);

        if (! $user || ! Hash::check($password, $user->password_hash)) {
            if ($user) {
                $this->recordFailedAttempt($user, $request, $login);
            } else {
                $this->auditService->log(
                    'auth.login.failed',
                    'users',
                    'unknown',
                    null,
                    ['login' => $login, 'channel' => $this->channel($request)],
                    'Invalid credentials',
                    'failure',
                    null,
                    $request
                );
            }

            return null;
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            $this->auditService->log(
                'auth.login.failed',
                'users',
                $user->id,
                null,
                ['login' => $login, 'locked_until' => $user->locked_until->toIso8601String(), 'channel' => $this->channel($request)],
                'Account locked',
                'failure',
                $user->id,
                $request
            );

            return null;
        }

        $user->forceFill([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $this->auditService->log(
            'auth.login.success',
            'users',
            $user->id,
            null,
            [
                'email' => $user->email,
                'user_type' => $user->user_type,
                'channel' => $this->channel($request),
            ],
            null,
            'success',
            $user->id,
            $request
        );

        return $user;
    }

    public function recordFailedAttempt(User $user, ?Request $request = null, ?string $login = null): void
    {
        $attempts = ($user->failed_login_attempts ?? 0) + 1;
        $maxAttempts = config('tich.auth.max_login_attempts', 5);
        $lockoutMinutes = config('tich.auth.lockout_minutes', 15);

        $updates = ['failed_login_attempts' => $attempts];
        $locked = false;

        if ($attempts >= $maxAttempts) {
            $updates['locked_until'] = now()->addMinutes($lockoutMinutes);
            $locked = true;
        }

        $user->forceFill($updates)->save();

        $this->auditService->log(
            'auth.login.failed',
            'users',
            $user->id,
            ['failed_login_attempts' => $attempts - 1],
            [
                'failed_login_attempts' => $attempts,
                'login' => $login ?? $user->email,
                'channel' => $this->channel($request),
            ],
            $locked ? 'Maximum login attempts exceeded' : 'Invalid password',
            'failure',
            $user->id,
            $request
        );

        if ($locked) {
            $this->auditService->log(
                'auth.login.locked',
                'users',
                $user->id,
                null,
                [
                    'locked_until' => $user->locked_until->toIso8601String(),
                    'failed_login_attempts' => $attempts,
                    'channel' => $this->channel($request),
                ],
                'Account locked after repeated failed login attempts',
                'success',
                $user->id,
                $request
            );
        }
    }

    public function postLoginDestination(User $user, Request $request): string
    {
        $request->session()->forget('mfa_verified_at');

        if ($this->mustCompleteMfaBeforeAccess($user, $request)) {
            return $this->mfaEntryRoute($user);
        }

        return $this->authenticatedHome($user);
    }

    public function mustCompleteMfaBeforeAccess(User $user, Request $request): bool
    {
        if (! $this->mfaService->isMFARequired($user)) {
            return false;
        }

        if (! $user->mfa_enabled) {
            return true;
        }

        return ! $this->isMfaSessionValid($request, $user);
    }

    public function mfaEntryRoute(User $user): string
    {
        return $user->mfa_enabled
            ? route('mfa.verify')
            : route('mfa.setup');
    }

    public function isSafeIntendedUrl(?string $url): bool
    {
        if (! $url) {
            return false;
        }

        if (! str_starts_with($url, url('/'))) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';

        foreach (['/login', '/register', '/mfa/', '/logout'] as $blocked) {
            if (str_starts_with($path, $blocked)) {
                return false;
            }
        }

        return true;
    }

    public function rememberIntendedUrl(Request $request): void
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return;
        }

        if ($request->routeIs('login', 'register', 'password.*', 'mfa.*', 'logout', 'home', 'employee.profile.*')) {
            return;
        }

        $this->storeIntendedUrl($request, $request->fullUrl());
    }

    public function storeIntendedUrl(Request $request, ?string $url): void
    {
        if ($this->isSafeIntendedUrl($url)) {
            $request->session()->put('url.intended', $url);
        }
    }

    public function redirectAfterAuthentication(User $user, Request $request): RedirectResponse
    {
        $request->session()->forget('mfa_verified_at');

        if ($this->mustCompleteMfaBeforeAccess($user, $request)) {
            return redirect()->to($this->mfaEntryRoute($user));
        }

        if (! $this->rbacService->hasRole($user, 'Super Admin')) {
            app(EmployeePortalService::class)->ensureStaffProfile($user);
            $user->refresh();
        }

        $home = $this->authenticatedHome($user);

        if (app(EmployeeProfileCompletenessService::class)->mustCompleteProfile($user)) {
            return redirect()
                ->to($home)
                ->with('warning', 'Complete your employee profile before using the ERP. This is required for accountability and emergency contact records.');
        }

        // Do not honour an intended /dashboard URL for new employees — send them to the portal first.
        if ($this->shouldPreferEmployeeHome($user)) {
            return redirect()->to($home);
        }

        return redirect()->intended($home);
    }

    public function redirectAfterMfa(User $user, Request $request): RedirectResponse
    {
        if (! $this->rbacService->hasRole($user, 'Super Admin')) {
            app(EmployeePortalService::class)->ensureStaffProfile($user);
            $user->refresh();
        }

        $home = $this->authenticatedHome($user);

        if (app(EmployeeProfileCompletenessService::class)->mustCompleteProfile($user)) {
            return redirect()
                ->to($home)
                ->with('warning', 'Complete your employee profile before using the ERP. This is required for accountability and emergency contact records.');
        }

        if ($this->shouldPreferEmployeeHome($user)) {
            return redirect()->to($home);
        }

        return redirect()->intended($home);
    }

    private function shouldPreferEmployeeHome(User $user): bool
    {
        if ($this->isEnrolledStudent($user) || $this->rbacService->hasRole($user, 'Super Admin')) {
            return false;
        }

        if (app(EmployeeAssignmentService::class)->isAwaitingDepartmentAssignment($user)) {
            return false;
        }

        return $user->user_type === 'staff'
            || app(EmployeePortalService::class)->hasEmployeeProfile($user);
    }

    public function authenticatedHome(User $user): string
    {
        if ($this->isEnrolledStudent($user)) {
            return route('portal.dashboard');
        }

        // Super Admins use the full platform — never force employee profile completion.
        if ($this->rbacService->hasRole($user, 'Super Admin')) {
            return route('dashboard');
        }

        $employeePortal = app(EmployeePortalService::class);
        $employeePortal->ensureStaffProfile($user);
        $user->refresh();

        $isEmployee = $user->user_type === 'staff'
            || $employeePortal->hasEmployeeProfile($user);

        // Invited users and staff are employees — home depends on profile + department assignment.
        if ($isEmployee) {
            if (app(EmployeeProfileCompletenessService::class)->mustCompleteProfile($user)) {
                return route('employee.profile.edit');
            }

            if (app(EmployeeAssignmentService::class)->isAwaitingDepartmentAssignment($user)) {
                return route('dashboard');
            }

            if ($employeePortal->hasEmployeeProfile($user)) {
                return route('employee.dashboard');
            }

            return route('account.start');
        }

        if (app(StaffPortalService::class)->isTeachingStaff($user)) {
            return route('staff.dashboard');
        }

        if ($this->rbacService->hasPermission($user, 'finance.read')) {
            return route('finance.dashboard');
        }

        if ($this->rbacService->hasPermission($user, 'hr.read')) {
            return route('hr.dashboard');
        }

        if ($this->rbacService->hasPermission($user, 'admissions.read')) {
            return route('admissions.dashboard');
        }

        if ($this->rbacService->hasPermission($user, 'academics.read')) {
            return route('departments.academics.dashboard');
        }

        if ($this->rbacService->hasPermission($user, 'research.read')) {
            return route('research.dashboard');
        }

        if ($this->rbacService->hasPermission($user, 'qa.read')) {
            return route('qa.dashboard');
        }

        if ($this->rbacService->hasPermission($user, 'procurement.read')) {
            return route('procurement.dashboard');
        }

        if ($this->rbacService->hasPermission($user, 'dashboard.access')) {
            return route('dashboard');
        }

        return route('account.start');
    }

    public function isEnrolledStudent(User $user): bool
    {
        return $user->isEnrolledStudent();
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

        $this->auditService->log(
            'auth.mfa.verify.success',
            'users',
            $user->id,
            null,
            ['mfa_method' => $user->mfa_method, 'channel' => $this->channel($request)],
            null,
            'success',
            $user->id,
            $request
        );
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

    public function registerUser(array $data, ?Request $request = null): User
    {
        $user = User::create([
            'email' => $data['email'],
            'user_type' => $data['user_type'],
            'password_hash' => Hash::make($data['password']),
            'is_active' => 1,
        ]);

        $this->rbacService->assignDefaultRole($user);

        $this->auditService->log(
            'auth.register',
            'users',
            $user->id,
            null,
            [
                'email' => $user->email,
                'user_type' => $user->user_type,
            ],
            'Self-registration',
            'success',
            $user->id,
            $request
        );

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
                'name' => $user->displayName(),
                'email' => $user->email,
                'user_type' => $user->user_type,
            ],
            'mfa_required' => $this->mfaService->isMFARequired($user),
            'mfa_enabled' => (bool) $user->mfa_enabled,
            'mfa_setup_required' => $this->mfaService->isMFARequired($user) && ! $user->mfa_enabled,
            'roles' => $this->rbacService->getUserRoles($user),
        ];
    }

    public function logLogout(?User $user, ?Request $request = null): void
    {
        if (! $user) {
            return;
        }

        $this->auditService->log(
            'auth.logout',
            'users',
            $user->id,
            null,
            ['channel' => $this->channel($request)],
            null,
            'success',
            $user->id,
            $request
        );
    }

    public function logMfaVerifyFailed(User $user, ?Request $request = null): void
    {
        $this->auditService->log(
            'auth.mfa.verify.failed',
            'users',
            $user->id,
            null,
            ['mfa_method' => $user->mfa_method, 'channel' => $this->channel($request)],
            'Invalid or expired verification code',
            'failure',
            $user->id,
            $request
        );
    }

    private function resolveUserByLogin(string $login): ?User
    {
        $user = User::query()
            ->where('is_active', 1)
            ->where('email', $login)
            ->first();

        if ($user) {
            return $user;
        }

        if (! filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $staffUserId = Staff::query()
            ->where(function ($query) use ($login) {
                $query->where('organisation_email', $login)
                    ->orWhere('primary_email', $login);
            })
            ->whereNotNull('user_id')
            ->value('user_id');

        if (! $staffUserId) {
            return null;
        }

        return User::query()
            ->where('id', $staffUserId)
            ->where('is_active', 1)
            ->first();
    }

    private function channel(?Request $request): string
    {
        if (! $request) {
            return 'system';
        }

        return $request->expectsJson() || $request->is('api/*') ? 'api' : 'web';
    }
}
