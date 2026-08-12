<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
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
            'recentInvitations' => $this->invites->recentInvitations(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $result = $this->invites->send($validated['email'], $request->user(), 'ict');

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
