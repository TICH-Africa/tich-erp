<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use App\Services\ErpRegistrationInviteService;
use App\Services\MFAService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ErpRegistrationController extends Controller
{
    public function __construct(
        protected ErpRegistrationInviteService $invites,
        protected AuthService $authService,
        protected MFAService $mfaService,
    ) {}

    public function showInvite(string $token): View|RedirectResponse
    {
        $invitation = $this->invites->findActiveByToken($token);

        if (! $invitation) {
            abort(410, 'This registration link is invalid or has expired. Contact ICT or HR for a new invitation.');
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])->exists()) {
            return redirect()
                ->route('login')
                ->with('status', 'Your ERP account is already active. Sign in with your email and password.');
        }

        if ($invitation->staff?->user_id) {
            return redirect()
                ->route('login')
                ->with('status', 'Your ERP account is already active. Sign in with your email and password.');
        }

        return view('auth.register-invite', [
            'invitation' => $invitation,
            'staff' => $invitation->staff,
        ]);
    }

    public function storeInvite(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->invites->findActiveByToken($token);

        if (! $invitation) {
            abort(410, 'This registration link is invalid or has expired.');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ]);

        $user = $this->invites->completeRegistration($invitation, $validated['password'], $request);

        Auth::login($user);
        $request->session()->regenerate();

        if ($this->mfaService->isMFARequired($user)) {
            return redirect()
                ->route('mfa.setup')
                ->with('status', 'Account created. Please configure multi-factor authentication to continue.');
        }

        return $this->authService
            ->redirectAfterAuthentication($user, $request)
            ->with('status', 'Welcome to TICH ERP. Your account has been created successfully.');
    }
}
