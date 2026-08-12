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

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
