<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class WebAuthController extends Controller
{
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

        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::query()
            ->where($loginField, $credentials['login'])
            ->where('is_active', 1)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password_hash)) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'These credentials do not match our records.']);
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'This account is temporarily locked. Please try again later.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        if ($user->mfa_enabled) {
            $request->session()->forget('mfa_verified_at');

            return redirect()->route('mfa.verify');
        }

        return redirect()->intended(route('home'));
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

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'user_type' => $validated['user_type'],
            'password_hash' => Hash::make($validated['password']),
            'is_active' => 1,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with('status', 'Your account has been created successfully.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

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

    public function showMfaVerify(Request $request): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if ($request->session()->has('mfa_verified_at')) {
            return redirect()->route('home');
        }

        return view('auth.mfa-verify', [
            'mfaMethod' => Auth::user()->mfa_method,
        ]);
    }

    public function verifyMfa(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        return back()->withErrors([
            'code' => 'MFA verification is not yet wired for web sessions. Please contact your administrator.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('status', 'You have been signed out.');
    }
}
