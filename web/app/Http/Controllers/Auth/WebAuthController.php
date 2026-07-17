<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\MFAService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected MFAService $mfaService,
    ) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->authService->attemptLogin($credentials['login'], $credentials['password'], $request);

        if (! $user) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'These credentials do not match our records or the account is locked.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($this->authService->postLoginDestination($user, $request));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'user_type' => ['required', 'in:student,staff,external'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ]);

        $user = $this->authService->registerUser($validated);

        Auth::login($user);
        $request->session()->regenerate();

        if ($this->mfaService->isMFARequired($user)) {
            return redirect()
                ->route('mfa.setup')
                ->with('status', 'Account created. Please configure multi-factor authentication to continue.');
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'Your account has been created successfully.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        return back()->with('status', 'If an account exists for that email, a password reset link will be sent shortly.');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        return back()->with('status', 'Password reset is not yet configured. Please contact your administrator.');
    }

    public function showMfaSetup(Request $request): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->mfa_enabled && $this->authService->isMfaSessionValid($request, $user)) {
            return redirect()->route('dashboard');
        }

        return view('auth.mfa-setup', [
            'userType' => $user->user_type,
        ]);
    }

    public function setupMfa(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:email,auth_app'],
            'code' => ['nullable', 'string'],
        ]);

        $user = Auth::user();

        if ($validated['method'] === 'email') {
            if (empty($validated['code'])) {
                $delivery = $this->mfaService->sendEmailOTP($user, $request);

                if (! $delivery['sent'] && ! config('app.debug')) {
                    return back()->withErrors([
                        'code' => 'Verification email could not be sent. Ask an administrator to configure Gmail App Password in MAIL_PASSWORD.',
                    ]);
                }

                return back()->with('status', $delivery['sent']
                    ? 'A verification code has been sent to your email.'
                    : 'Email delivery failed — use the development code below if shown.');
            }

            if (! $this->mfaService->verifyEmailOTP($user, $validated['code'])) {
                $this->authService->logMfaVerifyFailed($user, $request);

                return back()->withErrors(['code' => 'Invalid or expired verification code.']);
            }

            $this->mfaService->enableMFA($user, 'email', null, null, $request);
            $this->authService->markMfaVerified($request, $user);

            return redirect()->route('dashboard')->with('status', 'Email MFA is now active on your account.');
        }

        if (empty($validated['code'])) {
            $secret = $this->mfaService->generateTOTPSecret();
            $this->mfaService->stageTOTPSecret($user, $secret);

            return back()
                ->with('totp_secret', $secret)
                ->with('totp_uri', $this->mfaService->getTOTPQRCodeURI($user, $secret))
                ->with('status', 'Scan the QR code with your authenticator app, then enter the 6-digit code.');
        }

        if (! $this->mfaService->verifyTOTP($user, $validated['code'])) {
            $this->authService->logMfaVerifyFailed($user, $request);

            return back()
                ->with('totp_secret', $user->mfa_secret_temp)
                ->with('totp_uri', $this->mfaService->getTOTPQRCodeURI($user))
                ->withErrors(['code' => 'Invalid authenticator code. Please try again.']);
        }

        $backupCodes = $this->mfaService->generateBackupCodes();
        $this->mfaService->enableMFA($user, 'auth_app', $user->mfa_secret_temp, $backupCodes, $request);
        $this->authService->markMfaVerified($request, $user);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Authenticator MFA enabled. Save your backup codes: '.implode(', ', $backupCodes));
    }

    public function showMfaVerify(Request $request): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (! $user->mfa_enabled) {
            return redirect()->route('mfa.setup');
        }

        if ($this->authService->isMfaSessionValid($request, $user)) {
            return redirect()->route('dashboard');
        }

        if ($user->mfa_method === 'email') {
            $delivery = $this->mfaService->sendEmailOTP($user, $request);

            if (! $delivery['sent'] && ! config('app.debug')) {
                return redirect()
                    ->route('login')
                    ->withErrors(['login' => 'Could not send MFA verification email. Please contact your administrator.']);
            }
        }

        return view('auth.mfa-verify', [
            'mfaMethod' => $user->mfa_method,
        ]);
    }

    public function verifyMfa(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = Auth::user();

        if (! $this->authService->verifyMfaCode($user, $request->code)) {
            $this->authService->logMfaVerifyFailed($user, $request);

            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        $this->authService->markMfaVerified($request, $user);

        return redirect()->intended(route('dashboard'));
    }

    public function resendMfaCode(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->mfa_method === 'email') {
            $delivery = $this->mfaService->sendEmailOTP($user, $request);

            if ($delivery['sent']) {
                return back()->with('status', 'A new verification code has been sent.');
            }

            return back()->with('status', config('app.debug')
                ? 'Email delivery failed — use the development code below if shown.'
                : 'Could not resend verification email. Please try again later.');
        }

        return back()->with('status', 'A new verification code has been sent.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $this->authService->logLogout($user, $request);

        Auth::logout();
        $this->authService->clearMfaSession($request);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('status', 'You have been signed out.');
    }
}
