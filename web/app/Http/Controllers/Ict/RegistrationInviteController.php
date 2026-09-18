<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Models\ErpRegistrationInvitation;
use App\Services\ErpRegistrationInviteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationInviteController extends Controller
{
    public function __construct(
        protected ErpRegistrationInviteService $invites,
    ) {}

    public function index(): View
    {
        return view('ict.registration-invites.index', [
            'recentInvitations' => $this->invites->recentInvitations(40),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $result = $this->invites->send($validated['email'], $request->user(), 'ict');

        return $this->flashInviteResult($result);
    }

    public function resend(Request $request, ErpRegistrationInvitation $invitation): RedirectResponse
    {
        $result = $this->invites->resend($invitation, $request->user(), 'ict');

        return $this->flashInviteResult($result);
    }

    /**
     * @param  array{success: bool, message: string, warning?: bool, register_url?: string}  $result
     */
    private function flashInviteResult(array $result): RedirectResponse
    {
        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $redirect = back()->with(
            ! empty($result['warning']) ? 'warning' : 'success',
            $result['message'],
        );

        if (! empty($result['register_url'])) {
            $redirect->with('invite_register_url', $result['register_url']);
        }

        return $redirect;
    }
}
