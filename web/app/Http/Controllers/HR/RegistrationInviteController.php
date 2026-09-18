<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
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

        return $this->flashInviteResult($result);
    }

    public function inviteStaff(Request $request, Staff $staff): RedirectResponse
    {
        $result = $this->invites->sendForStaff($staff, $request->user(), 'hr');

        return $this->flashInviteResult($result);
    }

    /**
     * @param  array{success: bool, message: string}  $result
     */
    private function flashInviteResult(array $result): RedirectResponse
    {
        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }
}
