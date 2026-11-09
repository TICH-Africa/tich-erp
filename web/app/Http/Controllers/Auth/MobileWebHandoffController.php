<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\MobileWebHandoffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileWebHandoffController extends Controller
{
    public function __construct(
        protected MobileWebHandoffService $handoffs,
        protected AuthService $authService,
    ) {}

    public function consume(Request $request, string $token): RedirectResponse
    {
        $record = $this->handoffs->consume($token);
        if (! $record?->user) {
            return redirect()
                ->route('login')
                ->withErrors(['login' => 'This sign-in link has expired or was already used. Open the mobile app and try again.']);
        }

        $user = $record->user;
        if (! $user->is_active) {
            return redirect()
                ->route('login')
                ->withErrors(['login' => 'This account is not active.']);
        }

        Auth::logout();
        Auth::login($user);
        $request->session()->regenerate();

        $destination = url($record->intended_path);
        $this->authService->storeIntendedUrl($request, $destination);

        app(\App\Services\AuditService::class)->log(
            'auth.mobile_web_handoff',
            'users',
            $user->id,
            null,
            [
                'email' => $user->email,
                'user_type' => $user->user_type,
                'intended_path' => $record->intended_path,
                'channel' => 'mobile_web_handoff',
            ],
            'Signed in via mobile app handoff',
            'success',
            $user->id,
            $request
        );

        return $this->authService->redirectAfterAuthentication($user, $request);
    }
}
