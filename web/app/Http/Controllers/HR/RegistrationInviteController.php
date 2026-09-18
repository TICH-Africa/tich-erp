<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\ErpRegistrationInviteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegistrationInviteController extends Controller
{
    public function __construct(
        protected ErpRegistrationInviteService $invites,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $result = $this->invites->send($validated['email'], $request->user(), 'hr');

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
